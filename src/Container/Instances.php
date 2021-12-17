<?php

namespace Restfull\Container;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;

/**
 *
 */
class Instances
{

    /**
     * @var array
     */
    private $dependencies = [];

    /**
     * @var ReflectionClass|ReflectionFunction
     */
    private $reflection;

    /**
     * @param string $class
     * @param null $dependencies
     * @param bool $activeExceptions
     * @return object|null
     * @throws Exceptions
     */
    public function resolveClass(string $class, $dependencies = null, bool $activeExceptions = true): ?object
    {
        try {
            $class = $this->renameClass($class);
            if (isset($dependencies)) {
                $this->dependencies($dependencies);
            }
            if (is_string($class)) {
                $this->class($class);
            }
            if (is_object($this->reflection)) {
                if (!$this->reflection->isInstantiable()) {
                    throw new Exceptions("{$this->reflection->name} is not instanciable");
                }
                $constructor = $this->reflection->getConstructor();
                if (is_null($constructor)) {
                    return new $this->reflection->name;
                }
                return $this->reflection->newInstanceArgs($this->getDependencies($constructor->getParameters()));
            }
            return null;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            if ($activeExceptions) {
                throw new Exceptions($e, '500');
            } else {
                echo $e->getMessage();
                exit;
            }
        }
    }

    public function renameClass(string $class): string
    {
        if (stripos($class, substr(ROOT, strripos(ROOT, DS_REVERSE), -1)) !== false) {
            $class = substr($class, strlen(ROOT));
            if (stripos($class, '.php') !== false) {
                $class = substr($class, 0, -4);
            }
            if (strripos($class, DS) !== false) {
                $class = str_replace(DS, DS_REVERSE, $class);
            }
        }
        return $class;
    }

    /**
     * @param string $class
     * @return Instances
     * @throws ReflectionException
     */
    private function class(string $class): Instances
    {
        if (is_callable($class)) {
            $this->reflection = new ReflectionFunction($class);
            return $this;
        }
        $this->reflection = new ReflectionClass($class);
        return $this;
    }

    /**
     * @param array $parameters
     * @return mixed
     * @throws Exceptions
     */
    public function getDependencies(array $parameters, bool $compare = false)
    {
        $dependencies = [];
        if (count($parameters) > 0) {
            $count = [];
            $a = 0;
            $keys = array_keys($this->dependencies);
            foreach ($parameters as $parameter) {
                $dependecy = $parameter->getClass();
                if ($compare) {
                    $name = isset($dependecy) ? $dependecy->name : (isset($parameter->name) ? $parameter->name : '');
                    $count[$a] = !empty($name) ? (in_array($name, array_keys($this->dependencies)) ? 0 : 1) : 0;
                } else {
                    if (isset($dependecy)) {
                        if ($this->dependencies[$keys[$a]] instanceof $dependecy->name) {
                            $dependencies[] = $this->dependencies[$keys[$a]];
                        } else {
                            $dependencies[] = $this->resolveClass($dependecy);
                        }
                    } else {
                        $dependecy = $parameter->name;
                        if (isset($this->dependencies[$dependecy])) {
                            $dependencies[] = $this->dependencies[$dependecy];
                        } else {
                            if ($parameter->isDefaultValueAvailable()) {
                                $dependencies[] = $parameter->getDefaultValue();
                            } else {
                                throw new Exceptions("Cannot resolve unknow!");
                            }
                        }
                    }
                }
                $a++;
            }
            if ($compare) {
                $return = false;
                for ($a = 0; $a < count($count); $a++) {
                    if ($count[$a] == 1) {
                        $return = !$return;
                        break;
                    }
                }
                return $return;
            }
        }
        if ($compare) {
            return false;
        }
        return $dependencies;
    }

    /**
     * @param array $calleble
     * @param array $datas
     * @return mixed
     */
    public function callebleFunctionActive(array $calleble, array $datas)
    {
        return call_user_func_array($calleble, $datas);
    }

