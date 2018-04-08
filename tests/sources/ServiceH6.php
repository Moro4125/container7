<?php
/**
 * Class ServiceH6
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceH6
 */
class ServiceH6 extends ServiceH5
{
    public $value = 0;
    protected $_service;

    public function __construct(ServiceH5 $service)
    {
        $this->_service = $service;
    }

    public function getService(): ServiceH5
    {
        return $this->_service;
    }
}