<?php
namespace Mfonte\ImdbScraper;

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use Mfonte\ImdbScraper\Entities\Title;
use Mfonte\ImdbScraper\Entities\Person;

/**
* Class Parser
*
* @package mfonte/imdb-scraper
* @author Maurizio Fonte
*/
class Parser
{
    /**
     * @var string|null The IMDB identifier
     */
    private $id = null;

    /**
     * @var array $options
     */
    private $options = [];

    /**
     * @var array $properties
     */
    private $properties = [
        'metadata' => [],
        'isTvSeries' => false,
        'title' => '',
        'originalTitle' => '',
        'year' => null,
        'length' => '',
        'rating' => null,
        'ratingVotes' => null,
        'popularityScore' => null,
        'metaScore' => null,
        'genres' => [],
        'posterUrl' => null,
        'trailerUrl' => null,
        'plot' => '',
        'actors' => [],
        'similars' => [],
        'seasonRefs' => [],
        'seasons' => [],
        'credits' => [],
    ];

    /**
     * Expects an IMDB identifier in the form of 'tt1234567'
     *
     * @param string|null $id - IMDB identifier in the form of 'tt1234567'
     *
     * @return Parser
     */
    public static function parse(string $id, array $options = []) : Parser
    {
        self::validateId($id);

        return (new self($id, $options))->runParser();
    }

    /**
     * Constructor. Expects an IMDB identifier in the form of 'tt1234567'
     *
     * @param string $id
     */
    public function __construct(string $id, array $options = [])
    {
        self::validateId($id);
        $this->id = $id;
        $this->options = $options;
    }

    /**
     * Run the parser against the IMDB title HTML DOM
     *
     * @return Parser
     */
    public function runParser() : Parser
    {
        $dom = new Dom;

        $page = $dom->fetch("/title/{$this->id}/", $this->options);
        $pageCredits = $dom->fetch("/title/{$this->id}/fullcredits", $this->options);

        // set the properties
        $this->properties['id'] = $this->id;
        $this->properties['link'] = $this->getPermalink($page);
        $this->properties['metadata'] = $this->getMetadata($page);
        $this->properties['isTvSeries'] = $this->isTvSeries($page);
        $this->properties['title'] = $this->getTitle($page);
        $this->properties['originalTitle'] = $this->getOriginalTitle($page);
        $this->properties['year'] = $this->getYear($page);
        $this->properties['length'] = $this->getLength($page);
        $this->properties['rating'] = $this->getRating($page);
        $this->properties['ratingVotes'] = $this->getRatingVotes($page);
        $this->properties['popularityScore'] = $this->getPopularityScore($page);
        $this->properties['metaScore'] = $this->getMetaScore($page);
        $this->properties['genres'] = $this->getGenres($page);
        $this->properties['posterUrl'] = $this->getPosterUrl($page);
        $this->properties['trailerUrl'] = $this->getTrailerUrl($page);
        $this->properties['plot'] = $this->getPlot($page);
        $this->properties['actors'] = $this->getActors($page);
        $this->properties['similars'] = $this->getSimilars($page);
        $this->properties['seasonRefs'] = $this->getSeasonNumbers($page);
        $this->properties['credits'] = $this->getCredits($pageCredits);

        // if it's a series, and "seasons" options is true, then fetch the episodes for each season
        if (
            $this->properties['isTvSeries'] &&
            array_key_exists('seasons', $this->options) &&
            $this->options['seasons'] &&
            count($this->properties['seasonRefs']) > 0
        ) {
            $seasons = [];
            foreach ($this->properties['seasonRefs'] as $seasonNumber) {
                $page = $dom->fetch("/title/{$this->id}/episodes/?season={$seasonNumber}", $this->options);
                $episodes = $this->getEpisodes($page);

                // if there are episodes, add them to the seasons array
                if (count($episodes) > 0) {
                    $seasons[] = [
                        'id' => "S" . str_pad($seasonNumber, 2, '0', STR_PAD_LEFT),
                        'number' => $seasonNumber,
                        'episodes' => $episodes,
                    ];
                }
            }
            $this->properties['seasons'] = $seasons;
        }

        return $this;
    }

