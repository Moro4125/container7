<?php
/**
 * This file is part of the package moro/container7
 *
 * @see https://github.com/Moro4125/container7
 * @license http://opensource.org/licenses/MIT
 * @author Morozkin Andrey <andrey.dmitrievich@gmail.com>
 */

use Moro\Container7\Parameters;

/**
 * Class ParametersTest
 */
class ParametersTest extends \PHPUnit\Framework\TestCase
{
    use Codeception\Specify;
    use Codeception\AssertThrows;

    public function test()
    {
        $this->specify('Set value and read value by key', function () {
            $parameters = new Parameters();
            $parameters->add('k1', 'v1');
            verify($parameters->get('k1'))->same('v1');

            $parameters->set('k1', 'v2');
            verify($parameters->get('k1'))->same('v2');

            $parameters->add('k1', 'v3');
            verify($parameters->get('k1'))->same('v3');

            $parameters->set('k1', 4);
            verify($parameters->get('k1'))->same(4);

            $parameters->add('k1', 5);
            verify($parameters->get('k1'))->same(5);
            verify($parameters->has('k1'))->true();
        });

        $this->specify('Delete value by key', function () {
            $parameters = new Parameters();
            $parameters->add('k1', 'v1');

            verify($parameters->has('k1'))->true();
            $parameters->del('k1');
            verify($parameters->has('k1'))->false();
        });

        $this->specify('Read all values', function () {
            $parameters = new Parameters();
            $parameters->add('k1', 'v1');
            $parameters->add('k2', 'v2');
            $parameters->add('k3', 'v3');

            verify($parameters->all())->same(['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3']);
        });

        $this->specify('Use parameters in string values', function () {
            $parameters = new Parameters();
            $parameters->add('k1', 1);
            $parameters->add('k2', '%k1%');
            $parameters->add('k3', '%k1% %k2%');
            $parameters->add('k4', ['%k1%', '%k2%', '%k1% %k2%', '%k3%']);
            $parameters->add('k5', '%k4%');
            $parameters->add('k6', '%k4% :-)');
            $parameters->add('k7', '%k0%');
            $parameters->add('k8', '42 %k1%');

            verify($parameters->get('k2'))->same(1);
            verify($parameters->get('k3'))->same('1 1');
            verify($parameters->get('k4'))->same([1, 1, '1 1', '1 1']);
            verify($parameters->get('k5'))->same([1, 1, '1 1', '1 1']);
            verify($parameters->get('k6'))->same('[array] :-)');
            verify($parameters->get('k7'))->same('%k0%');
            verify($parameters->get('k8'))->same('42 1');

            verify($parameters->raw('k2'))->same('%k1%');

            verify($parameters->resolve(' %k1% '))->same(' 1 ');
        });

        $this->specify('Check ArrayAccess interface', function () {
            $parameters = new Parameters();

            $parameters->offsetSet('k1', 1);
            $parameters->offsetSet('k2', 2);

            verify($parameters->offsetExists('k0'))->false();
            verify($parameters->offsetExists('k1'))->true();

            $parameters->offsetUnset('k1');

            verify($parameters->offsetExists('k1'))->false();
            verify($parameters->offsetGet('k2'))->same(2);
        });

        $this->specify('Access to deep items by special keysByTag', function () {
            $parameters = new Parameters();
            $parameters->add('k1', ['k1.1' => 'v1.1', 'k1.2' => ['k1.2.1' => 'v1.2.1']]);

            verify($parameters->has('k1/k1.1'))->true();
            verify($parameters->has('k1/k1.2'))->true();
            verify($parameters->has('k1/k1.2/k1.2.1'))->true();
            verify($parameters->has('k1/k1.2/k1.2.2'))->false();
            verify($parameters->has('k1/k1.1/k1.1.1'))->false();

            verify($parameters->get('k1/k1.1'))->same('v1.1');
            verify($parameters->get('k1/k1.2'))->same(['k1.2.1' => 'v1.2.1']);
            verify($parameters->get('k1/k1.2/k1.2.1'))->same('v1.2.1');
            verify($parameters->get('k1/k1.2/k1.2.2'))->same(null);

            $parameters->add('k2', '%k1/k1.1%');
            $parameters->add('k3', '%k1/k1.1% %k1/k1.2/k1.2.1%');

            verify($parameters->get('k2'))->same('v1.1');
            verify($parameters->get('k3'))->same('v1.1 v1.2.1');
            verify($parameters->get('k1/k1.1/k1.1.1/k1.1.1.1'))->same(null);

            verify($parameters->get('k4', false))->false();
            verify($parameters->get('k1/k2.1/k0', false))->false();
        });

        $this->specify('Check array merge feature', function () {
            $parameters = new Parameters();

            $parameters->add('k1', '...');
            $parameters->add('k1', ['v1.3' => null]);
            verify($parameters->get('k1'))->same(['v1.3' => null]);
        });

        $this->specify('Check array append feature', function () {
            $parameters = new Parameters();

            $parameters->append(['k1' => 'v1', 'k2' => ['k2.1' => 'v2.1']]);

            verify($parameters->get('k1'))->same('v1');
            verify($parameters->get('k2'))->same(['k2.1' => 'v2.1']);

            $parameters->append(['k1' => 'v2', 'k2' => ['k2.2' => 'v2.2']]);

            verify($parameters->get('k1'))->same('v2');
            verify($parameters->get('k2'))->same(['k2.2' => 'v2.2', 'k2.1' => 'v2.1']);
        });

        $this->specify('Wrong parameter value definition', function () {
            $parameters = new Parameters();
            $parameters->add('k1', 'v1');
            $parameters->add('k2', 'ak1%');
            verify($parameters->get('k2'))->notSame('v1');
        });

        $this->specify('Call method merge from method add', function () {
            $parameters = new Parameters();
            $parameters->add('k1', ['v1']);
            $parameters->add('k1', ['v2']);
            verify($parameters->get('k1'))->same(['v2', 'v1']);
        });
    }

