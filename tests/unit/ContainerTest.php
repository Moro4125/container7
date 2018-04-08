<?php

use Moro\Container7\Aliases;
use Moro\Container7\Collection;
use Moro\Container7\Container;
use Moro\Container7\Exception\BadInstanceException;
use Moro\Container7\Exception\BadMethodDefinitionException;
use Moro\Container7\Exception\CollectionNotFoundException;
use Moro\Container7\Exception\DuplicateProviderException;
use Moro\Container7\Exception\FactoryException;
use Moro\Container7\Exception\FinalException;
use Moro\Container7\Exception\MixedException;
use Moro\Container7\Exception\RecursionDetectedException;
use Moro\Container7\Exception\ServiceNotFoundException;
use Moro\Container7\Exception\TooLateForExtendException;
use Moro\Container7\Parameters;
use Moro\Container7\Provider;
use Moro\Container7\Tags;
use Moro\Container7\Test\ProviderA;
use Moro\Container7\Test\ProviderB;
use Moro\Container7\Test\ProviderC;
use Moro\Container7\Test\ProviderD;
use Moro\Container7\Test\ProviderE;
use Moro\Container7\Test\ProviderF;
use Moro\Container7\Test\ProviderG;
use Moro\Container7\Test\ProviderH;
use Moro\Container7\Test\ProviderI;
use Moro\Container7\Test\ServiceA1;
use Moro\Container7\Test\ServiceA2;
use Moro\Container7\Test\ServiceA3;
use Moro\Container7\Test\ServiceA4;
use Moro\Container7\Test\ServiceA5;
use Moro\Container7\Test\ServiceA6;
use Moro\Container7\Test\ServiceA7;
use Moro\Container7\Test\ServiceH1;
use Moro\Container7\Test\ServiceH3;
use Moro\Container7\Test\ServiceH4;
use Moro\Container7\Test\ServiceH5;
use Moro\Container7\Test\ServiceH6;
use Moro\Container7\Test\ServiceH7;
use Moro\Container7\Test\ServiceH8;

/**
 * Class ContainerTest
 */
class ContainerTest extends \PHPUnit\Framework\TestCase
{
    use Codeception\Specify;
    use Codeception\AssertThrows;

    /** @noinspection PhpUndefinedClassInspection */

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var Container
     */
    protected $container;

    public function testContainerWithOptions()
    {
        $parameters = new Parameters(['a' => 1, 'new_parameter' => 2]);
        $this->container = new Container($parameters);

        /** @var Parameters $parameters */
        $parameters = $this->container->get(Parameters::class);
        verify($parameters->has('a'))->true();
        verify($parameters->get('a'))->same(1);
        verify($parameters->has('new_parameter'))->true();
        verify($parameters->get('new_parameter'))->same(2);
    }

    // tests

    public function testSerialization()
    {
        $this->container = unserialize(serialize($this->container));
        $this->testEmptyContainer();
    }

    public function testEmptyContainer()
    {
        $container = $this->container;

        verify($container)->isInstanceOf(Container::class);
        verify($container->has(Container::class))->true();
        verify($container->get(Container::class))->same($container->get(Container::class));

        verify($container->has(Aliases::class))->true();
        verify($container->get(Aliases::class))->isInstanceOf(Aliases::class);
        verify($container->get('aliases'))->isInstanceOf(Aliases::class);

        verify($container->get('aliases'))->same($container->get(Aliases::class));

        verify($container->get('parameters'))->isInstanceOf(Parameters::class);
        verify($container->get(Parameters::class))->isInstanceOf(Parameters::class);
        verify($container->has(Parameters::class))->true();

        verify($container->has(Tags::class))->true();
        verify($container->get(Tags::class))->isInstanceOf(Tags::class);
        verify($container->get('tags'))->isInstanceOf(Tags::class);
        verify($container->has(Tags::class))->true();

        verify($container->has('Unknown class name'))->false();

        verify($container->has(Container::class))->true();
        verify($container->get(Container::class))->isInstanceOf(Container::class);
        verify($container->get(Container::class))->same($container->get(Container::class));

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->specify('Check ServiceNotFound exception (1)', function () {
            $this->assertThrows(ServiceNotFoundException::class, function () {
                $this->container->get('Unknown class name');
            });
        });

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->specify('Check CollectionNotFound exception (1)', function () {
            $this->assertThrows(CollectionNotFoundException::class, function () {
                $this->container->getCollection('Unknown class name');
            });
        });

        /** @var Aliases $aliases */
        $aliases = $this->container->get(Aliases::class);
        $aliases->add('A', 'B');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->specify('Check ServiceNotFound exception (2)', function () {
            $this->assertThrows(ServiceNotFoundException::class, function () {
                $this->container->get('A');
            });
        });
    }

