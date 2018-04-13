<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

use Moro\Container7\Exception\BadInstanceException;
use Moro\Container7\Exception\CollectionNotFoundException;
use Moro\Container7\Exception\DuplicateProviderException;
use Moro\Container7\Exception\FactoryException;
use Moro\Container7\Exception\FinalException;
use Moro\Container7\Exception\MixedException;
use Moro\Container7\Exception\RecursionDetectedException;
use Moro\Container7\Exception\ServiceNotFoundException;
use Moro\Container7\Exception\TooLateForExtendException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 */
class Container implements ContainerInterface, \Serializable
{
    private static $_lastInstances = [];
    private $_providers = [];
    private $_mapping = [];
    private $_factories = [];
    private $_collections = [];
    private $_extends = [];

    public function __construct(Parameters $configuration = null)
    {
        $this->_mapping[__CLASS__] = count($this->_factories);
        $this->_factories[] = null;
        $this->addProvider(new Provider());

        if ($configuration) {
            $this->addProvider(Provider::fromParameters('configuration', $configuration));
        }
    }

    public function hasProvider($provider): bool
    {
        return isset($this->_providers[is_object($provider) ? get_class($provider) : (string)$provider]);
    }

    public function addProvider($provider)
    {
        is_string($provider) && $provider = new $provider();
        $definition = $provider instanceof Definition ? $provider : Definition::fromObject($provider);

        $definition->init();

        if (isset($this->_providers[$id = $definition->getId()])) {
            throw new DuplicateProviderException(sprintf(DuplicateProviderException::MSG, $id));
        }

        $instance = $definition->getProvider();
        $this->_providers[$id] = $instance;
        $aliases = new Aliases();

        foreach ([true, false] as $singleton) {
            $list = $singleton ? $definition->getSingletons() : $definition->getFactories();

            foreach ($list as list($interface, $method, $final, $args)) {
                if (isset($this->_mapping[$interface])) {
                    list(, $providerId, $methodName, $isSingleton,) = $this->_factories[$this->_mapping[$interface]];

                    if ($isSingleton !== $singleton) {
                        throw new MixedException(sprintf(MixedException::MSG, $interface));
                    }

                    if ($singleton) {
                        if (empty($this->_collections[$interface])) {
                            $collection = new Collection($this, $interface);
                            $collection->add($providerId, $methodName);
                            $this->_collections[$interface] = $collection;
                        }

                        /** @var Collection $collection */
                        $collection = $this->_collections[$interface];
                        $collection->add($id, $method);
                    }
                }

                $index = count($this->_factories);
                $uniqueId = $id . '::' . $method;
                $this->_mapping[$uniqueId] = $index;

                $aliases->add($method, $uniqueId);
                $aliases->add($interface, $uniqueId);

                if (!isset($this->_mapping[$interface]) || empty($this->_factories[$this->_mapping[$interface]][4])) {
                    $this->_mapping[$interface] = $index;
                } elseif ($final) {
                    throw new FinalException(sprintf(FinalException::MSG, $interface));
                }

                $this->_factories[$index] = [null, $instance, $method, $singleton, $final, $args];
            }
        }

        foreach ($definition->getTuners() as list($interface, $method, $result, $args)) {
            if (isset($this->_mapping[$interface]) && isset($this->_factories[$this->_mapping[$interface]][0])) {
                if ($result !== null) {
                    $message = $method ? TooLateForExtendException::MSG1 : TooLateForExtendException::MSG2;
                    throw new TooLateForExtendException(sprintf($message, $id, $method));
                }

                $service = $this->_factories[$this->_mapping[$interface]][0];
                $arguments = $this->_prepareArguments(isset($args) ? $args : [$this], null, $service);

                if ($service instanceof Aliases) {
                    try {
                        $service->setContext($aliases);
                        call_user_func_array([$instance, $method], $arguments);
                    } finally {
                        $service->setContext(null);
                    }
                } elseif ($service instanceof Tags) {
                    try {
                        $service->setContext($aliases);
                        call_user_func_array([$instance, $method], $arguments);
                    } finally {
                        $service->setContext(null);
                    }
                } else {
                    call_user_func_array([$instance, $method], $arguments);
                }
            } else {
                $this->_extends[$interface][(int)($result !== null)][] = [$instance, $method, $result, $args, $aliases];
            }
        }

        $definition->clear();
    }

    public function has($name)
    {
        if (isset($this->_mapping[$name])) {
            return true;
        }

        if ($interface = $this->get(Aliases::class)->resolve($name)) {
            return isset($this->_mapping[$interface]);
        }

        return false;
    }

