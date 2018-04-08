<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Helper\Decorator;

use Countable;

/**
 * Trait CountableDecoratorTrait
 */
trait CountableDecoratorTrait
{
    public function count()
    {
        /** @var DecoratorInterface $self */
        $self = $this;
        $instance = $self->getOriginalInstance();

        return $instance instanceof Countable ? $instance->count() : -1;
    }
}