<?php

namespace Carbon\Types;

use Carbon\Carbon;
use Closure;
use ReflectionClass;
use ReflectionParameter;

class Generator
{
    /**
     * @param (Closure|string)[] $boots
     *
     * @throws \ReflectionException
     *
     * @return mixed
     */
    protected function getMethods($boots)
    {
        foreach ($boots as $boot) {
            if (is_string($boot)) {
                if (class_exists($className = $boot)) {
                    $boot = function () use ($className) {
                        Carbon::mixin(new $className());
                    };
                } elseif (file_exists($file = $boot)) {
                    $boot = function () use ($file) {
                        include $file;
                    };
                }
            }

            call_user_func($boot);
        }

        $c = new ReflectionClass(Carbon::now());
        $macros = $c->getProperty('globalMacros');
        $macros->setAccessible(true);

        return $macros->getValue();
    }

    /**
     * @param $closure
     * @param string $source
     * @param int $sourceLength
     *
     * @return array|bool
     */
    protected function getClosureData($closure, string $source, int $sourceLength)
    {
        try {
            $function = new \ReflectionFunction($closure);
        } catch (\ReflectionException $e) {
            return false;
        }

        $file = $function->getFileName();

        if (!isset($files[$file])) {
            $files[$file] = file($file);
        }

        $lines = $files[$file];
        $file = str_replace('\\', '/', $file);

        return substr($file, 0, $sourceLength + 1) === "$source/"
            ? [$function, $file, $lines]
            : false;
    }

    /**
     * @param array $files
     * @param string $methodDocBlock
     * @param array $code
     * @param string $name
     * @param string $className
     * @param array $defaultClasses
     * @param int $length
     *
     * @return string
     */
    protected function getMethodDocBlock(array &$files, string $methodDocBlock, array $code, string $name, string $className, array $defaultClasses, int $length): string
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            if (preg_match('/^\s*(public|protected)\s+function\s+(\S+)\(.*\)(\s*\{)?$/', $code[$i], $match)) {
                if ($name !== $match[2]) {
                    try {
                        $method = new \ReflectionMethod($className, $name);
                    } catch (\ReflectionException $e) {
                        foreach ($defaultClasses as $defaultClass) {
                            try {
                                $method = new \ReflectionMethod($defaultClass, $name);

                                break;
                            } catch (\ReflectionException $e) {
                            }
                        }
                    }

                    $methodFile = $method->getFileName();

                    if (!isset($files[$methodFile])) {
                        $files[$methodFile] = file($methodFile);
                    }

                    $length = $method->getEndLine() - 1;
                    $lines = $files[$methodFile];
                    $code = array_slice($lines, 0, $length);

                    for ($i = $length - 1; $i >= 0; $i--) {
                        if (preg_match('/^\s*(public|protected)\s+function\s+(\S+)\(.*\)(\s*\{)?$/', $code[$i], $match)) {
                            break;
                        }
                    }

                    $code = implode('', array_slice($code, $i));

                    if (preg_match('/(\/\*\*[\s\S]+\*\/)\s+return\s/U', $code, $match)) {
                        $methodDocBlock = $match[1];
                    }
                }

                break;
            }
        }

        return (string) preg_replace('/^ +\*/m', '         *', $methodDocBlock);
    }

    /**
     * @param string $methods
     * @param string $methodDocBlock
     * @param string $className
     * @param string $name
     * @param string $parameters
     * @param string $file
     */
    protected function writeMethodsDoc(string &$methods, string $methodDocBlock, string $className, string $name, string $parameters, string $file): void
    {
        if ($methods !== '') {
            $methods .= "\n";
        }

        if ($methodDocBlock !== '') {
            $methodDocBlock = str_replace('/**', "/**\n         * @see $className::$name\n         *", $methodDocBlock);
            $methods .= "        $methodDocBlock\n";
        }

        $methods .= "        public static function $name($parameters)\n".
            "        {\n".
            "            // Content, see src/$file\n".
            "        }\n";
    }

    /**
     * @param string   $source
     * @param string[] $defaultClasses
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function getMethodsDefinitions($source, $defaultClasses)
    {
        $methods = '';
        $source = str_replace('\\', '/', realpath($source));
        $sourceLength = strlen($source);
        $files = array();

        foreach ($this->getMethods($defaultClasses) as $name => $closure) {
            $closureData = $this->getClosureData($closure, $source, $sourceLength);

            if ($closureData === false) {
                continue;
            }

            [$function, $file, $lines] = $closureData;
            $file = substr($file, $sourceLength + 1);
            $parameters = implode(', ', array_map(array($this, 'dumpParameter'), $function->getParameters()));
            $methodDocBlock = trim($function->getDocComment() ?: '');
            $length = $function->getStartLine() - 1;
            $code = array_slice($lines, 0, $length);
            $className = '\\'.str_replace('/', '\\', substr($file, 0, -4));

            $methodDocBlock = $this->getMethodDocBlock($files, $methodDocBlock, $code, $name, $className, $defaultClasses, $length);
            $file .= ':'.$function->getStartLine();

            $this->writeMethodsDoc($methods, $methodDocBlock, $className, $name, $parameters, $file);
        }

        return $methods;
    }

    /**
     * @param string[] $defaultClass
     * @param string   $source
     * @param string   $name
     *
     * @throws \ReflectionException
     */
    public function writeHelpers($defaultClasses, $source,  $name = 'types/_ide_carbon_mixin', array $classes = null)
    {
        $methods = $this->getMethodsDefinitions($source, $defaultClasses);

        $classes = $classes ?: [
            'Carbon\Carbon',
            'Carbon\CarbonImmutable',
            'Illuminate\Support\Carbon',
            'Illuminate\Support\Facades\Date',
        ];

        $code = "<?php\n";

        foreach ($classes as $class) {
            $class = explode('\\', $class);
            $className = array_pop($class);
            $namespace = implode('\\', $class);
            $code .= "\nnamespace $namespace\n{\n    class $className\n    {\n$methods    }\n}\n";
        }

        $directory = dirname($name);

        if (!file_exists($directory)) {
            @mkdir($directory, 0755, true);
        }

        file_put_contents("{$name}_static.php", $code);
        file_put_contents("{$name}_instantiated.php", str_replace(
            "\n        public static function ",
            "\n        public function ",
            $code
        ));
    }

    protected function dumpValue($value)
    {
        if ($value === null) {
            return 'null';
        }

        $value = preg_replace('/^array\s*\(\s*\)$/', '[]', var_export($value, true));
        $value = preg_replace('/^array\s*\(([\s\S]+)\)$/', '[$1]', $value);

        return $value;
    }

    protected function dumpParameter(ReflectionParameter $parameter)
    {
        $name = $parameter->getName();
        $output = '$'.$name;

        if ($parameter->isVariadic()) {
            $output = "...$output";
        }

        if ($parameter->getType()) {
            $name = $parameter->getType()->getName();
            if (preg_match('/^[A-Z]/', $name)) {
                $name = "\\$name";
            }
            $name = preg_replace('/^\\\\Carbon\\\\/', '', $name);
            $output = "$name $output";
        }

        try {
            if ($parameter->isDefaultValueAvailable()) {
                $output .= ' = '.$this->dumpValue($parameter->getDefaultValue());
            }
        } catch (\ReflectionException $exp) {
        }

        return $output;
    }
}
