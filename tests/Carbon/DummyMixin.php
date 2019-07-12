<?php

namespace Carbon\Tests;

class DummyMixin
{
    /**
     * @return \Closure
     */
    public function sayHi()
    {
        /**
         * Say "Hi!" to a given person name.
         */
        return function (string $name): string {
            return "Hi $name!";
        };
    }
}
