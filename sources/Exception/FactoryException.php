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
 * Class FactoryException
 */
class FactoryException extends RuntimeException implements ContainerExceptionInterface
{
}