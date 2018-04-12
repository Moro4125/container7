<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

use ArrayAccess;

/**
 * Class Parameters
 */
final class Parameters implements ArrayAccess
{
    private $_parameters;

    public function __construct(array $parameters = null)
    {
        $this->_parameters = (array)$parameters;
    }

    static function fromFile(string $file, string $folder = null): Parameters
    {
        $file = str_replace('\\', '/', $file);

        if ($folder !== null && strncmp($file, '/', 1) !== 0 && substr($file, 1, 1) != ':') {
            $file = $folder . '/' . $file;
        }

        if (!$realPath = realpath($file)) {
            throw new \RuntimeException('File not found: ' . $file);
        }

        $parameters = json_decode(file_get_contents($realPath), true);
        $parameters['@files'] = [$realPath];
        $folder = str_replace('\\', '/', dirname($realPath));

        foreach ((array)($parameters['@extends'] ?? []) as $filePath) {
            $extends = self::fromFile($filePath, $folder)->_parameters;
            $parameters = self::merge($extends, $parameters);
        }

        unset($parameters['@extends']);

        return new Parameters($parameters);
    }

    public function all(): array
    {
        $all = [];

        foreach (array_keys($this->_parameters) as $key) {
            $all[$key] = $this->get($key);
        }

        return $all;
    }

    public function raw(string $key = null)
    {
        return $key === null ? $this->_parameters : $this->_parameters[$key];
    }

    public function append(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->add($key, $value);
        }
    }

    public function add(string $key, $value)
    {
        while ($pos = strrpos($key, '/')) {
            $index = substr($key, $pos + 1);
            $key = substr($key, 0, $pos);
            $value = [$index => $value];
        }

        $this->_parameters[$key] = (isset($this->_parameters[$key]) && is_array($this->_parameters[$key]) && is_array($value)) ? self::merge($this->_parameters[$key],
            $value) : $value;
    }

    public function set(string $key, $value)
    {
        $cursor = &$this->_parameters;

        while ($pos = strpos($key, '/')) {
            $index = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);

            if (!array_key_exists($index, $cursor) || !is_array($cursor[$index])) {
                $key = $index . '/' . $key;
                break;
            }

            $cursor = &$cursor[$index];
        }

        while ($pos = strrpos($key, '/')) {
            $index = substr($key, $pos + 1);
            $key = substr($key, 0, $pos);
            $value = [$index => $value];
        }

        $cursor[$key] = $value;
    }

    public function has(string $key)
    {
        $value = $this->_parameters;

        while (($pos = strpos($key, '/')) && is_array($value)) {
            $index = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);

            if (!array_key_exists($index, $value)) {
                return false;
            }

            $value = $value[$index];
        }

        return is_array($value) && array_key_exists($key, $value);
    }

    public function get(string $key, $default = null)
    {
        $value = $this->_parameters;

        while (($pos = strpos($key, '/')) && is_array($value)) {
            $index = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);

            if (!array_key_exists($index, $value)) {
                return $default;
            }

            $value = $value[$index];
        }

        if (is_array($value) && array_key_exists($key, $value)) {
            return $this->resolve($value[$key]);
        }

        return $default;
    }

    public function del(string $key)
    {
        unset($this->_parameters[$key]);
    }

    public function resolve($value)
    {
        if (is_array($value)) {
            $list = [];

            foreach ($value as $index => $item) {
                $list[$index] = $this->resolve($item);
            }

            return $list;
        }

        if (is_string($value) && is_int($index = strpos($value, '%'))) {
            if ($index === 0 && substr($value, -1) === '%' && $this->has($key = substr($value, 1, -1))) {
                return $this->get($key);
            }

            $value = preg_replace_callback('{%(.+?)%}', function ($match) {
                $value = $this->has($match[1]) ? $this->get($match[1]) : $match[0];

                return is_scalar($value) ? (string)$value : '[' . gettype($value) . ']';
            }, $value);
        }

        return $value;
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetUnset($offset)
    {
        $this->del($offset);
    }

    static function merge($a, $b): array
    {
        foreach ($b as $k => $v) {
            if ($v === null) {
                unset($a[$k]);
                unset($b[$k]);
                continue;
            }

            if (is_integer($k)) {
                continue;
            }

            if (isset($a[$k])) {
                if (is_array($v) && is_array($a[$k])) {
                    $a[$k] = self::merge($a[$k], $v);
                } else {
                    $a[$k] = $v;
                }

                unset($b[$k]);
            } else {
                unset($a[$k]);
            }
        }

        return array_merge($b, $a);
    }
}