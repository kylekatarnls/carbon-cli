<?php

namespace Carbon;

use Carbon\Command\Macro;
use SimpleCli\SimpleCli;

class Cli extends SimpleCli
{
    protected $name = 'carbon';

    public function getPackageName(): string
    {
        return 'nesbot/carbon';
    }

    public function getCommands(): array
    {
        return [
            'macro' => Macro::class,
        ];
    }
}
