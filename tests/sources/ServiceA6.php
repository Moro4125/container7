<?php
/**
 * Class ServiceA6
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceA6
 */
class ServiceA6 extends ServiceA5
{
    protected $value6;

    public function getValue6()
    {
        return $this->value6;
    }

    public function setValue6($value)
    {
        $this->value6 = $value;
    }
}