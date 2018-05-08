<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

use Countable;
use Iterator;
use Moro\Container7\Exception\CollectionBrokenException;

/**
 * Class Collection
 */
class Collection implements Iterator, Countable
{
    private $_container;
    private $_interface;
    private $_collection;
    private $_arguments;

    public function __construct(Container $container, string $interface = null)
    {
        $this->_container = $container;
        $this->_interface = $interface;
        $this->_collection = [];
    }

    public function for(...$arguments): Collection
    {
        $this->_arguments = $arguments ?: null;
        return $this;
    }

    public function count(): int
    {
        return count($this->_collection);
    }

    public function append(array $items): Collection
    {
        foreach ($items as $item) {
            $this->add($item);
        }

        return $this;
    }

    public function exclude(string $tagOrInterface): Collection
    {
        if ($this->_container->hasCollection($tagOrInterface)) {
            $collection = $this->_container->getCollection($tagOrInterface);
            $collection->_collection = array_values(array_diff($this->_collection, $collection->_collection));
        } else {
            $collection = clone $this;
        }

        return $collection;
    }

    public function merge(string $tagOrInterface): Collection
    {
        if ($this->_container->hasCollection($tagOrInterface)) {
            $collection = $this->_container->getCollection($tagOrInterface);
            $collection->_collection = array_unique(array_merge($this->_collection, $collection->_collection));
        } else {
            $collection = clone $this;
        }

        return $collection;
    }

    public function with(string $tagOrInterface): Collection
    {
        if ($this->_container->hasCollection($tagOrInterface)) {
            $collection = $this->_container->getCollection($tagOrInterface);
            $collection->_collection = array_values(array_intersect($this->_collection, $collection->_collection));
        } else {
            $collection = clone $this;
            $collection->_collection = [];
        }

        return $collection;
    }

    public function add($providerOrAlias, string $method = null)
    {
        $class = is_object($providerOrAlias)
            ? ($providerOrAlias instanceof Definition
                ? $providerOrAlias->getId()
                : get_class($providerOrAlias)
            ) : (string)$providerOrAlias;
        $this->_collection[] = $method ? $class . '::' . $method : $class;
    }

    public function keys(): array
    {
        return $this->_collection;
    }

    public function current()
    {
        if (false === $alias = current($this->_collection)) {
            return null;
        }

        $value = $this->_arguments
            ? call_user_func_array([$this->_container, 'get'], array_merge([$alias], $this->_arguments))
            : $this->_container->get($alias);

        if ($this->_interface && !is_object($value)) {
            throw new CollectionBrokenException(CollectionBrokenException::MSG_1);
        }

        if ($this->_interface && !$value instanceof $this->_interface) {
            $message = sprintf(CollectionBrokenException::MSG_2, $this->_interface, get_class($value));
            throw new CollectionBrokenException($message);
        }

        return $value;
    }

    public function next()
    {
        next($this->_collection);
    }

    public function key()
    {
        return current($this->_collection) ?: null;
    }

    public function valid()
    {
        return $this->isEmpty() ? false : (key($this->_collection) !== null);
    }

    public function isEmpty(): bool
    {
        return empty($this->_collection);
    }

    public function rewind()
    {
        reset($this->_collection);
    }

    public function asArray(): array
    {
        return iterator_to_array($this);
    }
}