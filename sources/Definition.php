<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

use Moro\Container7\Exception\BadMethodDefinitionException;

/**
 * Class Definition
 */
class Definition
{
    private $_id;
    private $_provider;
    private $_singletons;
    private $_factories;
    private $_tuners;

    public function __construct($provider)
    {
        assert(is_object($provider));

        $this->_id = get_class($provider);
        $this->_provider = $provider;
        $this->_singletons = [];
        $this->_factories = [];
        $this->_tuners = [];
    }

    static public function fromObject($provider): Definition
    {
        $definition = new Definition($provider);
        self::_configureAutomatically($definition);
        return $definition;
    }

    public function init()
    {
    }

    final public function getId(): string
    {
        return $this->_id;
    }

    final protected function setId(string $id)
    {
        $this->_id = $id;
    }

    final public function getProvider()
    {
        return $this->_provider;
    }

    final public function addSingleton(string $interface, string $method, array $args = null, bool $final = null)
    {
        assert($interface && $method);
        $this->_singletons[$method] = [$interface, $method, (bool)$final, $args];
        return $this;
    }

    final public function addFactory(string $interface, string $method, array $args = null, bool $final = null)
    {
        assert($interface && $method);
        $this->_factories[$method] = [$interface, $method, (bool)$final, $args];
        return $this;
    }

    final public function addTuner(string $interface, string $method, array $args = null, string $replace = null)
    {
        assert($interface && ($method || $replace));
        $this->_tuners[$method] = [$interface, $method, $replace, $args];
        return $this;
    }

    final public function getSingletons(): array
    {
        return $this->_singletons;
    }

    final public function getFactories(): array
    {
        return $this->_factories;
    }

    final public function getTuners(): array
    {
        return $this->_tuners;
    }

    final public function clear()
    {
        $this->_singletons = [];
        $this->_factories = [];
        $this->_tuners = [];
    }

    static protected function _configureAutomatically(Definition $definition)
    {
        $reflection = new \ReflectionObject($definition->getProvider());

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            $arguments = self::_getMethodArguments($method);
            $interface = self::_getMethodReturnInterface($method);

            if ($factory = self::_checkServiceFactory($method)) {
                if ($factory === 1) {
                    $definition->addSingleton($interface, $method->getName(), $arguments, $method->isFinal());
                } else {
                    $definition->addFactory($interface, $method->getName(), $arguments, $method->isFinal());
                }

                continue;
            }

            if ($target = self::_checkServiceExtend($method)) {
                if ($target === __CLASS__) {
                    $callbacks[] = [$definition->getProvider(), $method->getName()];
                } else {
                    $definition->addTuner($target, $method->getName(), array_slice($arguments, 1), $interface);
                }

                continue;
            }

            $message = sprintf(BadMethodDefinitionException::MSG, $reflection->getName(), $method->getName());
            throw new BadMethodDefinitionException($message);
        }

        if (isset($callbacks)) {
            foreach ($callbacks as $callback) {
                $callback($definition);
            }
        }

        return $definition;
    }

    static private function _getMethodArguments(\ReflectionMethod $method): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isVariadic() || !$parameter->getClass()) {
                break;
            }

            $arguments[] = $parameter->getClass()->getName();
        }

        return $arguments;
    }

    static private function _getMethodReturnInterface(\ReflectionMethod $method): ?string
    {
        if (!$method->hasReturnType() || !$type = (string)$method->getReturnType()) {
            return null;
        }

        if (false === strpos($type, '\\') && !class_exists($type) && !interface_exists($type)) {
            return null;
        }

        return $type;
    }

    static private function _checkServiceFactory(\ReflectionMethod $method): int
    {
        if (($type = $method->getReturnType()) && !$type->allowsNull() && !$type->isBuiltin()) {
            $parameters = $method->getParameters();

            foreach ($parameters as $index => $parameter) {
                if ($parameter->isVariadic() && $index == count($parameters) - 1) {
                    return 2;
                }

                if ((!$type = $parameter->getType()) || $type->allowsNull() || $type->isBuiltin()) {
                    return 0;
                }
            }

            return 1;
        }

        return 0;
    }

    static private function _checkServiceExtend(\ReflectionMethod $method): ?string
    {
        if ((!$type = $method->getReturnType()) || $type->allowsNull() && !$type->isBuiltin()) {
            $parameters = $method->getParameters();

            foreach ($parameters as $parameter) {
                if ((!$type = $parameter->getType()) || $type->allowsNull() || $type->isBuiltin()) {
                    return null;
                }
            }

            if ($parameters && $class = $parameters[0]->getClass()) {
                return $class->getName();
            }
        }

        return null;
    }
}