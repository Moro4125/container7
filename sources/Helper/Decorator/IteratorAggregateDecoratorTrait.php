<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Helper\Decorator;

/**
 * Trait IteratorAggregateDecoratorTrait
 */
trait IteratorAggregateDecoratorTrait
{
    public function getIterator()
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        foreach ($instance as $key => $value) {
            yield $key => $value;
        }
    }
}