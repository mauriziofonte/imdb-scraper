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
            'credits' => 'Credit'
        ];

        // if the property is a Dataset, cast the value to the appropriate entity
        if (isset($castMap[$property])) {
            $entity = "\\Mfonte\\ImdbScraper\\Entities\\{$castMap[$property]}";
            $dataset = new Dataset;
            foreach ($value as $key => $item) {
                $index = (isset($item['id']) && $item['id']) ? $item['id'] : $key;
                
                // do $item attributes are all arrays? if so, we need to create a Dataset for them
                $itemArraysCount = array_reduce($item, function ($carry, $value) {
                    $add = (is_array($value) && !empty($value)) ? 1 : 0;
                    return $carry + $add;
                }, 0);

                // if all attributes are arrays, we need to create a Dataset for them
                if ($itemArraysCount === count($item)) {
                    $subDataset = new Dataset;
                    foreach ($item as $subKey => $subItem) {
                        $subIndex = (isset($subItem['id']) && $subItem['id']) ? $subItem['id'] : $subKey;
                        $subDataset->put($subIndex, $entity::newFromArray($subItem));
                    }
                    $dataset->put($index, $subDataset);
                } else {
                    $dataset->put($index, $entity::newFromArray($item));
                }
            }
            $value = $dataset;
        }

        // does the class have a setter method for this property?
        $setterMethod = 'set' . ucfirst($property);
        if (method_exists($this, $setterMethod)) {
            $this->{$setterMethod}($value);
            return;
        } else {
            $this->{$property} = $value;
        }
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

    /**
     * Magic method to serialize the entity for PHP 8+ compatibility.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Magic method to unserialize the entity for PHP 8+ compatibility.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
