<?php
/**
 * Class ServiceA4
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceA4
 */
class ServiceA4
{
    protected $serviceA1;

    public function __construct(ServiceA1 $service)
    {
        $this->serviceA1 = $service;
    }

    public function getServiceA1(): ServiceA1
    {
        return $this->serviceA1;
    }
}