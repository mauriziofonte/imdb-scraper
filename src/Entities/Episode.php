<?php

namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Episode
 * Represents an episode of a TV show.
 */
class Episode extends Entity
{
    /**
     * @var string Unique IMDb ID (e.g., "tt1234567")
     */
    public $id;

    /**
     * @var string URL of the episode's poster
     */
    public $img;

    /**
     * @var string Title of the episode
     */
    public $title;

    /**
     * @var string URL of the episode's IMDb page
     */
    public $link;

    /**
     * @var int Season number
     */
    public $seasonNumber;

    /**
     * @var int Episode number
     */
    public $episodeNumber;

    /**
     * @var string Air date
     */
    public $airDate;

    /**
     * @var string Plot summary
     */
    public $plot;

    /**
     * @var float Rating
     */
    public $rating;

    /**
     * @var int Number of votes
     */
    public $ratingVotes;
}
