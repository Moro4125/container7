<?php
/**
 * Class ServiceA3
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceA3
 */
class ServiceA3
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}