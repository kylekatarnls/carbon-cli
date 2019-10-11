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
    public $classes = [];

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
    public $sourcePath = '.';

    /**
     * @option
     *
     * Load composer.json macro config and vendor directory (config.vendor-dir setting or "vendor" by default).
     *
     * @var string
     */
    public $composer = false;

    protected function getComposerData(string $file)
    {
        return (array) (@json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY) ?: []);
    }

    protected function getCarbonMacrosFromData(array $data): array
    {
        $extra = (array) ($data['extra'] ?? []);
        $carbonExtra = (array) ($extra['carbon'] ?? []);

        return (array) ($carbonExtra['macros'] ?? []);
    }

    protected function addCarbonMacros(string $file): array
    {
        $data = $this->getComposerData($file);
        $this->classes = array_merge($this->classes, $this->getCarbonMacrosFromData($data));

        return $data;
    }

    protected function handleComposerConfig()
    {
        if ($this->composer && file_exists('composer.json')) {
            $data = $this->addCarbonMacros('composer.json');
            $config = (array) ($data['config'] ?? []);
            $vendorDirectory = $config['vendor-dir'] ?? 'vendor';

            foreach (glob($vendorDirectory.'/*/*/composer.json') as $file) {
                $this->addCarbonMacros($file);
            }
        }
    }

    public function run(SimpleCli $cli): bool
    {
        /* @var Cli $cli */

        $path = realpath($this->sourcePath);

        $this->handleComposerConfig();

        $generator = new Generator();
        $generator->writeHelpers($this->classes, $path, $this->filePrefix);

        $path .= DIRECTORY_SEPARATOR.preg_replace('`[/\\\\]`', DIRECTORY_SEPARATOR, $this->filePrefix);

        $cli->writeLine("{$path}_static.php created with static macros.");
        $cli->writeLine("{$path}_instantiated.php created with instantiated macros.");

        return true;
    }
}