    public function testProviderA()
    {
        $this->container->addProvider(ProviderA::class);

        $this->specify('Provider A is exists, but not is collection or service.', function () {
            verify($this->container->hasProvider(ProviderA::class))->true();
            verify($this->container->hasCollection(ProviderA::class))->false();
            verify($this->container->has(ProviderA::class))->false();
            verify($this->container->get(Parameters::class)->get('isBootCalled'))->true();
        });

        $this->specify('Check ServiceNotFound exception (3).', function () {
            $this->assertThrows(ServiceNotFoundException::class, function () {
                $this->container->get(ProviderA::class);
            });
        });

        $this->specify('Check CollectionNotFound exception (2)', function () {
            $this->assertThrows(CollectionNotFoundException::class, function () {
                $this->container->getCollection(ProviderA::class);
            });
        });

        $this->specify('Check DuplicateProvider exception (2)', function () {
            $this->assertThrows(DuplicateProviderException::class, function () {
                $this->container->addProvider(ProviderA::class);
            });
        });

        $this->__testProviderA(ProviderA::class);
    }

    public function __testProviderA($class)
    {
        $this->specify('Service A1 is exists and it have only one instance..', function () {
            verify($this->container->has(ServiceA1::class))->true();
            verify($this->container->get(ServiceA1::class))->isInstanceOf(ServiceA1::class);
            verify($this->container->get(ServiceA1::class))->same($this->container->get(ServiceA1::class));
            verify($this->container->hasCollection(ServiceA1::class))->false();

            $this->assertThrows(CollectionNotFoundException::class, function () {
                $this->container->getCollection(ServiceA3::class);
            });
        });

        $this->specify('Service A2 exists, it is collection and have two instances.', function () use ($class) {
            $name = $class . '::getServiceA1';
            verify($this->container->has($name))->true();
            verify($this->container->get($name))->isInstanceOf(ServiceA1::class);
            verify($this->container->get($name))->same($this->container->get(ServiceA1::class));

            verify($this->container->hasCollection(ServiceA1::class))->false();

            verify($this->container->has(ServiceA2::class))->true();
            verify($this->container->get(ServiceA2::class))->isInstanceOf(ServiceA2::class);

            $name1 = $class . '::getServiceA2_1';
            verify($this->container->has($name1))->true();
            verify($this->container->get($name1))->isInstanceOf(ServiceA2::class);
            verify($this->container->get($name1))->notSame($this->container->get(ServiceA2::class));

            $name2 = $class . '::getServiceA2_2';
            verify($this->container->has($name2))->true();
            verify($this->container->get($name2))->isInstanceOf(ServiceA2::class);
            verify($this->container->get($name2))->same($this->container->get(ServiceA2::class));

            verify($this->container->hasCollection('t2'))->true();
            verify($this->container->hasCollection(ServiceA2::class))->true();
            verify($this->container->getCollection(ServiceA2::class))->isInstanceOf(Collection::class);

            foreach ($this->container->getCollection(ServiceA2::class) as $index => $service) {
                verify($this->container->get(${'name' . ($index + 1)}))->same($service);
            }
        });

        $this->specify('Service A3 is factory and require one argument for construct', function () {
            verify($this->container->has(ServiceA3::class))->true();
            verify($this->container->hasCollection(ServiceA3::class))->false();

            /** @var ServiceA3 $alpha */
            $alpha = $this->container->get(ServiceA3::class, 'alpha');
            /** @var ServiceA3 $beta */
            $beta = $this->container->get(ServiceA3::class, 'beta');

            verify($alpha)->isInstanceOf(ServiceA3::class);
            verify($alpha->getValue())->same('alpha');
            verify($beta)->isInstanceOf(ServiceA3::class);
            verify($beta->getValue())->same('beta');
            verify($beta)->notSame($alpha);

            $this->assertThrows(CollectionNotFoundException::class, function () {
                $this->container->getCollection(ServiceA3::class);
            });
        });

        $this->specify('Service A4 hasTag access to service A1', function () {
            verify($this->container->has(ServiceA4::class))->true();
            verify($this->container->get(ServiceA4::class))->isInstanceOf(ServiceA4::class);

            /** @var ServiceA4 $service */
            $service = $this->container->get(ServiceA4::class);
            verify($service->getServiceA1())->isInstanceOf(ServiceA1::class);
        });

        $this->specify('Extend service of class ServiceA5 to class ServiceA6', function () {
            verify($this->container->has(ServiceA5::class))->true();
            /** @var ServiceA6 $service */
            $service = $this->container->get(ServiceA5::class);
            verify($service)->isInstanceOf(ServiceA5::class);
            verify($service)->isInstanceOf(ServiceA6::class);
            verify($service->getValue5())->same('v5');
            verify($service->getValue6())->same('v6');
        });

        $this->specify('Options was extended (have new parameter)', function () {
            /** @var Parameters $parameters */
            $parameters = $this->container->get(Parameters::class);
            verify($parameters->has('new_parameter'))->true();
            verify($parameters->get('new_parameter'))->same(1);
        });

        $this->specify('Aliasses was extended (alias "a1" is exists)', function () {
            verify($this->container->has('a1'))->true();
            verify($this->container->get('a1'))->isInstanceOf(ServiceA1::class);
        });

        $this->specify('Set property of ServiceA7 from Options', function () {
            /** @var ServiceA7 $service */
            $service = $this->container->get(ServiceA7::class);
            verify($service)->isInstanceOf(ServiceA7::class);
            verify($service->getValue7())->same(7);
        });

        $this->specify('Set property of ServiceA7 by tag', function () use ($class) {
            $name1 = $class . '::getServiceA2_1';
            $name2 = $class . '::getServiceA2_2';

            /** @var ServiceA7 $service */
            $service = $this->container->get(ServiceA7::class);
            verify($service)->isInstanceOf(ServiceA7::class);
            verify($service->getCollection())->same([$this->container->get($name1), $this->container->get($name2)]);
        });

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertThrows(ServiceNotFoundException::class, function () {
            $this->container->get('a2');
        });
    }

