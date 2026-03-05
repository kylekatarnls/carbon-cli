<?php

namespace Carbon\Tests;

trait DummyTrait2
{
    /**
     * Say "Bye!" to a given person name.
     */
    public function sayBye(string $name): string
    {
        return "Bye $name!";
    }
}
