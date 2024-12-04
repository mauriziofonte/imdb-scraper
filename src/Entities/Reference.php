<?php
namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Reference
 * Represents a reference to an entity.
 */
class Reference extends Entity
{
    /**
     * @var string Unique IMDb ID (e.g., "tt1234567")
     */
    public $id;

    /**
     * @var string The title of the reference
     */
    public $title;

    /**
     * @var string The URL of the IMDb page
     */
    public $link;
}