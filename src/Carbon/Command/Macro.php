<?php

namespace Carbon\Command;

use Carbon\Cli;
use Carbon\Types\Generator;
use SimpleCli\Command;
use SimpleCli\SimpleCli;

class Macro implements Command
{
    /**
     * @argument
     *
     * The class name of the mixin of the boot file to load macro functions.
     *
     * @var string
     */
    public $class;

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

        $generator = new Generator();
        $generator->writeHelpers($this->class, realpath($this->sourcePath), $this->filePrefix);

        $cli->write('ok');

        return true;
    }
}
