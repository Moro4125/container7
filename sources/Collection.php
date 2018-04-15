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
use RuntimeException;

/**
 * Class Collection
 */
class Collection implements Iterator, Countable
{
    private $_container;
    private $_interface;
    private $_collection;

    public function __construct(Container $container, string $interface = null)
    {
        $this->_container = $container;
        $this->_interface = $interface;
        $this->_collection = [];
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
        $collection = $this->_container->getCollection($tagOrInterface);
        $collection->_collection = array_values(array_diff($this->_collection, $collection->_collection));

        return $collection;
    }

    public function merge(string $tagOrInterface): Collection
    {
        $collection = $this->_container->getCollection($tagOrInterface);
        $collection->_collection = array_unique(array_merge($this->_collection, $collection->_collection));

        return $collection;
    }

    public function with(string $tagOrInterface): Collection
    {
        $collection = $this->_container->getCollection($tagOrInterface);
        $collection->_collection = array_values(array_intersect($this->_collection, $collection->_collection));

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

    public function current()
    {
        if (false === $alias = current($this->_collection)) {
            return null;
        }

        $value = $this->_container->get($alias);

        if ($this->_interface && !is_object($value)) {
            throw new RuntimeException('Collection can contains only objects.');
        }

        if ($this->_interface && !$value instanceof $this->_interface) {
            $message = 'Collection can contains instance of "%1$s". But "%2$s" found.';
            throw new RuntimeException(sprintf($message, $this->_interface, get_class($value)));
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