<?php

namespace Carbon\Tests;

use Carbon\Command\Macro;

class SubMacro extends Macro
{
    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    public function triggerComposerConfigHandle()
    {
        $this->composer = true;
        $this->handleComposerConfig();
    }
}
