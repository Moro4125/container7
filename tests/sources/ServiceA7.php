<?php
/**
 * Class ServiceA7
 */

namespace Moro\Container7\Test;

/**
 * Class ServiceA7
 */
class ServiceA7
{
    protected $collection;
    protected $value7;

    public function getCollection()
    {
        return $this->collection;
    }

    public function setCollection($value)
    {
        $this->collection = $value;
    }

    public function getValue7()
    {
        return $this->value7;
    }

    public function setValue7($value)
    {
        $this->value7 = $value;
    }
}