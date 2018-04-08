<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Helper\Decorator;

use ArrayAccess;

/**
 * Trait ArrayAccessDecoratorTrait
 */
trait ArrayAccessDecoratorTrait
{
    public function offsetExists($offset)
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        return $instance instanceof ArrayAccess ? $instance->offsetExists($offset) : isset($instance->{$offset});
    }

    public function offsetSet($offset, $value)
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        if ($instance instanceof ArrayAccess) {
            $instance->offsetSet($offset, $value);
        } else {
            $instance->{$offset} = $value;
        }
    }

    public function offsetGet($offset)
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        return $instance instanceof ArrayAccess ? $instance->offsetGet($offset) : $instance->{$offset};
    }

    public function offsetUnset($offset)
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        if ($instance instanceof ArrayAccess) {
            $instance->offsetUnset($offset);
        } else {
            unset($instance->{$offset});
        }
    }
}