<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

/**
 * Class ProviderA
 */

namespace Moro\Container7\Test;

use Moro\Container7\Aliases;
use Moro\Container7\Container;
use Moro\Container7\Definition;
use Moro\Container7\Parameters;
use Moro\Container7\Tags;

/**
 * Class ProviderA
 */
class ProviderA
{
    public $isBootCalled;

    final public function getServiceA1(): ServiceA1
    {
        return new ServiceA1();
    }

    public function getServiceA2_1(): ServiceA2
    {
        return new ServiceA2();
    }

    public function getServiceA2_2(): ServiceA2
    {
        return new ServiceA2();
    }

    public function getServiceA3(...$arguments): ServiceA3
    {
        return new ServiceA3($arguments[0]);
    }

    public function getServiceA4(ServiceA1 $serviceA): ServiceA4
    {
        return new ServiceA4($serviceA);
    }

    public function getServiceA5(...$arguments): ServiceA5
    {
        $service = new ServiceA5();
        $service->setValue5(array_shift($arguments) ?? 'v5');
        return $service;
    }

    public function extendA5_1(ServiceA5 $service): ?ServiceA6
    {
        unset($service);
        return null;
    }

    public function extendA5_2(ServiceA5 $service): ?ServiceA6
    {
        $newService = new ServiceA6();
        $newService->setValue5($service->getValue5());
        return $newService;
    }

    public function extendA6(ServiceA6 $service)
    {
        $service->setValue6('v6');
    }

    public function getServiceA7(Container $container, Parameters $parameters): ServiceA7
    {
        $service = new ServiceA7();
        $service->setCollection($container->getCollection('t2')->asArray());
        $service->setValue7($parameters->get('parameter_a7'));
        return $service;
    }

    public function init(Definition $definition)
    {
        unset($definition);
    }

    public function boot(Container $container)
    {
        unset($container);
        $this->isBootCalled = true;
        return null;
    }

    public function parameters(Parameters $parameters)
    {
        $parameters->set('new_parameter', 1);
        $parameters->set('parameter_a7', 7);
        $parameters->set('isBootCalled', $this->isBootCalled);
    }

    public function aliases(Aliases $aliases)
    {
        $aliases->add('a1', ServiceA1::class);
        $aliases->add('a2', 'unknown interface');
        $aliases->add('a5', ServiceA5::class);
    }

    public function tags(Tags $tags)
    {
        $tags->add('t2', 'getServiceA2_1');
        $tags->add('t2', 'getServiceA2_2');
        $tags->add('t5', 'getServiceA5');
    }
}