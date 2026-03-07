<?php

namespace Carbon\Tests;

trait DummyTrait
{
    /**
     * Say "Hi!" to a given person name.
     */
    public function sayHi(string $name): string
    {
        return "Hi $name!";
    }
}
