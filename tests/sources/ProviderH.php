<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

/**
 * Class ProviderH
 */

namespace Moro\Container7\Test;

use Moro\Container7\Aliases;
use Moro\Container7\Container;
use Moro\Container7\Definition;

/**
 * Class ProviderH
 */
class ProviderH
{
    public function __construct()
    {
    }

    public function getServiceH1(Container $container): ServiceH1
    {
        $service = $container->get(ServiceH2::class);
        unset($service);
        return new ServiceH1();
    }

    public function getServiceH2(Container $container): ServiceH2
    {
        $service = $container->get(ServiceH1::class);
        unset($service);
        return new ServiceH2();
    }

    public function getServiceH3(): ServiceH3
    {
        throw new \RuntimeException();
    }

    public function getServiceH4(): ServiceH4
    {
        return new ServiceH4();
    }

    public function initServiceH4(ServiceH4 $service): ?ServiceH5
    {
        unset($service);
        return new ServiceH5();
    }

    public function getServiceH5(): ServiceH5
    {
        return new ServiceH5();
    }

    public function extendServiceH5(ServiceH5 $service): ?ServiceH6
    {
        return new ServiceH6($service);
    }

    public function extendServiceH6(ServiceH6 $service)
    {
        $service->value++;
    }

    public function getServiceH7(): ServiceH7
    {
        return new ServiceH7();
    }

    public function extendServiceH7(ServiceH7 $service, Container $container): ?ServiceH8
    {
        return $container->get(ServiceH8::class, $service);
    }

    public function getServiceH8(...$args): ServiceH8
    {
        return new ServiceH8($args[0]);
    }

    public function extendServiceH8(ServiceH8 $service)
    {
        $service->value++;
    }

    public function aliases(Aliases $aliases)
    {
        $aliases->add('a', 'b');
    }

    public function definition(Definition $definition)
    {
        $definition->addTuner(ServiceH7::class, null, null, ServiceH7::class);
    }
}