<?php

namespace Mfonte\ImdbScraper\Entities;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use InvalidArgumentException;

/**
 * Class Dataset
 *
 * A lightweight collection implementation that provides basic collection functionalities.
 */
class Dataset implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The collection items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Static constructor for creating a new instance of the collection.
     *
     * @param array|\Traversable $items
     * @return Dataset
     */
    public static function new($items = []) : Dataset
    {
        return new self($items);
    }

    /**
     * Dataset constructor.
     *
     * @param array|\Traversable $items
     * @throws InvalidArgumentException
     */
    public function __construct($items = [])
    {
        if (is_array($items)) {
            $this->items = $items;
        } elseif ($items instanceof Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            throw new InvalidArgumentException("Mfonte\ImdbScraper\Entities\Dataset::__construct(): Items must be an array or Traversable");
        }
    }

    /**
     * Runs a callback on each item in the collection.
     *
     * @param callable $callback ($value, $key)
     *
     * @return $this
     */
    public function each(callable $callback) : self
    {
        foreach ($this->items as $key => $value) {
            $callback($value, $key);
        }
        return $this;
    }

    /**
     * Filters the collection using a callback function.
     *
     * @param callable|null $callback ($value, $key)
     * @return Dataset
     */
    public function filter($callback = null) : Dataset
    {
        $items = array_filter(
            $this->items,
            $callback !== null ? $callback : function ($value) {
                return (bool)$value;
            },
            ARRAY_FILTER_USE_BOTH
        );
        return new self($items);
    }

    /**
     * Removes specified keys from the collection.
     *
     * @param array $keys
     * @return Dataset
     */
    public function except(array $keys) : Dataset
    {
        $items = array_filter($this->items, function ($value, $key) use ($keys) {
            return !in_array($key, $keys, true);
        }, ARRAY_FILTER_USE_BOTH);

        return new self($items);
    }

    /**
     * Determines if the collection contains the given value.
     *
     * @param mixed $value
     * @param string|null $key
     * @return bool
     */
    public function contains($value, $key = null) : bool
    {
        foreach ($this->items as $itemKey => $item) {
            if ($key !== null) {
                if ($item instanceof self) {
                    if ($item->contains($value, $key)) {
                        return true;
                    }
                } elseif (is_array($item) && array_key_exists($key, $item) && $item[$key] === $value) {
                    return true;
                } elseif (is_object($item) && isset($item->$key) && $item->$key === $value) {
                    return true;
                }
            } elseif ($item === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a slice of the collection, starting at the given index and up to the specified length.
     *
     * @param int $offset
     * @param int|null $length
     * @return Dataset
     */
    public function slice($offset, $length = null) : Dataset
    {
        $sliced = array_slice($this->items, $offset, $length, true);
        return new self($sliced);
    }

    /**
     * Sorts the collection using the provided callbacks and options.
     *
     * @param array $callbacks
     * @param int $options
     * @return Dataset
     */
    public function sortBy(array $callbacks, $options = SORT_REGULAR) : Dataset
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($callbacks, $options) {
            foreach ($callbacks as $callback) {
                $valueA = $callback($a);
                $valueB = $callback($b);
                if ($options === SORT_NUMERIC) {
                    $result = $valueA - $valueB;
                } elseif ($options === SORT_STRING) {
                    $result = strcmp((string)$valueA, (string)$valueB);
                } else {
                    $result = $valueA <=> $valueB;
                }
                if ($result !== 0) {
                    return $result;
                }
            }
            return 0;
        });

        return new self($items);
    }

    /**
     * Sorts the collection in ascending order.
     *
     * @param int $options
     * @return Dataset
     */
    public function sortAsc($options = SORT_REGULAR) : Dataset
    {
        $items = $this->items;
        asort($items, $options);
        return new self($items);
    }

    /**
     * Sorts the collection in descending order.
     *
     * @param int $options
     * @return Dataset
     */
    public function sortDesc($options = SORT_REGULAR) : Dataset
    {
        $items = $this->items;
        arsort($items, $options);
        return new self($items);
    }

    /**
     * Returns the first item in the collection.
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->items);
    }

    /**
     * Returns the first item in the collection where the given key matches the given value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function firstWhere($key, $value)
    {
        foreach ($this->items as $idx => $item) {
            if ($item instanceof self) {
                $found = $item->firstWhere($key, $value);
                if ($found !== null) {
                    return $found;
                }
            } elseif (is_array($item) && array_key_exists($key, $item) && $item[$key] === $value) {
                return $item;
            } elseif (is_object($item) && isset($item->{$key}) && $item->{$key} === $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Applies a callback to all items in the collection.
     *
     * @param callable $callback ($value, $key)
     * @return Dataset
     */
    public function map(callable $callback) : Dataset
    {
        $mapped = [];
        foreach ($this->items as $key => $value) {
            $mapped[$key] = $callback($value, $key);
        }
        return new self($mapped);
    }

    /**
     * Reduces the collection to a single value using a callback function.
     *
     * @param callable $callback ($carry, $item, $key)
     * @param mixed|null $initial
     * @return Dataset
     */
    public function reduce(callable $callback, $initial = null) : Dataset
    {
        $carry = $initial;
        foreach ($this->items as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }
        return $carry;
    }

    /**
     * Checks if the given key exists in the collection.
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key) : bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Adds or updates an item in the collection with the given key.
     *
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function put($key, $value) : self
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * Gets an item from the collection by key, or returns the default value.
     *
     * @param mixed $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    /**
     * Counts the number of items in the collection.
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->items);
    }

    /**
     * Gets all the keys of the collection items.
     *
     * @param bool $unique - whether to return only unique keys
     *
     * @return array
     */
    public function keys($unique = false) : array
    {
        $keys = array_keys($this->items);
        return $unique ? array_unique($keys) : $keys;
    }

    /**
     * Gets all the values of the collection items.
     *
     * @param bool $unique - whether to return only unique values
     *
     * @return array
     */
    public function values($unique = false) : array
    {
        $values = array_values($this->items);
        return $unique ? array_unique($values) : $values;
    }

    /**
     * Converts the collection to a string (only values, flattened)
     *
     * @return string
     */
    public function toString() : string
    {
        $string = '';
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                $string .= $value->toString();
            } else {
                $stringValue = null;

                if (is_array($value)) {
                    $stringValue = implode(', ', array_values(array_filter($value, 'is_scalar')));
                } elseif (is_scalar($value)) {
                    $stringValue = (string)$value;
                } else {
                    $stringValue = gettype($value);
                }

                $string .= $stringValue . ', ';
            }
        }
        return rtrim($string, ', ');
    }


    /**
     * Converts the collection to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $array = [];
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Converts the collection to a JSON string.
     *
     * @return string
     */
    public function toJson() : string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Flattens a multi-dimensional array up to the given depth.
     *
     * @param array|\Traversable $array
     * @param int $depth
     * @return array
     */
    public static function flatten($array, $depth = INF) : array
    {
        $result = [];
        $stack = [[$array, $depth]];

        while ($stack) {
            list($current, $currentDepth) = array_pop($stack);
            foreach ($current as $item) {
                if (($item instanceof Traversable || is_array($item)) && $currentDepth > 1) {
                    $stack[] = [$item, $currentDepth - 1];
                } else {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * Gets an iterator for the items in the collection.
     *
     * @return \Traversable
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Converts the collection to a value suitable for JSON serialization.
     *
     * @return mixed
     */
    public function jsonSerialize() : mixed
    {
        return $this->toArray();
    }

    /**
     * Checks if an item exists at the given offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return $this->has($offset);
    }

    /**
     * Gets the item at the given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) : mixed
    {
        return $this->get($offset);
    }

    /**
     * Sets the item at the given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->put($offset, $value);
        }
    }

    /**
     * Unsets the item at the given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        unset($this->items[$offset]);
    }
}
