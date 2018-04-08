<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Helper;

use Psr\Container\ContainerInterface;

/**
 * Trait LazyServiceTrait
 *
 * @see \Moro\Container7\Helper\Decorator\DecoratorInterface
 */
trait LazyServiceTrait
{
    protected $_container;
    protected $_service;
    protected $_instance;

    public function __construct(ContainerInterface $container, string $service)
    {
        $this->_container = $container;
        $this->_service = $service;
    }

    public function __isset($name)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        return isset($instance->{$name});
    }

    public function __get($name)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        return $instance->{$name};
    }

    public function __set($name, $value)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        $instance->{$name} = $value;
    }

    public function __unset($name)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        unset($instance->{$name});
    }

    public function __call($name, $arguments)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        return $instance->{$name}(...$arguments);
    }

    public function __clone()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $instance = $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
        $this->_instance = clone $instance;
    }

    public function getOriginalInstance()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->_instance ?: $this->_instance = $this->_container->get($this->_service);
    }
}