<?php

namespace Carbon\Tests;

use Carbon\Cli;
use Carbon\Command\Macro;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Carbon\Cli
 */
class CliTest extends TestCase
{
    /**
     * @covers ::getCommands
     */
    public function testGetCommands()
    {
        $this->assertSame([
            'macro' => Macro::class,
        ], (new Cli())->getCommands());
    }

    /**
     * @covers ::getPackageName
     */
    public function testGetPackageName()
    {
        $this->assertSame('nesbot/carbon', (new Cli())->getPackageName());
    }
}