    /**
     * Get the properties
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->properties;
    }

    /**
     * Gets a Title instance from the properties
     *
     * @return Title
     */
    public function toTitle() : Title
    {
        return Title::newFromArray($this->properties);
    }

    /**
     * Gets the permalink of the movie or TV show, by parsing the <meta property="og:url" content="..." />
     *
     * @param HtmlDomParser $dom
     *
     * @return string
     */
    public function getPermalink(HtmlDomParser $dom) : string
    {
        $meta = $dom->findOneOrFalse('meta[property="og:url"]');
        if ($meta) {
            return $meta->getAttribute('content');
        }

        return "https://www.imdb.com/title/{$this->id}/";
    }

    /**
     * Get the metadata of the movie or TV show, by parsing the JSON-LD script tag
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getMetadata(HtmlDomParser $dom) : array
    {
        $scripts = $dom->findMultiOrFalse('script');
        foreach ($scripts as $script) {
            if ($script->getAttribute('type') === 'application/ld+json') {
                $json = json_decode($script->innerText(), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
        }
        return [];
    }

    /**
     * Get the "is series" boolean value, determining if the title is a series or not
     *
     * @param HtmlDomParser $dom
     *
     * @return bool
     */
    public function isTvSeries(HtmlDomParser $dom) : bool
    {
        $infoContainer = self::getInfoContainer($dom);
        if ($infoContainer) {
            $listItems = $infoContainer->find('li');
            foreach ($listItems as $item) {
                $text = self::clean($item->innerText());

                // if the text is "TV Series" or "Serie TV", then it's a series
                if (preg_match('/(TV Series|Serie TV)/i', $text, $matches)) {
                    return true;
                }
            }
        }

        $type = $this->getMetadataProp('@type');
        return ($type === 'TVSeries' || $type === 'TVSeason');
    }

