<?php

use Moro\Container7\Definition;
use Moro\Container7\Test\ProviderA;

/**
 * Class DefinitionTest
 */
class DefinitionTest extends \PHPUnit\Framework\TestCase
{
    use Codeception\Specify;
    use Codeception\AssertThrows;

    // tests
    public function test()
    {
        $this->specify('A', function () {
            $this->assertThrows(\AssertionError::class, function () {
                $definition = new Definition(new ProviderA());
                $definition->addSingleton('', 'getSomething');
            });
        });

        $this->specify('B', function () {
            $this->assertThrows(\AssertionError::class, function () {
                $definition = new Definition(new ProviderA());
                $definition->addFactory('', 'getSomething');
            });
        });

        $this->specify('C', function () {
            $this->assertThrows(\AssertionError::class, function () {
                $definition = new Definition(new ProviderA());
                $definition->addTuner('Interface', '');
            });
        });

        $definition = new Definition(new ProviderA());
        verify($definition->addSingleton('Interface', 'getSomething'))->same($definition);

        $definition = new Definition(new ProviderA());
        verify($definition->addFactory('Interface', 'getSomething'))->same($definition);

        $definition = new Definition(new ProviderA());
        verify($definition->addTuner('Interface', 'getSomething'))->same($definition);

//        $definition = new Definition(new ProviderA());
//        verify($definition->setId('A'))->same($definition);

        verify(Definition::fromObject(new ProviderA()))->isInstanceOf(Definition::class);
    }
}