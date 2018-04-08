<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Class ServiceNotFoundException
 */
class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    const MSG = 'Service "%1$s" is not exists.';
}