    /**
     * Get the title of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string
     */
    public function getTitle(HtmlDomParser $dom) : string
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $title = $heroContainer->findOneOrFalse('h1[data-testid="hero__pageTitle"] span');
            if ($title) {
                return self::clean($title->innerText());
            }
        }

        return $this->getMetadataProp('name') ?? '';
    }

    /**
     * Get the original title of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string
     */
    public function getOriginalTitle(HtmlDomParser $dom) : string
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $title = $heroContainer->findOneOrFalse('h1[data-testid="hero__pageTitle"]');
            if ($title) {
                // find the closest parent <div> of the title
                $parent = $title->parentNode();

                // if the parent has a <div>, and it contains the text "Original title" or "Titolo originale", then we can assume the next sibling is the original title
                $div = $parent->findOneOrFalse('div');

                if ($div && (stripos($div->innerText(), 'original title') !== false || stripos($div->innerText(), 'titolo originale') !== false)) {
                    return self::clean(preg_replace('/(Original title|Titolo originale)\s*:?\s*/i', '', $title->innerText()));
                }
            }
        }

        // fallback: return the title
        return $this->getTitle($dom);
    }

    /**
     * Get the Year of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return int|null
     */
    public function getYear(HtmlDomParser $dom) : ?int
    {
        $infoContainer = self::getInfoContainer($dom);
        if ($infoContainer) {
            $listItems = $infoContainer->find('li');
            foreach ($listItems as $item) {
                $text = self::clean($item->innerText());

                if (preg_match('/([0-9]{4})(\s*[–\-]\s*([0-9]{4}))?/ui', $text, $matches)) {
                    return (int) $matches[1];
                }
            }
        }

        $datePublished = $this->getMetadataProp('datePublished');
        if ($datePublished) {
            return (int) substr($datePublished, 0, 4);
        }

        return null;
    }

    /**
     * Get the length of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string|null
     */
    public function getLength(HtmlDomParser $dom) : ?string
    {
        $infoContainer = self::getInfoContainer($dom);
        if ($infoContainer) {
            $listItems = $infoContainer->find('li');
            foreach ($listItems as $item) {
                $text = self::clean($item->innerText());
                if (preg_match("/([0-9]+[h|m]\s*[0-9]+[h|m])|([0-9]+[h|m])/ui", $text, $matches)) {
                    return $matches[0];
                }
            }
        }

        $duration = $this->getMetadataProp('duration');
        if ($duration) {
            $duration = str_ireplace(['PT', 'H', 'M'], ['', 'h ', 'm '], $duration);
            return trim($duration);
        }

        return null;
    }

    /**
     * Get the rating of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return float|null
     */
    public function getRating(HtmlDomParser $dom) : ?float
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $rating = $heroContainer->findOneOrFalse('div[data-testid="hero-rating-bar__aggregate-rating__score"] span');
            if ($rating) {
                return (float) str_replace(',', '.', self::clean($rating->innerText()));
            }
        }

        return $this->getMetadataProp('aggregateRating.ratingValue') ?? null;
    }

    /**
     * Get the number of votes for the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return int|null
     */
    public function getRatingVotes(HtmlDomParser $dom) : ?int
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $ratingContainer = $heroContainer->findOneOrFalse('div[data-testid="hero-rating-bar__aggregate-rating__score"]');
            if ($ratingContainer) {
                // get the parent <div> of the rating container, then the last <div>
                $votesContainer = $ratingContainer->parentNode()->find('div', -1);
                if ($votesContainer) {
                    $text = self::clean($votesContainer->innerText());
                    return self::parseCount($text);
                }
            }
        }

        return $this->getMetadataProp('aggregateRating.ratingCount') ?? null;
    }

    /**
     * Get the Popularity Score of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return int|null
     */
    public function getPopularityScore(HtmlDomParser $dom) : ?int
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $popularityScore = $heroContainer->findOneOrFalse('div[data-testid="hero-rating-bar__popularity__score"]');
            if ($popularityScore) {
                return (int) str_replace(',', '.', self::clean($popularityScore->innerText()));
            }
        }

        return null;
    }

    /**
     * Get the MetaScore of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return int|null
     */
    public function getMetaScore(HtmlDomParser $dom) : ?int
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $listContainer = $heroContainer->findOneOrFalse('ul[data-testid="reviewContent-all-reviews"]');
            if ($listContainer) {
                $listItems = $listContainer->find('li');
                foreach ($listItems as $item) {
                    $spans = $item->find('span.three-Elements span');
                    if (count($spans) > 0) {
                        foreach ($spans as $i => $span) {
                            $text = self::clean($span->innerText());
                            if (strtolower($text) === 'metascore') {
                                return (int) self::clean($spans[$i - 1]->innerText());
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get the genres of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getGenres(HtmlDomParser $dom) : array
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $interestsContainer = $heroContainer->findOneOrFalse('div[data-testid="interests"]');
            $genres = $interestsContainer->find('a.ipc-chip span');
            if (count($genres) > 0) {
                return array_map(function ($genre) {
                    return self::clean($genre->innerText());
                }, (array) $genres);
            }
        }

        return $this->getMetadataProp('genre') ?? [];
    }

    /**
     * Get the Poster URL of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string|null
     */
    public function getPosterUrl(HtmlDomParser $dom) : ?string
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $posterContainer = $heroContainer->findOneOrFalse('div[data-testid="hero-media__poster"]');
            if ($posterContainer) {
                // first img.ipc-image has the poster
                $poster = $posterContainer->findOneOrFalse('img.ipc-image');
                if ($poster) {
                    return self::absolutizeUrl($poster->getAttribute('src'));
                }
            }
        }

        return $this->getMetadataProp('image') ?? null;
    }

    /**
     * Get the Trailer URL of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string|null
     */
    public function getTrailerUrl(HtmlDomParser $dom) : ?string
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $trailerContainer = $heroContainer->findOneOrFalse('a[data-testid="video-player-slate-overlay"]');
            if ($trailerContainer) {
                $href = $trailerContainer->getAttribute('href');
                if (strpos($href, '/') === 0) {
                    return self::absolutizeUrl($href);
                }

                return $href;
            }
        }

        return $this->getMetadataProp('trailer.url') ?? null;
    }

    /**
     * Get the Plot of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return string
     */
    public function getPlot(HtmlDomParser $dom) : string
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $plotContainer = $heroContainer->findOneOrFalse('p[data-testid="plot"]');
            if ($plotContainer) {
                // use span[data-testid="plot-xl"] if available, else, use the first span
                $plot = $plotContainer->findOneOrFalse('span[data-testid="plot-xl"]') ?? $plotContainer->findOneOrFalse('span');
                if ($plot) {
                    return self::clean($plot->innerText());
                }
            }
        }

        return $this->getMetadataProp('description') ?? '';
    }

    /**
     * Get the Actors of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getActors(HtmlDomParser $dom) : array
    {
        $castContainer = self::getCastContainer($dom);
        if ($castContainer) {
            $castElements = $castContainer->find('div[data-testid="title-cast-item"]');
            if (count($castElements) > 0) {
                $cast = [];
                foreach ($castElements as $element) {
                    $imgElement = $element->findOneOrFalse('img');
                    $actorElement = $element->findOneOrFalse('a[data-testid="title-cast-item__actor"]');
                    $characterElement = $element->findOneOrFalse('a[data-testid="cast-item-characters-link"]');

                    $img = $imgElement ? self::absolutizeUrl($imgElement->getAttribute('src')) : null;
                    $actor = $actorElement ? self::clean($actorElement->innerText()) : null;
                    $link = $actorElement ? self::absolutizeUrl($actorElement->getAttribute('href')) : null;
                    $character = $characterElement ? self::clean($characterElement->innerText()) : null;

                    $id = null;
                    if ($link && preg_match('/\/name\/(nm[0-9]{7,8})\//', $link, $matches)) {
                        $id = $matches[1];
                    }

                    $cast[] = [
                        'type' => Person::TYPE_ACTOR,
                        'id' => $id,
                        'image' => $img,
                        'name' => $actor,
                        'link' => $link,
                        'character' => $character,
                    ];
                }
                return $cast;
            }
        }

        $cast = $this->getMetadataProp('actor.*.name');
        if (is_array($cast) && count($cast) > 0) {
            return array_map(function ($actor) {
                return [
                    'type' => Person::TYPE_ACTOR,
                    'id' => null,
                    'image' => null,
                    'name' => $actor,
                    'link' => null,
                    'character' => null,
                ];
            }, $cast);
        }

        return [];
    }

    /**
     * Get the Similars of the movie or TV show
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getSimilars(HtmlDomParser $dom) : array
    {
        $similarsContainer = $dom->findOneOrFalse('section.ipc-page-section[data-testid="MoreLikeThis"]');
        if ($similarsContainer) {
            $similarsElements = $similarsContainer->find('div.ipc-poster-card[role="group"]');
            if (count($similarsElements) > 0) {
                $similars = [];
                foreach ($similarsElements as $element) {
                    $linkElement = $element->findOneOrFalse('a.ipc-poster-card__title');

                    $title = $linkElement ? self::clean($linkElement->find('span[data-testid="title"]', 0)->innerText()) : null;
                    $link = $linkElement ? self::absolutizeUrl($linkElement->getAttribute('href')) : null;

                    $id = null;
                    if ($link && preg_match('/\/title\/(tt[0-9]{7,8})\//', $link, $matches)) {
                        $id = $matches[1];
                    }

                    $similars[] = [
                        'id' => $id,
                        'title' => $title,
                        'link' => $link,
                    ];
                }
                return $similars;
            }
        }

        return [];
    }

    public function getCredits(HtmlDomParser $dom) : array
    {
        $creditSections = $dom->findMultiOrFalse('.ipc-page-section.ipc-page-section--base');
        $creditsByCategory = [];
        if (!$creditSections) {
            return [];
        }

        foreach ($creditSections as $creditSection) {
            $creditCategory = $creditSection->findOneOrFalse('h3 span');
            if (!$creditCategory) {
                continue;
            }
            $creditCategory = strtolower($creditCategory->innerText());
            $creditsByCategory[$creditCategory] = [];

            $creditEntries = $creditSection->findMultiOrFalse('li.ipc-metadata-list-summary-item');
            if (!$creditEntries) {
                continue;
            }
            foreach($creditEntries as $creditEntry) {
                $creditEntry = $creditEntry->findOneOrFalse('div')?->findOneOrFalse('div')?->findOneOrFalse('div');
                if(!$creditEntry ) {
                    continue;
                }

                $creditedPersonImage = $creditEntry->findOneOrFalse('img');
                $creditedPersonImage = $creditedPersonImage ? self::absolutizeUrl($creditedPersonImage) : null;

                $creditedPersonName = $creditEntry->findOneOrFalse('a')->innerText();
                $creditedPersonLink = self::clean(
                    self::absolutizeUrl(
                        $creditEntry->findOneOrFalse('a')->getAttribute('href')
                    )
                );
                if (preg_match('/\/name\/(nm\d+)/', $creditedPersonLink, $matches)) {
                    $creditedPersonId = $matches[1];
                } else {
                    $creditedPersonId = null;
                }
                if(!$creditedPersonName || !$creditedPersonLink) {
                    continue;
                }

                if(!$creditedFor) {
                    $creditedFor = $creditCategory;
                }
                $creditsByCategory[$creditCategory][] = [
                    'type' => $creditedFor,
                    'name' => $creditedPersonName,
                    'link' => $creditedPersonLink,
                    'image' => $creditedPersonImage,
                    'id' => $creditedPersonId
                ];
            }
        }
        return $creditsByCategory;
    }

    /**
     * Get the Seasons Numbers of the TV show (if it's a series)
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getSeasonNumbers(HtmlDomParser $dom) : array
    {
        $isTvSeries = $this->properties['isTvSeries'];
        if (!$isTvSeries) {
            return [];
        }

        $seasonsContainer = $dom->findOneOrFalse('div[data-testid="episodes-browse-episodes"]');
        if ($seasonsContainer) {
            $seasonsElements = $seasonsContainer->find('select#browse-episodes-season option');
            if (count($seasonsElements) > 0) {
                $seasons = array_values(array_filter(array_map(function ($element) {
                    $value = intval($element->getAttribute('value'));
                    return ($value > 0) ? $value : null;
                }, (array) $seasonsElements)));

                // sort the seasons in ascending order
                sort($seasons, SORT_NUMERIC);

                return $seasons;
            } else {
                // fallback searching <a> elements that match /title/tt[0-9]+/episodes?season=
                $seasonsElements = $seasonsContainer->find('a');
                if (count($seasonsElements) > 0) {
                    $seasons = array_values(array_filter(array_map(function ($element) {
                        $href = $element->getAttribute('href');
                        if (preg_match('/\/title\/tt[0-9]+\/episodes\?season=([0-9]+)/', $href, $matches)) {
                            return (int) $matches[1];
                        }
                        return null;
                    }, (array) $seasonsElements)));

                    // sort the seasons in ascending order
                    sort($seasons, SORT_NUMERIC);

                    return $seasons;
                }
            }
        }

        return [];
    }

    /**
     * Get the Episodes of the TV show (if it's a series)
     *
     * @param HtmlDomParser $dom
     *
     * @return array
     */
    public function getEpisodes(HtmlDomParser $dom) : array
    {
        $episodes = [];
        $episodeContainers = $dom->find('article.episode-item-wrapper');
        if (count($episodeContainers) > 0) {
            foreach ($episodeContainers as $container) {
                $imgElement = $container->findOneOrFalse('img.ipc-image');
                $titleElement = $container->findOneOrFalse('h4[data-testid="slate-list-card-title"]');
                $linkElement = $container->findOneOrFalse('h4[data-testid="slate-list-card-title"] a');
                $airDateElement = ($titleElement) ? $titleElement->parentNode()->findOneOrFalse('span') : null;
                $plotElement = $container->findOneOrFalse('div.ipc-html-content.ipc-html-content--base.ipc-html-content--display-inline[role="presentation"]');
                $ratingContainer = $container->findOneOrFalse('div[data-testid="ratingGroup--container"]');

                $img = $imgElement ? self::absolutizeUrl($imgElement->getAttribute('src')) : null;
                $title = $titleElement ? self::clean($titleElement->innerText()) : null;
                $link = $linkElement ? self::absolutizeUrl($linkElement->getAttribute('href')) : null;
                $airDate = $airDateElement ? self::parseDate(self::clean($airDateElement->innerText())) : null;
                $plot = $plotElement ? self::clean($plotElement->innerText()) : null;
                $rating = $ratingContainer ? self::clean($ratingContainer->findOne('span.ipc-rating-star--rating')->innerText()) : null;
                $ratingVotes = $ratingContainer ? self::parseCount(self::clean($ratingContainer->findOne('span.ipc-rating-star--voteCount')->innerText())) : null;

                // match the season and episode number from the title (S8.E1 ∙ The Locomotion Interruption)
                $seasonNumber = null;
                $episodeNumber = null;
                if ($title && preg_match('/s([0-9]+)[\s\.\-]*e([0-9]+)/i', $title, $matches)) {
                    $seasonNumber = (int) $matches[1];
                    $episodeNumber = (int) $matches[2];

                    // remove the full match from the title
                    $title = str_replace($matches[0], '', $title);

                    // remove the bullet and any leading/trailing spaces
                    $title = trim(str_replace('∙', '', $title));
                }

                // extract the IMDB ID from the link
                $id = null;
                if ($link && preg_match('/\/title\/(tt[0-9]{7,8})\//', $link, $matches)) {
                    $id = $matches[1];
                }

                $episodes[] = [
                    'id' => $id,
                    'img' => $img,
                    'title' => $title,
                    'link' => $link,
                    'seasonNumber' => $seasonNumber,
                    'episodeNumber' => $episodeNumber,
                    'airDate' => $airDate,
                    'plot' => $plot,
                    'rating' => $rating,
                    'ratingVotes' => $ratingVotes,
                ];
            }
        }

        return $episodes;
    }

    /**
     * Get a Metadata Property, navigating through the LD-JSON metadata with dot notation.
     *
     * @param string $dotNotationProp The dot notation string (e.g., "actor.*.name").
     * @param array|null $currMetadata Optional current metadata to search in.
     * @return mixed The extracted metadata or null if not found.
     */
    private function getMetadataProp(string $dotNotationProp, ?array $currMetadata = null)
    {
        $keys = explode('.', $dotNotationProp);
        $metadata = ($currMetadata) ? $currMetadata : $this->properties['metadata'] ?? [];

        foreach ($keys as $i => $key) {
            if (is_array($metadata) && !array_key_exists($key, $metadata)) {
                $metadata = [];
            } else if (is_array($metadata) && array_key_exists($key, $metadata)) {
                $metadata = $metadata[$key];
            } elseif (is_array($metadata) && $key === '*') {
                $innerKeys = array_slice($keys, $i + 1);
                $metadata = array_map(function ($item) use ($innerKeys) {
                    return $this->getMetadataProp(implode('.', $innerKeys), $item);
                }, $metadata);
            }
        }

        if (empty($metadata)) {
            return null;
        }

        return $metadata;
    }

    /**
     * Get the hero container
     *
     * @param HtmlDomParser $dom
     *
     * @return SimpleHtmlDom|null
     */
    private static function getHeroContainer(HtmlDomParser $dom) : ?SimpleHtmlDom
    {
        $elements = $dom->find('section.ipc-page-section[data-testid="hero-parent"]');

        if (count($elements) === 0) {
            return null;
        }

        return array_values((array) $elements)[0];
    }

    /**
     * Get the Cast container
     *
     * @param HtmlDomParser $dom
     *
     * @return SimpleHtmlDom|null
     */
    private static function getCastContainer(HtmlDomParser $dom) : ?SimpleHtmlDom
    {
        $elements = $dom->find('section.ipc-page-section[data-testid="title-cast"]');

        if (count($elements) === 0) {
            return null;
        }

        return array_values((array) $elements)[0];
    }

    /**
     * Get the info container UL
     *
     * @param HtmlDomParser $dom
     *
     * @return SimpleHtmlDom|null
     */
    private static function getInfoContainer(HtmlDomParser $dom) : ?SimpleHtmlDom
    {
        $heroContainer = self::getHeroContainer($dom);
        if ($heroContainer) {
            $listContainers = $heroContainer->find('ul.ipc-inline-list[role="presentation"]');
            if ($listContainers) {
                // among all listContainers, find the one who does not have data-testid="hero-subnav-bar-topic-links"
                $listContainer = array_values(array_filter((array) $listContainers, function ($container) {
                    return !$container->hasAttribute('data-testid') || $container->getAttribute('data-testid') !== 'hero-subnav-bar-topic-links';
                }));

                if (count($listContainer) > 0) {
                    return $listContainer[0];
                }
            }
        }

        return null;
    }

    /**
     * Clean up the string
     *
     * @param string $string
     *
     * @return string
     */
    private static function clean(string $string) : string
    {
        $string = str_replace(chr(194).chr(160), '', html_entity_decode(trim($string), ENT_QUOTES));
        return trim(preg_replace('/\s\s+/', ' ', strip_tags($string)));
    }

    /**
     * Absolutizes an IMDB URL, if it's relative
     *
     * @param string $url
     *
     * @return string
     */
    private static function absolutizeUrl(string $url) : string
    {
        if (strpos($url, '/') === 0) {
            $url = "https://www.imdb.com$url";
        }

        // remove any query string
        $url = explode('?', $url)[0];

        return $url;
    }

    /**
     * Parses a count string (e.g., "1.5M") and returns the integer value
     *
     * @param string $text
     *
     * @return int|null
     */
    private static function parseCount(string $text) : ?int
    {
        // match any [m] or [mln] or [k] and convert them to millions or thousands
        if (preg_match('/([0-9,.]+)\s*(m|mln|k)?/i', $text, $matches)) {
            $votes = (float) str_replace(',', '.', $matches[1]);
            $multiplier = strtolower($matches[2] ?? '');

            if ($multiplier === 'm' || $multiplier === 'mln') {
                return (int) ($votes * 1000000);
            } elseif ($multiplier === 'k') {
                return (int) ($votes * 1000);
            } else {
                return (int) $votes;
            }
        }

        return null;
    }

    /**
     * Parses a date as Y-m-d
     *
     * @param string $date
     *
     * @return string|null
     */
    private static function parseDate(string $date) : ?string
    {
        $patterns = [
            'EEE, MMM dd, yyyy',        // Mon, Sep 22, 2014
            'EEE, dd MMM yyyy',         // lun, 22 sept 2014
            'EEE, dd \'de\' MMM \'de\' yyyy', // seg., 22 de set. de 2014
            'EEE, dd MMM yyyy',         // dom, 21 set 2014
        ];

        foreach ($patterns as $pattern) {
            $formatter = new \IntlDateFormatter(
                'en_US', // Neutral locale, it works for various inputs
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'UTC',
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );

            $timestamp = $formatter->parse($date);
            if ($timestamp !== false) {
                return (new \DateTime())->setTimestamp($timestamp)->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Validate the IMDB identifier
     *
     * @param string $id
     *
     * @return void
     */
    private static function validateId(string $id) : void
    {
        // throw an exception if the identifier is not in the correct format
        if (!preg_match('/^tt[0-9]{7,8}$/', $id)) {
            throw new \Exception("Mfonte\ImdbScraper\Parser:: Invalid IMDB identifier provided: $id. Must be in the form of 'tt1234567'");
        }
    }
}
