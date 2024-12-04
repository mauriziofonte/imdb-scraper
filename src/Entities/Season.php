<?php
namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Season
 * Represents a season of a TV show.
 */
class Season extends Entity
{
    /**
     * @var string Unique Season ID (e.g., "S01")
     */
    public $id;

    /**
     * @var int The season number
     */
    public $number;

    /**
     * @var Dataset<Episode> The episodes in the season
     */
    public $episodes;
}
