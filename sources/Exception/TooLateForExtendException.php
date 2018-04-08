<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Class TooLateForExtendException
 */
class TooLateForExtendException extends RuntimeException implements ContainerExceptionInterface
{
    const MSG1 = 'Provider "%1$s" in method "%1$s" try extend already created instance.';
    const MSG2 = 'Provider "%1$s" try extend already created instance.';
}