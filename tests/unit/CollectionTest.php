<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

use Moro\Container7\Aliases;
use Moro\Container7\Collection;
use Moro\Container7\Container;
use Moro\Container7\Definition;
use Moro\Container7\Parameters;
use Moro\Container7\Provider;
use Moro\Container7\Tags;

/**
 * Class CollectionTest
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use Codeception\Specify;
    use Codeception\AssertThrows;

    public function testCollectionWithoutInterface()
    {
        $collection = new Collection(new Container());
        verify($collection->isEmpty())->true();
        verify($collection->valid())->false();
        verify($collection->count())->same(0);

        $collection->add('aliases');
        verify($collection->isEmpty())->false();

        $collection->add(Provider::class, 'parameters');
        $collection->add(Tags::class);

        $collection->rewind();
        verify($collection->valid())->true();
        verify($collection->key())->same(0);
        verify($collection->current())->isInstanceOf(Aliases::class);

        $collection->next();
        verify($collection->valid())->true();
        verify($collection->key())->same(1);
        verify($collection->current())->isInstanceOf(Parameters::class);

        $collection->next();
        verify($collection->valid())->true();
        verify($collection->key())->same(2);
        verify($collection->current())->isInstanceOf(Tags::class);

        $collection->next();
        verify($collection->valid())->false();
        verify($collection->key())->same(null);
        verify($collection->current())->same(null);

        verify($collection->count())->same(3);
    }

    public function testCollectionWithInterface()
    {
        $collection = new Collection(new Container(), ArrayAccess::class);
        verify($collection->isEmpty())->true();

        $collection->add('parameters');
        verify($collection->isEmpty())->false();

        $collection->add(Aliases::class);
        verify($collection->count())->same(2);

        $collection->rewind();
        verify($collection->valid())->true();
        verify($collection->key())->same(0);
        verify($collection->current())->isInstanceOf(Parameters::class);

        $this->specify('We ask service without required interface', function () use ($collection) {
            $this->assertThrows(\RuntimeException::class, function () use ($collection) {
                $collection->next();

                verify($collection->valid())->true();
                verify($collection->key())->same(1);

                $collection->current();
            });
        });

        $definition = new Definition(new ArrayObject());
        $definition->addSingleton('notObject', 'count', []);

        $container = new Container();
        $container->addProvider($definition);

        $collection = new Collection($container, ArrayAccess::class);
        $collection->add('notObject');

        $this->specify('Test exception for value, that is not object', function () use ($collection) {
            $eMessage = 'Collection can contains only objects.';

            $this->assertThrows([RuntimeException::class, $eMessage], function () use ($collection) {
                $collection->rewind();

                verify($collection->valid())->true();
                verify($collection->key())->same(0);

                $collection->current();
            });
        });
    }

    public function testCollectionMethods()
    {
        $this->specify('Test collection method "append".', function () {
            $collection = new Collection(new Container(), ArrayAccess::class);
            $result = $collection->append(['parameters']);

            verify($collection->isEmpty())->false();
            verify($result)->same($collection);
        });

        $this->specify('Test collection method "asArray".', function () {
            $container = new Container();
            $collection = new Collection($container, ArrayAccess::class);
            $collection->append(['parameters']);

            verify($collection->asArray())->same([$container->get(Parameters::class)]);
        });

        $this->specify('Test collection methods "merge", "exclude", "with".', function () {
            $container = new Container();

            /** @var Tags $tags */
            $tags = $container->get(Tags::class);
            $tags->add(Aliases::class, Aliases::class);
            $tags->add(Parameters::class, Parameters::class);
            $tags->add(Container::class, Container::class);

            $collection = new Collection($container, ArrayAccess::class);
            $collection->append([Parameters::class]);
            $collection = $collection->merge(Aliases::class);

            verify($collection->asArray())->same([
                $container->get(Parameters::class),
                $container->get(Aliases::class),
            ]);

            $collection = $collection->exclude(Parameters::class);

            verify($collection->asArray())->same([
                $container->get(Aliases::class),
            ]);

            $collection = $collection->merge(Aliases::class);

            verify($collection->asArray())->same([
                $container->get(Aliases::class),
            ]);

            $collection = $collection->merge(Container::class);

            verify($collection->asArray())->same([
                $container->get(Aliases::class),
                $container,
            ]);

            $collection = $collection->with(Tags::REGULAR);

            verify($collection->asArray())->same([
                $container,
            ]);
        });
    }
}