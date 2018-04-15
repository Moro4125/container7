<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

/**
 * Class Tags
 */
final class Tags
{
    const REGULAR = 'regular';

    /** @var Aliases */
    private $_aliases;
    /** @var Aliases */
    private $_context;
    private $_byTag = [];
    private $_byKey = [];

    public function __construct(Aliases $aliases)
    {
        $this->_aliases = $aliases;
    }

    public function setContext(Aliases $context = null)
    {
        $this->_context = $context;
    }

    public function register(string $tag)
    {
        if (empty($this->_byTag[$tag])) {
            $this->_byTag[$tag] = [];
        }
    }

    public function add(string $tag, string $key, $meta = null)
    {
        assert(is_null($meta) || is_numeric($meta) || is_array($meta));

        if ($this->_context && $real = $this->_context->resolve($key)) {
            $key = $real;
        } else {
            $key = $this->_aliases->resolve($key) ?: $key;
        }

        $meta = is_array($meta) ? $meta : ['priority' => $meta];
        $priority = (float)($meta['priority'] ?? 0.5);
        unset($meta['priority']);

        $this->_byTag[$tag][$key] = $priority;
        $this->_byKey[$key][$tag] = $meta ?: null;
    }

    public function hasKey($key): bool
    {
        return isset($this->_byKey[$key]);
    }

    public function hasTag($tag): bool
    {
        return isset($this->_byTag[$tag]);
    }

    public function keysByTag(string $tag): array
    {
        if (empty($this->_byTag[$tag])) {
            return [];
        }

        uasort($this->_byTag[$tag], function ($a, $b) {
            return (int)((abs($a - $b) < 0.000001) ? null : ceil($b - $a));
        });

        return array_keys($this->_byTag[$tag]);
    }

    public function tagsForKey(string $key): array
    {
        if (empty($this->_byKey[$key])) {
            return [];
        }

        return array_keys($this->_byKey[$key]);
    }

    public function metaByTagAndKey(string $tag, string $key): ?array
    {
        return $this->_byKey[$key][$tag] ?? null;
    }
}