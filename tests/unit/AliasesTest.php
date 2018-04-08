<?php

use Moro\Container7\Aliases;

/**
 * Class AliasesTest
 */
class AliasesTest extends \PHPUnit\Framework\TestCase
{
    // tests
    public function testAllVariants()
    {
        $aliases = new Aliases();

        verify($aliases->resolve('something'))->same(null);

        $aliases->add('a1', 'i1');
        verify($aliases->resolve('a1'))->same('i1');
        $aliases->add('i1', 'i2');
        verify($aliases->resolve('a1'))->same('i2');
        $aliases->add('a2', 'i1');
        verify($aliases->resolve('a2'))->same('i2');

        $aliases->add('a3', 'i3');
        verify($aliases->resolve('a3'))->same('i3');
        $aliases->add('a4', 'i3');
        verify($aliases->resolve('a4'))->same('i3');
        $aliases->add('i3', 'i4');
        verify($aliases->resolve('a3'))->same('i4');
        verify($aliases->resolve('a4'))->same('i4');
        $aliases->add('a5', 'i3');
        verify($aliases->resolve('a5'))->same('i4');

        $aliases->add('a6', 'i5');
        verify($aliases->resolve('a6'))->same('i5');
        $aliases->add('a6', 'i6');
        verify($aliases->resolve('a6'))->same('i6');
    }
}