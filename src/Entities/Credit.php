<?php
namespace Mfonte\ImdbScraper\Entities;

/**
 * Class Actor
 * Represents an actor.
 */
class Credit extends Entity
{
    /**
     * @var string The role of the Credit (e.g., "actor", "director")
     */
    public $role;

    /**
     * @var string The involvement of the Person in the movie or TV show
     */
    public $involvement;

    /**
     * @var Person The Person associated with the Credit
     */
    public $person;

    public function setPerson(array $person): void
    {
        $this->person = Person::newFromArray($person);
    }
}
