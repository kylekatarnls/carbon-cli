<?php

namespace Carbon\Tests;

use Carbon\Carbon;
use Carbon\Types\Generator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @coversDefaultClass \Carbon\Types\Generator
 */
class GeneratorTest extends TestCase
{
    /**
     * @covers ::getClosureData
     */
    public function testGetClosureData()
    {
        $generator = new Generator();
        $method = new ReflectionMethod(Generator::class, 'getClosureData');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($generator, 'doesNotExist', 'foobar', 2));
    }

    /**
     * @covers ::getReflectionMethod
     */
    public function testGetReflectionMethod()
    {
        $generator = new Generator();
        $method = new ReflectionMethod(Generator::class, 'getReflectionMethod');
        $method->setAccessible(true);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $method->invoke($generator, 'DoesNotExist', 'create', [Carbon::class]);

        $this->assertInstanceOf(ReflectionMethod::class, $reflectionMethod);
        $this->assertSame('create', $reflectionMethod->getName());
        $this->assertSame(Carbon::class, $reflectionMethod->getDeclaringClass()->getName());
        $this->assertNull($method->invoke($generator, 'DoesNotExist', 'create', ['NeitherThisOne']));
    }

    /**
     * @covers ::getNextMethod
     */
    public function testGetNextMethod()
    {
        $generator = new Generator();
        $method = new ReflectionMethod(Generator::class, 'getNextMethod');
        $method->setAccessible(true);
        $code = '
            class Foo
            {
                /**
                 * Description.
                 */
                public function goGo()
                {
                    return function () {
                    };
                }
            }
        ';
        eval($code);
        $lines = explode("\n", $code);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $method->invoke($generator, $lines, 8, ['goGo', 'Foo', []]);

        $this->assertInstanceOf(ReflectionMethod::class, $reflectionMethod);
        $this->assertSame('goGo', $reflectionMethod->getName());
        $this->assertSame('Foo', $reflectionMethod->getDeclaringClass()->getName());
        $this->assertStringContainsString('* Description.', $reflectionMethod->getDocComment());
    }

    /**
     * @covers ::dumpValue
     */
    public function testDumpValue()
    {
        $generator = new Generator();
        $method = new ReflectionMethod(Generator::class, 'dumpValue');
        $method->setAccessible(true);

        $this->assertSame('null', $method->invoke($generator, null));
        $this->assertSame('[]', $method->invoke($generator, []));
        $this->assertSame("['foo'=>'bar']", preg_replace('/[,\s]/', '', $method->invoke($generator, ['foo' => 'bar'])));
    }
}
