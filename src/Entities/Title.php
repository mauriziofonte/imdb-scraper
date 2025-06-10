<?php

namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Title
 * Represents detailed information about a title.
 */
class Title extends Entity
{
    /**
     * @var string Unique IMDb ID (e.g., "tt1234567")
     */
    public $id;

    /**
     * @var bool Indicates if this is a TV show
     */
    public $isTvSeries = false;

    /**
     * @var string The URL of the IMDb page
     */
    public $link;

    /**
     * @var string The title of the movie or TV show
     */
    public $title;

    /**
     * @var string The original title of the movie or TV show
     */
    public $originalTitle;

    /**
     * @var int|null The release year
     */
    public $year = null;

    /**
     * @var string|null The length of the title (e.g., "2h 15m")
     */
    public $length = null;

    /**
     * @var float|null The IMDb rating
     */
    public $rating = null;

    /**
     * @var int|null The number of votes
     */
    public $ratingVotes = null;

    /**
     * @var int|null the Popularity Score
     */
    public $popularityScore = null;

    /**
     * @var int|null The MetaScore
     */
    public $metaScore = null;

    /**
     * @var array List of genres
     */
    public $genres = [];

    /**
     * @var string|null The poster URL
     */
    public $posterUrl = null;

    /**
     * @var string|null The trailer URL
     */
    public $trailerUrl = null;

    /**
     * @var string|null The plot or description
     */
    public $plot = null;

    /**
     * @var Dataset List of Actors
     */
    public $actors = null;

    /**
     * @var Dataset List of Similars
     */
    public $similars = null;

    /**
     * @var array List of season references e.g. [1, 2, 3, 4] for a 4-season TV show
     */
    public $seasonRefs = [];

    /**
     * @var Dataset List of seasons
     */
    public $seasons = [];

    /**
     * @var Dataset List of credits
     */
    public $credits = [];

    /**
     * @var array Raw metadata
     */
    public $metadata = [];
}
