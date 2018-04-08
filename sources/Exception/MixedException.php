<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Exception;

use LogicException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class MixedException
 */
class MixedException extends LogicException implements ContainerExceptionInterface
{
    const MSG = 'Interface or class "%1$s" can not have singleton and factory implementations.';
}