<?php
/**
 * Class ServiceH8
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceH6
 */
class ServiceH8 extends ServiceH7
{
    public $value = 0;
    protected $_service;

    public function __construct(ServiceH7 $service)
    {
        $this->_service = $service;
    }

    public function getService(): ServiceH7
    {
        return $this->_service;
    }
}