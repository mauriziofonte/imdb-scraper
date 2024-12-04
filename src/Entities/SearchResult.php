<?php

namespace Mfonte\ImdbScraper\Entities;

/**
 * Class SearchResult
 * Represents a search result item.
 */
class SearchResult extends Entity
{
    /**
     * @var string Unique IMDb ID (e.g., "tt1234567")
     */
    public $id;

    /**
     * @var string The title of the search result
     */
    public $title;

    /**
     * @var string|null The URL of the result image
     */
    public $image = null;

    /**
     * @var int|null The release year
     */
    public $year = null;

    /**
     * @var string|null The type of the result (e.g., "movie", "tvShow")
     */
    public $type = null;

    /**
     * @var string|null The category of the result (e.g., "feature film")
     */
    public $category = null;

    /**
     * @var string|null Starring information
     */
    public $starring = null;

    /**
     * @var int|null The rank of the result
     */
    public $rank = null;
}
