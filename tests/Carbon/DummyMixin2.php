<?php

namespace Carbon\Tests;

class DummyMixin2
{
    /**
     * @return \Closure
     */
    public function sayBye()
    {
        /**
         * Say "Bye!" to a given person name.
         */
        return function (string $name): string {
            return "Bye $name!";
        };
    }
}
