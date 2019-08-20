<?php

namespace Carbon\Types;

use Carbon\Carbon;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Generator
{
    /**
     * @var string[][]
     */
    protected $files = [];

    /**
     * @param Closure|string $boot
     */
    protected function runBoot($boot): void
    {
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

    /**
     * @param (Closure|string)[] $boots
     *
     * @return mixed
     * @throws ReflectionException
     *
     */
    protected function getMethods($boots)
    {
        foreach ($boots as $boot) {
            $this->runBoot($boot);
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
            $function = new ReflectionFunction($closure);
        } catch (ReflectionException $e) {
            return false;
        }

        $file = $function->getFileName();

        if (!isset($this->files[$file])) {
            $this->files[$file] = file($file);
        }

        $lines = $this->files[$file];
        $file = str_replace('\\', '/', $file);

        return substr($file, 0, $sourceLength + 1) === "$source/"
            ? [$function, $file, $lines]
            : false;
    }

    /**
     * @param string   $className
     * @param string   $name
     * @param string[] $defaultClasses
     *
     * @return ReflectionMethod|null
     */
    protected function getReflectionMethod(string $className, string $name, array $defaultClasses): ?ReflectionMethod
    {
        array_unshift($defaultClasses, $className);

        foreach ($defaultClasses as $defaultClass) {
            try {
                return new ReflectionMethod($defaultClass, $name);
            } catch (ReflectionException $e) {
            }
        }

        return null;
    }

    /**
     * @param string   $methodDocBlock
     * @param string[] $code
     * @param int      $length
     * @param array    $methodData
     *
     * @return ReflectionMethod|null
     */
    protected function getNextMethod(array $code, int $length, array $methodData): ?ReflectionMethod
    {
        [, $className, $defaultClasses] = $methodData;

        for ($i = $length - 1; $i >= 0; $i--) {
            if (
                preg_match('/^\s*(public|protected)\s+function\s+(\S+)\(.*\)(\s*\{)?$/', $code[$i], $match) &&
                ($method = $this->getReflectionMethod($className, $match[2], $defaultClasses))
            ) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @param string $methodFile
     *
     * @return string[]
     */
    protected function loadFileLines(string $methodFile): array
    {
        if (!isset($this->files[$methodFile])) {
            $this->files[$methodFile] = file($methodFile);
        }

        return $this->files[$methodFile];
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return string
     */
    protected function getMethodSourceCode(ReflectionMethod $method): string
    {
        $length = $method->getEndLine() - 1;
        $code = array_slice($this->loadFileLines($method->getFileName()), 0, $length);

        for ($i = $length - 1; $i >= 0; $i--) {
            if (preg_match('/^\s*(public|protected)\s+function\s+(\S+)\(.*\)(\s*\{)?$/', $code[$i])) {
                break;
            }
        }

        return implode('', array_slice($code, $i));
    }

    /**
     * @param array  $files
     * @param string $methodDocBlock
     * @param array  $code
     * @param int    $length
     * @param array  $methodData
     *
     * @return string
     */
    protected function getMethodDocBlock(string $methodDocBlock, array $code, int $length, array $methodData): string
    {
        if (
            ($method = $this->getNextMethod($code, $length, $methodData)) &&
            preg_match('/(\/\*\*[\s\S]+\*\/)\s+return\s/U', $this->getMethodSourceCode($method), $match)
        ) {
            $methodDocBlock = $match[1];
        }

        return (string) preg_replace('/^ +\*/m', '         *', $methodDocBlock);
    }

    /**
     * @param string $methodDocBlock
     * @param string $className
     * @param string $prototype
     * @param string $file
     *
     * @return string
     */
    protected function getMethodDoc(string $methodDocBlock, string $className, string $prototype, string $file): string
    {
        $doc = '';

        if ($methodDocBlock !== '') {
            $methodDocBlock = str_replace('/**', "/**\n         * @see $className\n         *", $methodDocBlock);
            $doc .= "        $methodDocBlock\n";
        }

        return "$doc        public static function $prototype\n".
            "        {\n".
            "            // Content, see src/$file\n".
            "        }\n";
    }

    /**
     * @param ReflectionFunction $function
     *
     * @return string
     */
    protected function dumpReturnType(ReflectionFunction $function): string
    {
        $returnType = $function->getReturnType();

        if (!$function->hasReturnType()) {
            return '';
        }

        $returnDump = $this->getNormalizedType(
            $returnType instanceof ReflectionNamedType
                ? $returnType->getName()
                : $returnType->__toString()
        );

        return ': '.($returnType->allowsNull() ? '?' : '').$returnDump;
    }

    /**
     * @param string $source
     * @param string[] $defaultClasses
     *
     * @return string
     * @throws ReflectionException
     *
     */
    protected function getMethodsDefinitions($source, $defaultClasses)
    {
        $methods = [];
        $source = str_replace('\\', '/', realpath($source));
        $sourceLength = strlen($source);

        foreach ($this->getMethods($defaultClasses) as $name => $closure) {
            $closureData = $this->getClosureData($closure, $source, $sourceLength);

            if ($closureData === false) {
                continue;
            }

            /**
             * @var $function ReflectionFunction
             */
            [$function, $file, $lines] = $closureData;
            $file = substr($file, $sourceLength + 1);
            $parameters = implode(', ', array_map([$this, 'dumpParameter'], $function->getParameters()));
            $methodDocBlock = trim($function->getDocComment() ?: '');
            $length = $function->getStartLine() - 1;
            $code = array_slice($lines, 0, $length);
            $className = '\\'.str_replace('/', '\\', substr($file, 0, -4));

            $methodDocBlock = $this->getMethodDocBlock($methodDocBlock, $code, $length, [$name, $className, $defaultClasses]);
            $file .= ':'.$function->getStartLine();
            $return = $this->dumpReturnType($function);

            $methods[] = $this->getMethodDoc($methodDocBlock, "$className::$name", "$name($parameters)$return", $file);
        }

        return implode("\n", $methods);
    }

    /**
     * @param string[] $defaultClass
     * @param string $source
     * @param string $name
     *
     * @throws ReflectionException
     */
    public function writeHelpers($defaultClasses, $source, $name = 'types/_ide_carbon_mixin', array $classes = null)
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

    protected function getParameterName(ReflectionParameter $parameter)
    {
        $name = $parameter->getName();
        $output = '$'.$name;

        if ($parameter->isVariadic()) {
            $output = "...$output";
        }

        return $output;
    }

    protected function getNormalizedType(string $type)
    {
        if (preg_match('/^[A-Z]/', $type)) {
            $type = "\\$type";
        }

        return preg_replace('/^\\\\Carbon\\\\/', '', $type);
    }

    protected function getParameterNameAndType(ReflectionParameter $parameter)
    {
        $output = $this->getParameterName($parameter);

        if ($parameter->getType()) {
            $name = $this->getNormalizedType($parameter->getType()->getName());
            $output = "$name $output";
        }

        return $output;
    }

    protected function dumpParameter(ReflectionParameter $parameter)
    {
        $output = $this->getParameterNameAndType($parameter);

        if ($parameter->isDefaultValueAvailable()) {
            // getDefaultValue() cannot throw an exception as we checked isDefaultValueAvailable() first
            /** @noinspection PhpUnhandledExceptionInspection */
            $output .= ' = '.$this->dumpValue($parameter->getDefaultValue());
        }

        return $output;
    }
}
