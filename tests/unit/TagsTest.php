<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

use Moro\Container7\Aliases;
use Moro\Container7\Tags;

/**
 * Class TagsTest
 */
class TagsTest extends \PHPUnit\Framework\TestCase
{
    use \Codeception\Specify;

    // tests
    public function test()
    {
        $this->specify('Object Tags after construction does not have any tagsForKey', function () {
            $aliases = new Aliases();
            $tags = new Tags($aliases);

            verify($tags->hasTag('unknown tag'))->false();
            verify($tags->keysByTag('unknown tag'))->same([]);
            verify($tags->hasKey('unknown key'))->false();
            verify($tags->tagsForKey('unknown key'))->same([]);
        });

        $this->specify('Check simple tagsForKey and it\'s priorities.', function () {
            $aliases = new Aliases();
            $tags = new Tags($aliases);

            $tags->add('t1', 'i1', 1);
            $tags->add('t1', 'i2', 2);
            $tags->add('t1', 'i3', 0);

            verify($tags->hasKey('i1'))->true();
            verify($tags->hasKey('i2'))->true();
            verify($tags->hasKey('i3'))->true();

            verify($tags->hasTag('t1'))->true();
            verify($tags->keysByTag('t1'))->same(['i2', 'i1', 'i3']);

            $tags->add('t2', 'i1', 3);
            $tags->add('t2', 'i2', 2);
            $tags->add('t2', 'i3', 1);

            verify($tags->hasKey('i1'))->true();
            verify($tags->hasKey('i2'))->true();
            verify($tags->hasKey('i3'))->true();

            verify($tags->hasTag('t2'))->true();
            verify($tags->keysByTag('t2'))->same(['i1', 'i2', 'i3']);

            verify($tags->tagsForKey('i1'))->same(['t1', 't2']);

            $tags->add('t3', 'i1', 1);
            $tags->add('t3', 'i2', 1);
            $tags->add('t3', 'i3', 1);

            verify($tags->hasTag('t3'))->true();
            verify($tags->keysByTag('t3'))->same(['i1', 'i2', 'i3']);

            $tags->add('t4', 't4', 1);
            verify($tags->keysByTag('t4'))->same(['t4']);
            verify($tags->tagsForKey('t4'))->same(['t4']);
        });

        $this->specify('Check bad float priorities.', function () {
            $aliases = new Aliases();
            $tags = new Tags($aliases);

            $tags->add('t1', 'i1', 0);
            $tags->add('t1', 'i2', 0.000001);
            $tags->add('t1', 'i3', 0.0000005);
            $tags->add('t1', 'i4', 0.0000006);

            verify($tags->keysByTag('t1'))->same(['i3', 'i2', 'i1', 'i4']);
        });

    }
}