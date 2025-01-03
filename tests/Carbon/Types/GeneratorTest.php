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
     * @covers ::loadFileLines
     * @covers ::getMethodSourceCode
     */
    public function testGetMethodSourceCode()
    {
        $generator = new Generator();
        $method = new ReflectionMethod(Generator::class, 'getMethodSourceCode');
        $method->setAccessible(true);

        $object = require __DIR__.'/../outside/class.php';
        // ReflectionMethod cannot throw an exception as outside/class.php is always a valid class
        /** @noinspection PhpUnhandledExceptionInspection */
        $foo = new ReflectionMethod(get_class($object), 'foo');

        $sourceCode = $method->invoke($generator, $foo);
        $match = (preg_match('/public function foo\(\)\s*\{\s*return \'hello\';/', $sourceCode) > 0);

        $this->assertTrue($match, 'Source code must contain foo method returning "hello".');
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
