<?php
namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Actor
 * Represents an actor.
 */
class Person extends Entity
{
    public const TYPE_ACTOR = 'actor';
    public const TYPE_DIRECTOR = 'director';
    public const TYPE_WRITER = 'writer';
    public const TYPE_PRODUCER = 'producer';

    /**
     * @var string The type of the Person (e.g., "actor", "director")
     */
    public $type;

    /**
     * @var string Unique IMDb ID (e.g., "nm0000226")
     */
    public $id;

    /**
     * @var string The name of the Person
     */
    public $name;

    /**
     * @var string The URL of the IMDb page
     */
    public $link;

    /**
     * @var string The character played by the Person
     */
    public $character;

    /**
     * @var string The URL of the Person's image
     */
    public $image;
}
