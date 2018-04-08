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
 * Class FinalException
 */
class FinalException extends LogicException implements ContainerExceptionInterface
{
    const MSG = 'Interface or class "%1$s" has factory, that is marked as final. You can not reassign implementation.';
}