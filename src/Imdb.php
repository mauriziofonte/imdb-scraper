<?php
namespace Mfonte\ImdbScraper;

use Mfonte\ImdbScraper\Exceptions\NoSearchResults;
use Mfonte\ImdbScraper\Exceptions\MultipleSearchResults;
use Mfonte\ImdbScraper\Exceptions\BadMethodCall;
use Mfonte\ImdbScraper\Entities\Title;
use Mfonte\ImdbScraper\Entities\Dataset;
use Mfonte\ImdbScraper\Entities\SearchResult;

/**
* Class Imdb
*
* @package mfonte/imdb-scraper
* @author Maurizio Fonte
*/
class Imdb
{
    /**
     * Options for the class
     *
     * @var array
     */
    private $options;

    /**
     * Cache instance
     *
     * @var Cache
     */
    private $cache = null;

    /**
     * Factory method to create a new instance of the class
     *
     * @param array $options
     * @return Imdb
     */
    public static function new(array $options = []) {
        return new self($options);
    }

    /**
     * Imdb constructor. Direct class instantiation is not allowed (use factory methods!)
     *
     * @param array $options
     */
    private function __construct(array $options = [])
    {
        $this->options = $this->extendOpts($options);

        if ($this->options["cache"]) {
            $this->cache = new Cache;
        }
    }

    /**
     * Gets a Movie data from IMDB, by best effort
     * 
     * @param string $title
     * 
     * @return Title
     */
    public function movie(string $title): Title
    {
        $imdbId = $this->narrow($title, 'movie', true);
        return $this->id($imdbId);
    }

    /**
     * Gets a Movie data from IMDB, strictly narrowing the search to the given year.
     *
     * @param string $title
     * @param int $year
     * 
     * @return Title
     */
    public function movieByYear(string $title, int $year): Title
    {
        $imdbId = $this->narrow($title, 'movie', false, $year);
        return $this->id($imdbId);
    }

    /**
     * Gets a TV Show data from IMDB, by best effort
     * 
     * @param string $title
     * 
     * @return Title
     */
    public function tvSeries(string $title): Title
    {
        $imdbId = $this->narrow($title, 'tvSeries', true);
        return $this->id($imdbId);
    }

    /**
     * Gets a TV Show data from IMDB, strictly narrowing the search to the given year.
     *
     * @param string $title
     * @param int $year
     * 
     * @return Title
     */
    public function tvSeriesByYear(string $title, int $year): Title
    {
        $imdbId = $this->narrow($title, 'tvSeries', false, $year);
        return $this->id($imdbId);
    }

    /**
     * Gets a Movie or TV Show data from IMDB, based on its identifier.
     * Both compatible with titles (search keyword) and film ids in the form of 'tt1234567'.
     *
     * @param string $imdbId - The IMDB ID of the movie or TV show (e.g., 'tt1234567')
     * 
     * @return Title
     */
    public function id(string $imdbId) : Title
    {
        // throw an exception if the IMDB ID is not valid
        if (!preg_match('/^tt\d{7,8}$/', $imdbId)) {
            throw new BadMethodCall("Mfonte\ImdbScraper\Imdb::id() - Invalid IMDB ID: {$imdbId}");
        }

        // early return from cache, if the cache is enabled
        if ($this->cache && $this->cache->has($imdbId)) {
            return $this->cache->get($imdbId);
        }

        // run the parser against this IMDB ID
        $parser = Parser::parse($imdbId, $this->options);
        $title = $parser->toTitle();

        // set the title in cache, if the cache is enabled
        if ($this->cache) {
            //  Add result to the cache
            $this->cache->add($imdbId, $title);
        }

        return $title;
    }

