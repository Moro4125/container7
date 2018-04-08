<?php
/**
 * Class ProviderI
 */

namespace Moro\Container7\Test;

/**
 * Class ProviderI
 */
class ProviderI
{
    public function newServiceA1_1(...$arguments): ServiceA1
    {
        unset($arguments);
        return new ServiceA1();
    }

    public function getServiceA1_2(): ServiceA1
    {
        return new ServiceA1();
    }
}