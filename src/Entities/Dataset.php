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
 * A lightweight collection implementation that provides basic collection functionalities,
 * with support for recursive operations on nested Dataset instances.
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
     * @param array|Traversable $items
     * @return Dataset
     */
    public static function new($items = [])
    {
        return new self($items);
    }

    /**
     * Dataset constructor.
     *
     * @param array|Traversable $items
     * @throws InvalidArgumentException
     */
    public function __construct($items = [])
    {
        if (is_array($items)) {
            $this->items = $items;
        } elseif ($items instanceof Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            throw new InvalidArgumentException("Dataset::__construct(): Items must be an array or Traversable");
        }
    }

    /**
     * Runs a callback on each item in the collection, recursively on nested Datasets.
     *
     * @param callable $callback ($value, $key)
     * @return Dataset
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                $value->each($callback);
            } else {
                $callback($value, $key);
            }
        }
        return $this;
    }

    /**
     * Filters the collection using a callback function, recursively on nested Datasets.
     *
     * @param callable|null $callback ($value, $key)
     * @return Dataset
     */
    public function filter($callback = null): Dataset
    {
        $results = [];
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                $filtered = $value->filter($callback);
                if ($filtered->count() > 0) {
                    $results[$key] = $filtered;
                }
            } else {
                if ($callback !== null) {
                    $keep = (bool) $callback($value, $key);
                } else {
                    $keep = (bool) $value;
                }
                if ($keep) {
                    $results[$key] = $value;
                }
            }
        }
        return new self($results);
    }

    /**
     * Removes specified keys from the collection (non-recursive).
     *
     * @param array $keys
     * @return Dataset
     */
    public function except(array $keys): Dataset
    {
        $results = [];
        foreach ($this->items as $k => $v) {
            if (! in_array($k, $keys, true)) {
                $results[$k] = $v;
            }
        }
        return new self($results);
    }

    /**
     * Determines if the collection contains the given value, optionally by key, recursively.
     *
     * @param mixed       $value
     * @param string|null $key
     * @return bool
     */
    public function contains($value, $key = null): bool
    {
        foreach ($this->items as $item) {
            if ($item instanceof self) {
                if ($item->contains($value, $key)) {
                    return true;
                }
            } elseif ($key !== null) {
                if (is_array($item) && array_key_exists($key, $item) && $item[$key] === $value) {
                    return true;
                }
                if (is_object($item) && isset($item->$key) && $item->$key === $value) {
                    return true;
                }
            } else {
                if ($item === $value) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a slice of the collection.
     *
     * @param int      $offset
     * @param int|null $length
     * @return Dataset
     */
    public function slice($offset, $length = null): Dataset
    {
        $sliced = array_slice($this->items, $offset, $length, true);
        return new self($sliced);
    }

    /**
     * Sorts the collection using callbacks (non-recursive).
     *
     * @param array $callbacks
     * @param int   $options
     * @return Dataset
     */
    public function sortBy(array $callbacks, $options = SORT_REGULAR): Dataset
    {
        $items = $this->items;
        usort($items, function ($a, $b) use ($callbacks, $options) {
            foreach ($callbacks as $cb) {
                $va = $cb($a);
                $vb = $cb($b);
                if ($options === SORT_NUMERIC) {
                    $res = $va - $vb;
                } elseif ($options === SORT_STRING) {
                    $res = strcmp((string)$va, (string)$vb);
                } else {
                    $res = $va <=> $vb;
                }
                if ($res !== 0) {
                    return $res;
                }
            }
            return 0;
        });
        return new self($items);
    }

    /**
     * Sorts the collection ascending (non-recursive).
     *
     * @param int $options
     * @return Dataset
     */
    public function sortAsc($options = SORT_REGULAR): Dataset
    {
        $items = $this->items;
        asort($items, $options);
        return new self($items);
    }

    /**
     * Sorts the collection descending (non-recursive).
     *
     * @param int $options
     * @return Dataset
     */
    public function sortDesc($options = SORT_REGULAR): Dataset
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
     * Returns the first item matching the given key/value, recursively.
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed|null
     */
    public function firstWhere($key, $value)
    {
        foreach ($this->items as $item) {
            if ($item instanceof self) {
                $found = $item->firstWhere($key, $value);
                if ($found !== null) {
                    return $found;
                }
            } elseif (is_array($item) && array_key_exists($key, $item) && $item[$key] === $value) {
                return $item;
            } elseif (is_object($item) && isset($item->$key) && $item->$key === $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Filters the collection by a specific property value, recursively.
     *
     * @param string $property
     * @param mixed  $value
     * @return Dataset
     */
    public function where($property, $value): Dataset
    {
        return $this->filter(function ($item) use ($property, $value) {
            if (is_array($item) && array_key_exists($property, $item)) {
                return $item[$property] === $value;
            }
            if (is_object($item) && isset($item->$property)) {
                return $item->$property === $value;
            }
            return false;
        });
    }

    /**
     * Applies a callback to all items in the collection, recursively.
     *
     * @param callable $callback ($value, $key)
     * @return Dataset
     */
    public function map(callable $callback): Dataset
    {
        $mapped = [];
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                $mapped[$key] = $value->map($callback);
            } else {
                $mapped[$key] = $callback($value, $key);
            }
        }
        return new self($mapped);
    }

    /**
     * Reduces the collection to a single value using a callback function.
     *
     * @param callable   $callback ($carry, $item, $key)
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        $carry = $initial;
        foreach ($this->items as $key => $value) {
            $carry = $callback($carry, $value, $key);
        }
        return $carry;
    }

    /**
     * Checks if the given key exists in the collection (non-recursive).
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Adds or updates an item in the collection with the given key.
     *
     * @param mixed $key
     * @param mixed $value
     * @return Dataset
     */
    public function put($key, $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * Gets an item from the collection by key, or returns the default value.
     *
     * @param mixed      $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }
        return $default;
    }

    /**
     * Counts the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Gets all the keys of the collection items.
     *
     * @param bool $unique - whether to return only unique keys
     * @return array
     */
    public function keys($unique = false): array
    {
        $keys = array_keys($this->items);
        if ($unique) {
            return array_unique($keys);
        }
        return $keys;
    }

    /**
     * Gets all the values of the collection items.
     *
     * @param bool $unique - whether to return only unique values
     * @return array
     */
    public function values($unique = false): array
    {
        $values = array_values($this->items);
        if ($unique) {
            return array_unique($values);
        }
        return $values;
    }

    /**
     * Plucks the values of a given key from the collection items, recursively.
     *
     * @param string $key
     * @return Dataset
     */
    public function pluck($key): Dataset
    {
        $results = [];
        foreach ($this->items as $value) {
            if ($value instanceof self) {
                $nested = $value->pluck($key);
                foreach ($nested->toArray() as $v) {
                    $results[] = $v;
                }
            } elseif (is_array($value) && array_key_exists($key, $value)) {
                $results[] = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $results[] = $value->$key;
            }
        }
        return new self($results);
    }

    /**
     * Plucks unique values of a given key from the collection items, recursively.
     *
     * @param string $key
     * @return Dataset
     */
    public function pluckUnique($key): Dataset
    {
        $results = [];
        foreach ($this->items as $value) {
            if ($value instanceof self) {
                $nested = $value->pluckUnique($key);
                foreach ($nested->toArray() as $v) {
                    if (! in_array($v, $results, true)) {
                        $results[] = $v;
                    }
                }
            } elseif (is_array($value) && array_key_exists($key, $value)) {
                $v = $value[$key];
                if (! in_array($v, $results, true)) {
                    $results[] = $v;
                }
            } elseif (is_object($value) && isset($value->$key)) {
                $v = $value->$key;
                if (! in_array($v, $results, true)) {
                    $results[] = $v;
                }
            }
        }
        return new self($results);
    }

    /**
     * Converts the collection to a string (flattened, recursively).
     *
     * @return string
     */
    public function toString(): string
    {
        $string = '';
        foreach ($this->items as $value) {
            if ($value instanceof self) {
                $string .= $value->toString();
            } else {
                if (is_array($value)) {
                    $filtered = array_filter($value, 'is_scalar');
                    $string .= implode(', ', array_values($filtered)) . ', ';
                } elseif (is_scalar($value)) {
                    $string .= (string) $value . ', ';
                } else {
                    $string .= gettype($value) . ', ';
                }
            }
        }
        return rtrim($string, ', ');
    }

    /**
     * Converts the collection to an array (recursively).
     *
     * @return array
     */
    public function toArray(): array
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
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Flattens a multi-dimensional array up to the given depth.
     *
     * @param array|Traversable $array
     * @param int               $depth
     * @return array
     */
    public static function flatten($array, $depth = INF): array
    {
        $result = [];
        $stack  = [[$array, $depth]];
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
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Converts the collection to a value suitable for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * Checks if an item exists at the given offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
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
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->put($offset, $value);
        }
    }

    /**
     * Unsets the item at the given offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
