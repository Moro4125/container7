<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Helper;

use Moro\Container7\Aliases;
use Moro\Container7\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class ServiceLocator
 */
class ServiceLocator implements ContainerInterface
{
    protected $_container;
    protected $_aliases;

    public function __construct(ContainerInterface $container, array $mapping)
    {
        $this->_container = $container;
        $this->_aliases = new Aliases();

        foreach ($mapping as $alias => $key) {
            if ($container->has($key)) {
                $this->_aliases->add(is_string($alias) ? $alias : $key, $key);
            }
        }
    }

    public function has($id)
    {
        return null !== $this->_aliases->resolve($id);
    }

    public function get($id)
    {
        if (!$key = $this->_aliases->resolve($id)) {
            throw new ServiceNotFoundException(sprintf(ServiceNotFoundException::MSG, $id));
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->_container->get($key);
    }
}