    /**
     * Searches IMDB for films, people and companies
     * 
     * @param string $keyword
     * 
     * @return Dataset<SearchResult>
     */
    public function search(string $search): Dataset
    {
        // fetch the search page in json format
        $dom = new Dom;
        $keyword = urlencode(urldecode($search));
        $page = $dom->raw("https://v3.sg.media-imdb.com/suggestion/x/{$keyword}.json?includeVideos=0", $this->options);

        // try to json-decode the textContent of the page
        $searchData = @json_decode($page, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($searchData) || !array_key_exists('d', $searchData)) {
            return Dataset::new([]);
        }

        return Dataset::new(array_map(function ($item) {
            return SearchResult::newFromArray([
                'id' => (array_key_exists('id', $item) && $item['id']) ? $item['id'] : null,
                'title' => (array_key_exists('l', $item) && $item['l']) ? $item['l'] : null,
                'image' => (array_key_exists('i', $item) && array_key_exists('imageUrl', $item['i']) && $item['i']['imageUrl']) ? $item['i']['imageUrl'] : null,
                'year' => (array_key_exists('y', $item) && $item['y']) ? intval($item['y']) : null,
                'type' => (array_key_exists('q', $item) && $item['q']) ? $item['q'] : null,
                'category' => (array_key_exists('qid', $item) && $item['qid']) ? $item['qid'] : null,
                'starring' => (array_key_exists('s', $item) && $item['s']) ? $item['s'] : null,
                'rank' => (array_key_exists('rank', $item) && $item['rank']) ? $item['rank'] : null,
            ]);
        }, $searchData['d']));
    }

    /**
     * Narrows the search to a specific category (movie or tvSeries), and optionally a year
     * 
     * @param string $keyword
     * @param string $category
     * @param bool $forceFirstResult
     * @param int|null $year
     * 
     * @return string
     */
    private function narrow(string $keyword, string $category, bool $forceFirstResult = false, ?int $year = null) : string {
        if (!in_array($category, ['movie', 'tvSeries'])) {
            throw new BadMethodCall("Mfonte\ImdbScraper\Imdb::narrow() - Invalid category: {$category}");
        }

        $results = $this->search($keyword);

        // throw an exception if no results are found
        if ($results->count() === 0) {
            throw new NoSearchResults($keyword);
        }

        // narrow the results to only the ones that match the category
        $results = $results->filter(function ($result) use ($category) {
            return $result->category === $category;
        });

        // if in $forceFirstResult mode, return the first result
        if ($forceFirstResult) {
            return $results->first()->id;
        }

        // if we've got the year, then, narrow the results to only the ones that match the year, or year -1, or year +1
        if ($year) {
            $results = $results->filter(function ($result) use ($year) {
                return $result->year === $year || $result->year === $year - 1 || $result->year === $year + 1;
            });
        }
        else {
            // calculate the levenshtein distance between the search term and the title of the results
            $distances = [];
            foreach ($results as $result) {
                $distance = levenshtein($keyword, $result->title);
                $distances[$result->id] = $distance;
            }

            // sort the distances: the lower the distance, the better the match
            asort($distances);

            // get the first match key, and first match value
            $firstMatch = array_key_first($distances);

            // calculate the likelihood of the match, starting from 100, removing 5 points for each character of distance
            $likelihood = 100 - ($distances[$firstMatch] * 5);

            // if the likelihood is less than 75, return null (5 chars wrong)
            if ($likelihood < 75) {
                return null;
            }

            // filter the results to only the one that matches the firstMatch
            $results = $results->filter(function ($result) use ($firstMatch) {
                return $result->id === $firstMatch;
            });
        }

        // if no results are found, throw an exception
        if ($results->count() === 0) {
            throw new NoSearchResults($keyword);
        }

        // if we've got more than 1 result, throw an exception
        if ($results->count() > 1) {
            throw new MultipleSearchResults($keyword, $results);
        }

        // return the first and only result
        return $results->first()->id;
    }

    /**
     * Returns default options extended with any user options
     *
     * @param array $options
     * @return array $defaults
     */
    private function extendOpts(array $options = []): array
    {
        //  Default options
        $defaults = [
            'cache'         => false,
            'locale'        => 'en',
            'seasons'       => false,
            'guzzleLogFile' => null
        ];

        //  Merge any user options with the default ones
        foreach ($options as $key => $option) {
            $defaults[$key] = $option;
        }

        //  Return final options array
        return $defaults;
    }
}
