<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

namespace Moro\Container7\Test;

use Moro\Container7\Aliases;
use Moro\Container7\Tags;

/**
 * Class ProviderJ
 */
class ProviderJ
{
    public function aliases(Aliases $aliases)
    {
        $aliases->add('alias1', 'serviceA1');
    }

    public function tags(Tags $tags)
    {
        $tags->register(ServiceA1::class);
        $tags->add(ServiceA2::class, ServiceA2::class);
        $tags->add('tag2', ServiceA2::class);
        $tags->add('tag3', __CLASS__ . '::serviceA2');
        $tags->add('tag4', 'serviceA2');
    }

    public function serviceA1(): ServiceA1
    {
        return new ServiceA1();
    }

    public function serviceA2(): ServiceA2
    {
        return new ServiceA2();
    }
}