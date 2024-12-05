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
     * @var string|null The type of the result (e.g., "feature", "TV mini-series", "TV series")
     */
    public $type = null;

    /**
     * @var string|null The category of the result (e.g., "movie", "tvSeries", "tvMiniSeries")
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

    /**
     * Determine if the current search result is a movie.
     * 
     * @return bool
     */
    public function isMovie(): bool
    {
        return $this->category === 'movie';
    }

    /**
     * Determine if the current search result is a TV Series.
     * 
     * @return bool
     */
    public function isTvSeries(): bool
    {
        return in_array($this->category, ['tvSeries', 'tvMiniSeries']);
    }
}