    /**
     * @param string $class
     * @param string $method
     * @return array
     * @throws Exceptions
     */
    public function getParameters(string $class, string $method = ''): array
    {
        try {
            $this->class($this->renameClass($class));
            if (empty($method)) {
                $newMethod = $this->reflection->getConstructor();
                if (is_null($newMethod)) {
                    return [];
                }
            } else {
                $newMethod = $this->reflection->getMethod($method);
            }
            return $newMethod->getParameters();
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     * @return array
     * @throws Exceptions
     */
    public function getMethods(string $class): array
    {
        try {
            $this->class($this->renameClass($class));
            foreach ($this->reflection->getMethods() as $method) {
                $methods[] = $method->name;
            }
            return $methods;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param callable $callback
     * @return array
     * @throws Exceptions
     */
    public function getFunction(callable $callback): array
    {
        try {
            $params = [];
            $this->class($callback);
            $parameters = $this->reflection->getParameters();
            if (!is_null($parameters)) {
                foreach ($parameters as $value) {
                    $params[] = $value->name;
                }
            }
            return $params;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     * @return string
     */
    public function extension(string $class): string
    {
        if ((new File(str_replace(DS_REVERSE, DS, str_replace(substr(ROOT_APP, -4), 'src', $class) . '.php')))->exists(
                )) {
            $class = substr(
                    str_replace(substr(ROOT_APP, -4), substr(ROOT_APP, -4, -1), $class),
                    stripos(ROOT_APP, substr(ROOT_APP, -4))
            );
        } else {
            if (stripos($class, 'Model') !== false) {
                if (stripos($class, 'Entity') !== false) {
                    $class = explode(DS, $this->path($class));
                    $newClass = explode(DS_REVERSE, $class[count($class) - 1]);
                    $newClass[count($newClass) - 1] = $newClass[count($newClass) - 2];
                    $class[count($class) - 1] = implode(DS, $newClass);
                    $class = implode(DS, $class);
                    unset($newClass);
                    $class = str_replace(MVC[2]['app'], MVC[2]['restfull'], $class);
                }
                if (file_exists($class . '.php')) {
                    $class = substr($class, stripos(RESTFULL, "vendor") + strlen("vendor" . DS));
                    $class = str_replace("rest-full/src" . DS, "Restfull" . DS_REVERSE, $class);
                    $class = str_replace(DS, DS_REVERSE, $class);
                }
            } else {
                $class = substr($class, stripos($class, substr(ROOT_APP, -4, -1)));
                $class = str_replace(substr(ROOT_APP, -4), ROOT_NAMESPACE, $class);
            }
        }

        return $class;
    }

    /**
     * @param string $format
     * @param array $args
     * @param bool $convert
     * @return string
     * @throws Exceptions
     */
    public function assemblyClassOrPath(string $format, array $args, bool $convert = false): string
    {
        if (!$convert) {
            if (stripos($format, DS) !== false) {
                $replace = str_replace(DS, DS_REVERSE, $format);
            }
            $replace = explode(DS_REVERSE, (isset($replace) ? $replace : $format));
            if (count($replace) == 1) {
                $replace = explode(', ', $replace[0]);
            }
            $count = 0;
            for ($a = 0; $a < count($replace); $a++) {
                if (stripos($replace[$a], '%s') !== false) {
                    $count++;
                }
            }
            if ($count != count($args)) {
                throw new Exceptions(
                                'The ' . $format . ' format or ' . implode(', ', $args) . ' files are not equal.',
                                404
                );
            }
        }
        return vsprintf($format, $args);
    }

    /**
     * @param array $dependencies
     * @return $this
     */
    public function dependencies(array $dependencies): Instances
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * @param string $class
     * @return object|null
     * @throws Exceptions
     */
    public function getParent(string $class): ?object
    {
        try {
            $this->class($this->renameClass($class));
            return $this->reflection->getParentClass();
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            throw new Exceptions($e, '500');
        }
    }

}
