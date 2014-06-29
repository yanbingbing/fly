<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Placeholder;

use Fly\View\Exception;
use Fly\View\Placeholder\Container\AbstractContainer;

class Placeholder
{

    /**
     * @var array
     */
    protected $placeholderDeclares = array();

    /**
     * @var AbstractContainer[]
     */
    protected $placeholderContainers = array();

    /**
     * @var array
     */
    protected $containerClass = array();

    /**
     * @var array
     */
    protected $configs = array();

    public function __construct()
    {
        foreach (array(
            'script' => __NAMESPACE__ . '\Container\Script',
            'style' => __NAMESPACE__ . '\Container\Style'
        ) as $name => $class) {
            $this->registerContainerClass($name, $class);
        }
    }

    /**
     * Register a class of name
     *
     * @param string $name
     * @param string $class
     * @return $this
     */
    public function registerContainerClass($name, $class)
    {
        $this->containerClass[$name] = $class;
        return $this;
    }

    /**
     * @param string string $name
     * @throws Exception\RuntimeException
     */
    public function declarePlaceholder($name)
    {
        $name = self::normalizeName($name);
        if (in_array($name, $this->placeholderDeclares)) {
            throw new Exception\RuntimeException("Placeholder '$name' has been declared.");
        }
        ob_start();
        $this->placeholderDeclares[ob_get_level()] = $name;
    }

    /**
     * @param array|\Traversable $configs
     * @throws Exception\InvalidArgumentException
     * @return $this
     */
    public function setConfigs($configs)
    {
        if (!is_array($configs) && !($configs instanceof \Traversable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        foreach ($configs as $key => $cfg) {
            $this->configs[self::normalizeName($key)] = $cfg;
        }

        return $this;
    }

    /**
     * @param string $name
     * @return null|AbstractContainer
     */
    public function getContainer($name)
    {
        $name = self::normalizeName($name);

        if (!isset($this->placeholderContainers[$name])) {
            list($namespace) = explode(':', $name, 2);
            $class = isset($this->containerClass[$namespace])
                ? $this->containerClass[$namespace] : __NAMESPACE__ . '\\Container\\Container';
            if (!class_exists($class, true)) {
                throw new Exception\RuntimeException("Not found the Container of class '$class'");
            }
            $this->placeholderContainers[$name] = new $class;
            if (isset($this->configs[$namespace])) {
                $this->placeholderContainers[$namespace]->setOptions($this->configs[$namespace]);
            }
            if ($name !== $namespace && isset($this->configs[$name])) {
                $this->placeholderContainers[$name]->setOptions($this->configs[$name]);
            }
        }

        return $this->placeholderContainers[$name];
    }

    public function flushAtLevel($level)
    {
        if (!isset($this->placeholderDeclares[$level])) {
            return '';
        }
        $name = $this->placeholderDeclares[$level];
        unset($this->placeholderDeclares[$level]);
        if (!isset($this->placeholderContainers[$name])) {
            return '';
        }
        $container = $this->placeholderContainers[$name];
        unset($this->placeholderContainers[$name]);
        $ret = $container->toString();
        unset($container);
        return $ret;
    }

    public function clean()
    {
        $this->placeholderDeclares = array();
        $this->placeholderContainers = array();
    }


    protected static function normalizeName($name)
    {
        return strtolower(str_replace(array(' ', '-', '_', '.'), '', $name));
    }
}