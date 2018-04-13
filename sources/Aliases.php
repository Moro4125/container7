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
 * Class Aliases
 */
final class Aliases
{
    /** @var Aliases */
    private $_context;
    private $_map = [];
    private $_invert = [];

    public function setContext(Aliases $context = null)
    {
        $this->_context = $context;
    }

    public function add(string $alias, string $interface)
    {
        if ($this->_context && $real = $this->_context->resolve($interface)) {
            $interface = $real;
        } else {
            $interface = $this->_map[$interface] ?? $interface;
        }

        if (isset($this->_invert[$alias])) {
            foreach ($this->_invert[$alias] as $key) {
                $this->_map[$key] = $interface;
                $this->_invert[$interface][] = $key;
            }

            unset($this->_invert[$alias]);
        }

        if ($name = $this->_map[$alias] ?? null) {
            $this->_invert[$name] = array_diff($this->_invert[$name], [$alias]);

            if (empty($this->_invert[$name])) {
                unset($this->_invert[$name]);
            }
        }

        $this->_map[$alias] = $interface;
        $this->_invert[$interface][] = $alias;
    }

    public function resolve(string $alias): ?string
    {
        return $this->_map[$alias] ?? null;
    }
}