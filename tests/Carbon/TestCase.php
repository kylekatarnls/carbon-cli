<?php

namespace Carbon\Tests;

use PHPUnit\Framework\TestCase as FrameworkTestCase;

class TestCase extends FrameworkTestCase
{
    /**
     * Remove a directory and all sub-directories and files inside.
     *
     * @param string $directory
     *
     * @return void
     */
    protected function removeDirectory($directory)
    {
        if (!($dir = @opendir($directory))) {
            return;
        }

        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir($directory.'/'.$file)) {
                $this->removeDirectory($directory.'/'.$file);

                continue;
            }

            unlink($directory.'/'.$file);
        }

        closedir($dir);

        @rmdir($directory);
    }
}
