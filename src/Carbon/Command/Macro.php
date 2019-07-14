<?php

namespace Carbon\Command;

use Carbon\Cli;
use Carbon\Types\Generator;
use SimpleCli\Command;
use SimpleCli\Options\Help;
use SimpleCli\Options\Quiet;
use SimpleCli\SimpleCli;

class Macro implements Command
{
    use Help, Quiet;

    /**
     * @rest
     *
     * The class name of the mixin of the boot file to load macro functions.
     *
     * @var string[]
     */
    public $classes;

    /**
     * @option filePrefix, file-prefix, f
     *
     * The file name prefix for IDE types files.
     *
     * @var string
     */
    public $filePrefix = 'types/_ide_carbon_mixin';

    /**
     * @option sourcePath, source-path, s
     *
     * The path base of source files.
     *
     * @var string
     */
    public $sourcePath = 'src';

    public function run(SimpleCli $cli): bool
    {
        /* @var Cli $cli */

        $path = realpath($this->sourcePath);
        $generator = new Generator();
        $generator->writeHelpers($this->classes, $path, $this->filePrefix);

        $cli->writeLine("{$path}_static.php created with static macros.");
        $cli->writeLine("{$path}_instantiated.php created with instantiated macros.");

        return true;
    }
}
