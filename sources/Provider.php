<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7;

use IteratorAggregate;

/**
 * Class Provider
 */
final class Provider extends Definition
{
    const CODE_CUT_ARGUMENT = 1;
    const CODE_NEW_INSTANCE = 2;
    const CODE_SET_PROPERTY = 3;
    const CODE_CALL_METHOD = 4;
    const CODE_FOREACH = 5;
    /** @var Parameters */
    protected $_configuration;
    protected $_instructions;
    protected $_tag;

    public function __construct(Parameters $configuration = null)
    {
        parent::__construct($this);

        if (empty($configuration)) {
            $this->addSingleton(Aliases::class, 'aliases', null, true);
            $this->addSingleton(Tags::class, 'tags', [Aliases::class], true);
            $this->addSingleton(Parameters::class, 'parameters', null, true);
            $this->addFactory(Collection::class, 'collection');
        } elseif ($configuration->raw()) {
            $this->_configuration = clone $configuration;
            $this->addTuner(Parameters::class, 'configure', null, Parameters::class);
        }
    }

    static function fromParameters(string $name, Parameters $parameters): Provider
    {
        if ($parameters->has('container')) {
            $containerConf = $parameters->raw('container');
            $parameters->del('container');
        }

        $instance = new self($parameters);
        $instance->setId($name);

        if (isset($containerConf)) {
            $instance->_initContainer($containerConf);
        }

        return $instance;
    }

    static function fromConfiguration(string $name, $configuration): Provider
    {
        $parameters = is_array($configuration) ? new Parameters(['container' => $configuration]) : $configuration;
        return self::fromParameters($name, $parameters);
    }

    private function _initContainer(array $config)
    {
        $id = $this->getId();
        $index = null;
        $simple = [Aliases::class => 'aliases', Parameters::class => 'parameters', Tags::class => 'tags'];
        $bad = array_flip(get_class_methods($this));
        $map = ['singletons' => true, 'factories' => false, 'extends' => null];

        foreach ($config['services'] ?? [] as $defs) {
            $config[empty($defs['factory']) ? 'singletons' : 'factories'][] = $defs;
        }

        foreach (array_intersect_key($map, $config) as $key => $singleton) {
            foreach (array_reverse((array)$config[$key]) as $defs) {
                $index++;
                $instructions = [];

                $target = $defs['target'] ?? null;
                $replace = $defs['class'] ?? null;
                $interface = $defs['interface'] ?? $replace;
                $alias = (string)(empty($bad[$defs['alias'] ?? 'aliases']) ? $defs['alias'] : $index);
                $final = !empty($defs['final']);
                $path = $id . '::' . ($target ?: $alias);

                if (!is_numeric($alias)) {
                    $defs['aliases'][] = $defs['alias'];

                    if ($target) {
                        $alias = (string)($index);
                    }
                }

                if ($singleton === true) {
                    $this->addSingleton($interface, $alias, [Container::class], $final);
                } elseif ($singleton === false) {
                    $this->addFactory($interface, $alias, [Container::class], $final);
                } else {
                    $this->addTuner($target, $alias, [Container::class], $replace ?? $interface);
                    $instructions[] = [self::CODE_CUT_ARGUMENT];
                }

                $instructions[] = [self::CODE_CUT_ARGUMENT];

                if (isset($defs['class'])) {
                    $arguments = $defs['args'] ?? [];
                    $instructions[] = [self::CODE_NEW_INSTANCE, $defs['class'], $arguments];
                }

                if (isset($defs['properties'])) {
                    foreach ($defs['properties'] as $k => $v) {
                        $instructions[] = [self::CODE_SET_PROPERTY, $k, $v];
                    }
                }

                if (isset($defs['calls'])) {
                    $rich = empty($simple[$target]);

                    foreach ($defs['calls'] as $call) {
                        $arguments = $call['args'] ?? [];

                        if (isset($call['foreach'])) {
                            $instructions[] = [self::CODE_FOREACH, $call['foreach']];
                        }

                        $instructions[] = [self::CODE_CALL_METHOD, $call['method'], $arguments, $rich];
                    }
                }

                if (isset($defs['aliases'])) {
                    foreach ($defs['aliases'] as $v) {
                        $config['aliases'][$v] = $path;
                    }
                }

                if (isset($defs['tags'])) {
                    foreach ($defs['tags'] as $k => $v) {
                        $tag = is_string($k) ? $k : $v;
                        $meta = is_string($k) ? $v : null;
                        $config['tags'][] = [$tag, $path, $meta];
                    }
                }

                $this->_instructions[$alias] = $instructions;
            }
        }

        foreach (array_intersect_key(array_flip($simple), $config) as $key => $class) {
            $instructions = [[self::CODE_CUT_ARGUMENT], [self::CODE_CUT_ARGUMENT]];
            $alias = '_' . $key;

            $this->addTuner($class, $alias, [Container::class], null);

            foreach ($config[$key] as $k => $v) {
                $instructions[] = [self::CODE_CALL_METHOD, 'add', is_int($k) ? $v : [$k, $v], null];
            }

            $this->_instructions[$alias] = $instructions;
        }
    }