    public function testMerge()
    {
        $a = ['a' => 1];
        $b = ['b' => 2];
        $c = ['b' => 2, 'a' => 1];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => 1];
        $b = ['a' => 2];
        $c = ['a' => 2];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a'];
        $b = ['b'];
        $c = ['b', 'a'];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => 1, 'b' => 2];
        $b = ['b' => null];
        $c = ['a' => 1];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a', 'b', 'c'];
        $b = [null, null];
        $c = ['c'];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => 1, 'b', 'c' => 2];
        $b = ['a' => 3, 'd'];
        $c = ['d', 'a' => 3, 'b', 'c' => 2];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => ['b' => 1]];
        $b = ['a' => ['c' => 2]];
        $c = ['a' => ['c' => 2, 'b' => 1]];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => ['b' => 1]];
        $b = ['a' => 2];
        $c = ['a' => 2];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => 1];
        $b = ['a' => ['b' => 2]];
        $c = ['a' => ['b' => 2]];

        verify(Parameters::merge($a, $b))->same($c);

        $a = ['a' => 1, 'b' => 2];
        $b = ['c', 'a' => null];
        $c = ['c', 'b' => 2];

        verify(Parameters::merge($a, $b))->same($c);
    }

    public function testOptionsFromFile()
    {
        $path = __DIR__ . '/../_data/parameters.json';
        $parameters = Parameters::fromFile($path);

        verify($parameters->get('@files'))->same([
            realpath(__DIR__ . '/../_data/parameters.json'),
            realpath(__DIR__ . '/../_data/parent.json'),
        ]);
        verify($parameters->get('first'))->same('new value');

        $path = realpath($path);

        verify(Parameters::fromFile($path, __DIR__)->raw())->same($parameters->raw());

        $path = preg_replace('{^.*:}', '', $path);
        $path = str_replace('\\', '/', $path);

        verify(Parameters::fromFile($path, __DIR__)->raw())->same($parameters->raw());

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertThrows(RuntimeException::class, function () {
            Parameters::fromFile('not_exists.json');
        });
    }
}