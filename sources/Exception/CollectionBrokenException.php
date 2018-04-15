<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Exception;

/**
 * Class CollectionBrokenException
 * @package Moro\Container7\Exception
 */
class CollectionBrokenException extends \RuntimeException
{
    const MSG_1 = 'Collection can contains only objects.';
    const MSG_2 = 'Collection can contains instance of "%1$s". But "%2$s" found.';
}