    public function testProviderB()
    {
        $this->specify('Provider methods can not have a scalar parameter', function () {
            $this->assertThrows(BadMethodDefinitionException::class, function () {
                $this->container->addProvider(ProviderB::class);
            });
        });
    }

    public function testProviderC()
    {
        $this->specify('Provider methods can not return a scalar parameter', function () {
            $this->assertThrows(BadMethodDefinitionException::class, function () {
                $this->container->addProvider(ProviderC::class);
            });
        });
    }

    public function testProviderD()
    {
        $this->specify('Provider methods can not was void and does not have any parameters', function () {
            $this->assertThrows(BadMethodDefinitionException::class, function () {
                $this->container->addProvider(ProviderD::class);
            });
        });
    }

    public function testProviderE()
    {
        $this->specify('Provider services can not override original final service (1)', function () {
            $this->assertThrows(FinalException::class, function () {
                $this->container->addProvider(ProviderE::class);
            });
        });
    }

    public function testProviderF()
    {
        $this->specify('Provider services can not override original final service (2)', function () {
            $this->assertThrows(FinalException::class, function () {
                $this->container->addProvider(ProviderF::class);
            });
        });
    }

    public function testProviderG()
    {
        $this->specify('Provider services can not override original final service (3)', function () {
            $this->assertThrows(FinalException::class, function () {
                $this->container->addProvider(ProviderG::class);
            });
        });
    }

    public function testProviderH()
    {
        $this->beforeSpecify(function () {
            $this->container = new Container();
        });

        $this->specify('Provider services can not use recursion calls', function () {
            $this->assertThrows(RecursionDetectedException::class, function () {
                $this->container->addProvider(ProviderH::class);
                $this->container->get(ServiceH1::class);
            });
        });

        $this->specify('Provider too late for extends', function () {
            $this->assertThrows(TooLateForExtendException::class, function () {
                verify($this->container->get(Aliases::class))->isInstanceOf(Aliases::class);
                $this->container->addProvider(ProviderH::class);
            });
        });

        $this->specify('Exception in factory', function () {
            $this->assertThrows(FactoryException::class, function () {
                $this->container->addProvider(ProviderH::class);
                $this->container->get(ServiceH3::class);
            });
        });

        $this->specify('Check exception "Bad Instance"', function () {
            $this->assertThrows(BadInstanceException::class, function () {
                $this->container->addProvider(ProviderH::class);
                $this->container->get(ServiceH4::class);
            });
        });

        $this->specify('Check creation of decorators.', function () {
            $this->container->addProvider(ProviderH::class);

            /** @var ServiceH6 $service */
            $service = $this->container->get(ServiceH5::class);
            verify($service)->isInstanceOf(ServiceH6::class);
            verify($service->value)->same(1);

            /** @var ServiceH8 $service */
            $service = $this->container->get(ServiceH7::class);
            verify($service)->isInstanceOf(ServiceH8::class);
            verify($service->value)->same(1);
        });
    }

    public function testProviderI()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertThrows(MixedException::class, function () {
            $this->container->addProvider(ProviderI::class);
        });
    }

    public function testContainerFromConfiguration()
    {
        $this->specify('Container from services1.json', function () {
            $this->container = new Container(Parameters::fromFile(__DIR__ . '/../_data/services1.json'));
            $this->__testProviderA('configuration');
        });

        $this->specify('Container from services2.json', function () {
            $this->container = new Container();
            $configuration = Parameters::fromFile(__DIR__ . '/../_data/services2.json');
            $provider = Provider::fromConfiguration('configuration', $configuration);
            $this->container->addProvider($provider);
            $this->__testProviderA('configuration');
        });
    }

    public function testContainerFromExtendedConfiguration()
    {
        $this->container = new Container(Parameters::fromFile(__DIR__ . '/../_data/services3.json'));

        $this->specify('Test service with rule "each"', function () {
            verify($this->container->has('ServiceTestEachRule'))->true();
            /** @var \ArrayObject $service */
            $service = $this->container->get('ServiceTestEachRule');
            verify($service)->isInstanceOf(\ArrayObject::class);
            $serviceA1 = $this->container->get(ServiceA1::class);
            $serviceA2 = $this->container->get(ServiceA2::class);
            verify($service->getArrayCopy())->same([$serviceA1, $serviceA2]);
        });

        $this->specify('Test service with rule "properties"', function () {
            /** @var ServiceA3 $service */
            $service = $this->container->get(ServiceA3::class);
            verify($service->value)->same(3);
        });
    }

    protected function setUp()
    {
        $this->container = new Container();
    }
}