    public function get($name)
    {
        if (empty($this->_factories[$this->_mapping[__CLASS__]])) {
            // 0 => instance, 1 => provider, 2 => method, 3 => singleton, 4 => final, 5 => arguments
            $this->_factories[$this->_mapping[__CLASS__]] = [$this, null, null, null, null, null];
            $this->_extendInstance(__CLASS__, $this);
        }

        if (isset($this->_mapping[$name])) {
            $index = $this->_mapping[$name];
        } elseif (($interface = $this->get(Aliases::class)->resolve($name)) && isset($this->_mapping[$interface])) {
            $index = $this->_mapping[$interface];
        } else {
            throw new ServiceNotFoundException(sprintf(ServiceNotFoundException::MSG, $name));
        }

        list($instance, $provider, $method, $singleton, /* $final */, $arguments) = $this->_factories[$index];

        if (empty($instance)) {
            if ($instance === false) {
                throw new RecursionDetectedException(sprintf(RecursionDetectedException::MSG, $name));
            }

            try {
                $this->_factories[$index][0] = false;

                $arguments = $this->_prepareArguments($arguments ?? [$this], func_get_args());
                /** @noinspection PhpUnhandledExceptionInspection */
                $instance = $this->_createInstance([$provider, $method], $arguments);
                $instance = $this->_extendInstance($name, $instance);
            } finally {
                $this->_factories[$index][0] = null;
            }

            if ($singleton) {
                $this->_factories[$index][0] = $instance;
            }
        }

        array_walk(self::$_lastInstances, function (&$list) use ($instance) {
            $list[] = $instance;
        });

        return $instance;
    }

    public function hasCollection($tagOrInterface): bool
    {
        if (isset($this->_collections[$tagOrInterface])) {
            return true;
        }

        /** @var Tags $tags */
        if (($tags = $this->get(Tags::class)) && $tags->hasTag($tagOrInterface)) {
            return true;
        }

        return false;
    }

    public function getCollection($tagOrInterface): Collection
    {
        if (isset($this->_collections[$tagOrInterface])) {
            return clone $this->_collections[$tagOrInterface];
        }

        /** @var Tags $tags */
        if (($tags = $this->get(Tags::class)) && $tags->hasTag($tagOrInterface)) {
            if (!($keys = $tags->keysByTag($tagOrInterface)) && isset($this->_mapping[$tagOrInterface])) {
                // Unique situation, when interface registered as tag and there is only one instance of it.
                $index = $this->_mapping[$tagOrInterface];
                $provider = array_search($this->_factories[$index][1], $this->_providers);
                $keys = [$provider . '::' . $this->_factories[$index][2]];
            }

            return (new Collection($this))->append($keys);
        }

        throw new CollectionNotFoundException(sprintf(CollectionNotFoundException::MSG, $tagOrInterface));
    }

    public function serialize()
    {
        if ($this->hasCollection(Tags::REGULAR)) {
            iterator_to_array($this->getCollection(Tags::REGULAR));
        }

        return serialize([$this->_providers, $this->_mapping, $this->_collections, $this->_factories, $this->_extends]);
    }

    public function unserialize($serialized)
    {
        $list = unserialize($serialized);
        list($this->_providers, $this->_mapping, $this->_collections, $this->_factories, $this->_extends) = $list;
    }

    private function _extendInstance(string $interface, $instance, array &$used = [])
    {
        $used[$interface] = true;

        if (!empty($this->_extends[$interface][0])) {
            if ($instance instanceof Aliases) {
                foreach ($this->_extends[$interface][0] as list($provider, $method, , $args, $context)) {
                    $arguments = $this->_prepareArguments($args ?? [$this], null, $instance);
                    try {
                        $instance->setContext($context);
                        call_user_func_array([$provider, $method], $arguments);
                    } finally {
                        $instance->setContext(null);
                    }
                }
            } elseif ($instance instanceof Tags) {
                foreach ($this->_extends[$interface][0] as list($provider, $method, , $args, $context)) {
                    $arguments = $this->_prepareArguments($args ?? [$this], null, $instance);
                    try {
                        $instance->setContext($context);
                        call_user_func_array([$provider, $method], $arguments);
                    } finally {
                        $instance->setContext(null);
                    }
                }
            } else {
                foreach ($this->_extends[$interface][0] as list($provider, $method, , $args,)) {
                    $arguments = $this->_prepareArguments($args ?? [$this], null, $instance);
                    call_user_func_array([$provider, $method], $arguments);
                }
            }
        }

        if (!empty($this->_extends[$interface][1])) {
            foreach ($this->_extends[$interface][1] as list($provider, $method, $class, $args)) {
                if ($method) {
                    array_push(self::$_lastInstances, []);

                    try {
                        $arguments = isset($args) ? $args : [$this];
                        $arguments = $this->_prepareArguments($arguments, null, $instance);
                        $instance = call_user_func_array([$provider, $method], $arguments);

                        $used[$class] = $used[$class] ?? in_array($instance, end(self::$_lastInstances)) ?: null;
                    } finally {
                        array_pop(self::$_lastInstances);
                    }
                }

                if (!$instance instanceof $interface) {
                    $class = $instance ? get_class($instance) : 'NULL';
                    throw new BadInstanceException(sprintf(BadInstanceException::MSG, $class, $interface));
                }

                if (empty($used[$class])) {
                    $instance = $this->_extendInstance($class, $instance, $used);
                }
            }
        }

        return $instance;
    }

    private function _prepareArguments(array $arguments, array $args = null, $prepend = null)
    {
        foreach ($arguments as &$argument) {
            if (is_string($argument) && $this->has($argument)) {
                $argument = $this->get($argument);
            }
        }

        if ($prepend) {
            array_unshift($arguments, $prepend);
        }

        return $args ? array_merge($arguments, array_slice($args, 1)) : $arguments;
    }

    private function _createInstance(callable $factory, array $arguments)
    {
        try {
            $instance = call_user_func_array($factory, $arguments);
        } catch (ContainerExceptionInterface $exception) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $exception;
        } /** @noinspection PhpUndefinedClassInspection */ catch (\Throwable $exception) {
            throw new FactoryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $instance;
    }
}