<?php

namespace Mfonte\ImdbScraper\Entities;

use Serializable;
use JsonSerializable;

abstract class Entity implements JsonSerializable, Serializable
{
    /**
     * Create a new entity instance from an associative array.
     *
     * @param array $data
     * @return static
     */
    public static function newFromArray(array $data)
    {
        $instance = new static();
        foreach ($data as $key => $value) {
            $instance->__set($key, $value);
        }
        return $instance;
    }

    /**
     * Get a property of the entity.
     *
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
        // get the child class name that extended this class
        $class = get_called_class();

        // throw an exception if the property does not exist
        if (!property_exists($this, $property)) {
            throw new \Exception("Mfonte\ImdbScraper\Entities\{$class}::__get(): Property '{$property}' does not exist");
        }

        return $this->{$property} ?? null;
    }

    /**
     * Set a property of the entity.
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set(string $property, $value): void
    {
        // get the child class name that extended this class
        $class = get_called_class();

        // throw an exception if the property does not exist
        if (!property_exists($this, $property)) {
            throw new \Exception("Mfonte\ImdbScraper\Entities\{$class}::__set(): Property '{$property}' does not exist");
        }

        $castMap = [
            'actors' => 'Person',
            'similars' => 'Reference',
            'seasons' => 'Season',
            'episodes' => 'Episode',
        ];

        // if the property is a Dataset, cast the value to the appropriate entity
        if (isset($castMap[$property])) {
            $entity = "\\Mfonte\\ImdbScraper\\Entities\\{$castMap[$property]}";
            $dataset = new Dataset;
            foreach ($value as $item) {
                $dataset->put($item['id'], $entity::newFromArray($item));
            }
            $value = $dataset;
        }

        $this->{$property} = $value;
    }

    /**
     * Convert the entity's properties to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Specify data to be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Serialize the entity for conventional PHP serialization.
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->toArray());
    }

    /**
     * Unserialize the data to restore the entity.
     *
     * @param string $data Serialized data.
     */
    public function unserialize($data): void
    {
        $array = unserialize($data);
        foreach ($array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