    public function __call($name, $arguments)
    {
        assert(isset($this->_instructions[$name]));

        /** @var Container $container */
        $container = null;
        $instance1 = null;
        $list = null;
        $flag = null;

        $instructions = $this->_instructions[$name];

        while ($instruction = array_shift($instructions)) {
            switch (array_shift($instruction)) {
                case self::CODE_CUT_ARGUMENT:
                    $instance1 = $container;
                    $container = array_shift($arguments);
                    $arguments['target'] = $instance1;
                    break;

                case self::CODE_NEW_INSTANCE:
                    list($class, $args) = $instruction;

                    $class = is_int(strpos($class, '%'))
                        ? $container->get(Parameters::class)->resolve($class)
                        : $class;
                    $args = $this->_prepareArguments($args, $container, $arguments);
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $instance1 = empty($args)
                        ? new $class
                        : (new \ReflectionClass($class))->newInstanceArgs($args);
                    break;

                case self::CODE_SET_PROPERTY:
                    list($property, $value) = $instruction;

                    $value = $this->_prepareArguments([$value], $container, $arguments);
                    $instance1->{$property} = reset($value);
                    break;

                case self::CODE_CALL_METHOD:
                    list($method, $args, $rich) = $instruction;

                    if ($rich) {
                        $args = $this->_prepareArguments($args, $container, $arguments);
                    }

                    $arguments['result'] = call_user_func_array([$instance1, $method], $args);
                    break;

                case self::CODE_FOREACH:
                    list($value) = $instruction;

                    if ($list === null) {
                        /** @var \Iterator|IteratorAggregate|array $list */
                        $list = $this->_prepareArguments([$value], $container, $arguments);
                        $list = reset($list);

                        if ($list instanceof IteratorAggregate) {
                            $list = $list->getIterator();
                        }

                        $flag = is_array($list);
                        $flag ? reset($list) : $list->rewind();
                    }

                    if ($flag ? key($list) !== null : $list->valid()) {
                        $arguments['index'] = $flag ? key($list) : $list->key();
                        $arguments['item'] = $flag ? current($list) : $list->current();

                        $flag ? next($list) : $list->next();

                        $instruction = reset($instructions);
                        array_unshift($instructions, [self::CODE_FOREACH, $value]);
                        array_unshift($instructions, $instruction);
                    } else {
                        array_shift($instructions);
                        $this->_tag = null;
                        $list = null;
                    }

                    break;
            }
        }

        return $instance1;
    }

    private function _prepareArguments($args, Container $container, $arguments): array
    {
        /** @var Parameters $parameters */
        $parameters = null;
        /** @var Tags $tags */
        $tags = null;

        foreach ($args as &$value) {
            if (is_string($value)) {
                $property = null;
                $method = null;
                $key = null;

                if (strpos($value, '->')) {
                    list($value, $property) = explode('->', $value);
                } elseif (strpos($value, '::')) {
                    list($value, $method) = explode('::', $value);
                } elseif (strpos($value, '[')) {
                    list($value, $key) = explode('[', substr($value, 0, -1));
                }

                if ($value === '$collections') {
                    $arguments['collections'] = $container->getCollection($key)->asArray();
                    $this->_tag = $this->_tag ?? $key;
                    $key = null;
                }

                if ($value === '$meta' && $this->_tag) {
                    $tags = $tags ?? $container->get(Tags::class);
                    $meta = $tags->metaByTagAndKey($this->_tag, $arguments['index'] ?? '');
                    $arguments['meta'] = $meta[$key] ?? null;
                    $key = null;
                }

                switch (substr($value, 0, 1)) {
                    case '$':
                        $value = $arguments[substr($value, 1)] ?? null;
                        break;
                    case '@':
                        $value = $container->get(substr($value, 1));
                        break;
                    default:
                        if (is_int(strpos($value, '%'))) {
                            $parameters = $parameters ?: $container->get(Parameters::class);
                            $value = $parameters->resolve($value);
                        }
                }

                if ($property !== null && is_object($value)) {
                    $value = $value->{$property};
                } elseif ($method !== null && is_object($value)) {
                    $value = call_user_func([$value, $method]);
                } elseif ($key !== null) {
                    $value = $value[$key];
                }
            } elseif (is_array($value)) {
                $parameters = $parameters ?: $container->get(Parameters::class);
                $value = $parameters->resolve($value);
            }
        }

        return $args;
    }

    final public function aliases(): Aliases
    {
        $aliases = new Aliases();
        $aliases->add('aliases', Aliases::class);
        $aliases->add('parameters', Parameters::class);
        $aliases->add('tags', Tags::class);

        return $aliases;
    }

    final public function tags(Aliases $aliases): Tags
    {
        $tags = new Tags($aliases);
        $tags->add(Tags::REGULAR, Container::class);
        return $tags;
    }

    final public function parameters(): Parameters
    {
        return new Parameters();
    }

    public function configure(Parameters $parameters): ?Parameters
    {
        if ($this->_configuration) {
            foreach ($this->_configuration->raw() as $key => $value) {
                $parameters->add($key, $value);
            }
        }

        return $parameters;
    }

    public function collection(Container $container, ...$arguments): Collection
    {
        $items = array_shift($arguments) ?? [];
        $interface = array_shift($arguments);

        $collection = new Collection($container, $interface);
        $collection->append($items);
        return $collection;
    }
}