<?php

namespace Carbon\Tests\Command;

use Carbon\Cli;
use Carbon\Tests\DummyMixin;
use Carbon\Tests\DummyMixin2;
use Carbon\Tests\SubMacro;
use Carbon\Tests\TestCase;

/**
 * @coversDefaultClass \Carbon\Command\Macro
 */
class MacroTest extends TestCase
{
    /**
     * @covers ::run
     * @covers \Carbon\Types\Generator::runBoot
     * @covers \Carbon\Types\Generator::getClosureData
     * @covers \Carbon\Types\Generator::getMethodDocBlock
     * @covers \Carbon\Types\Generator::getMethodDoc
     * @covers \Carbon\Types\Generator::getMethods
     * @covers \Carbon\Types\Generator::getMethodsDefinitions
     * @covers \Carbon\Types\Generator::getParameterName
     * @covers \Carbon\Types\Generator::getParameterNameAndType
     * @covers \Carbon\Types\Generator::dumpParameter
     * @covers \Carbon\Types\Generator::dumpValue
     * @covers \Carbon\Types\Generator::getReflectionMethod
     * @covers \Carbon\Types\Generator::getNextMethod
     * @covers \Carbon\Types\Generator::loadFileLines
     * @covers \Carbon\Types\Generator::getMethodSourceCode
     * @covers \Carbon\Types\Generator::writeHelpers
     */
    public function testRun()
    {
        $dir = sys_get_temp_dir().'/macro-test-'.mt_rand(0, 999999);
        @mkdir($dir);
        chdir($dir);
        $cli = new Cli();
        $cli->mute();
        $cli('carbon', 'macro', DummyMixin::class, DummyMixin2::class, '--source-path', __DIR__.'/..');

        $this->assertSame([
            '.',
            '..',
            'types',
        ], scandir($dir));
        $this->assertSame([
            '.',
            '..',
            '_ide_carbon_mixin_instantiated.php',
            '_ide_carbon_mixin_static.php',
        ], scandir("$dir/types"));
        $this->assertFileEquals(__DIR__.'/_ide_carbon_mixin_instantiated.php', "$dir/types/_ide_carbon_mixin_instantiated.php");
        $this->assertFileEquals(__DIR__.'/_ide_carbon_mixin_static.php', "$dir/types/_ide_carbon_mixin_static.php");

        $this->removeDirectory($dir);
    }

    /**
     * @covers ::run
     * @covers \Carbon\Types\Generator::runBoot
     * @covers \Carbon\Types\Generator::getClosureData
     * @covers \Carbon\Types\Generator::getMethodDocBlock
     * @covers \Carbon\Types\Generator::getMethodDoc
     * @covers \Carbon\Types\Generator::getMethods
     * @covers \Carbon\Types\Generator::getMethodsDefinitions
     * @covers \Carbon\Types\Generator::getParameterName
     * @covers \Carbon\Types\Generator::getParameterNameAndType
     * @covers \Carbon\Types\Generator::dumpParameter
     * @covers \Carbon\Types\Generator::dumpValue
     * @covers \Carbon\Types\Generator::getReflectionMethod
     * @covers \Carbon\Types\Generator::getNextMethod
     * @covers \Carbon\Types\Generator::loadFileLines
     * @covers \Carbon\Types\Generator::getMethodSourceCode
     * @covers \Carbon\Types\Generator::writeHelpers
     */
    public function testRunWithFile()
    {
        $dir = sys_get_temp_dir().'/macro-test-'.mt_rand(0, 999999);
        @mkdir($dir);
        chdir($dir);
        file_put_contents('test.php', '<?php \Carbon\Carbon::macro(\'foo\', function () { return 42; });');
        $cli = new Cli();
        $cli->mute();
        $cli('carbon', 'macro', 'test.php');

        $contents = file_get_contents("$dir/types/_ide_carbon_mixin_instantiated.php");
        $this->assertStringContainsString('public function foo()', $contents);

        $this->removeDirectory($dir);
    }

    /**
     * @covers ::getComposerData
     * @covers ::getCarbonMacrosFromData
     * @covers ::addCarbonMacros
     * @covers ::handleComposerConfig
     */
    public function testHandleComposerConfig()
    {
        $macro = new SubMacro();
        chdir(__DIR__.'/../app1');
        $macro->triggerComposerConfigHandle();

        $this->assertSame([
            'NS5\Class5',
            'NS6\Class6',
        ], $macro->getClasses());

        $macro = new SubMacro();
        chdir(__DIR__.'/../app2');
        $macro->triggerComposerConfigHandle();

        $this->assertSame([
            'NS4\Class4',
            'NS2\Class2',
            'NS3\Class3',
            'NS1\Class1',
        ], $macro->getClasses());
    }
}
