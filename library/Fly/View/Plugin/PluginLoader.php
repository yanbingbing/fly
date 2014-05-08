<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

use Fly\View\Exception;
use Fly\View\Renderer\Php as Renderer;

class PluginLoader
{
    const NS_SEPARATOR = '\\';

    const NS_ROOT = '\\';

    /**
     * @var array
     */
    protected $pluginMap = array();

    /**
     * @var array
     */
    protected $paths = array();

    /**
     * @var Renderer
     */
    protected $renderer;

    public function __construct()
    {
        $this->registerPath(__DIR__, __NAMESPACE__);
    }

    /**
     * Register a path of plugin
     *
     * @param string $path
     * @param string $namespace
     * @throws Exception\InvalidArgumentException
     * @return $this
     */
    public function registerPath($path, $namespace = self::NS_ROOT)
    {
        if (is_array($path) || $path instanceof \Traversable) {
            foreach ($path as $p => $ns) {
                if (is_string($p)) {
                    $this->registerPath($p, $ns ? : $namespace);
                } else {
                    $this->registerPath($ns, $namespace);
                }
            }
            return $this;
        }
        $path = self::normalizePath($path);
        if (!is_dir($path)) {
            throw new Exception\InvalidArgumentException(sprintf('"%s" is not a directory', $path));
        }
        $this->paths[$path] = rtrim($namespace, self::NS_SEPARATOR) . self::NS_SEPARATOR;

        return $this;
    }

    /**
     * Register a plugin instance or callable
     *
     * @param $name
     * @param $plugin PluginInterface|callable
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function registerPlugin($name, $plugin)
    {
        $name = static::normalizeName($name);
        if ($plugin instanceof PluginInterface) {
            if ($this->renderer) {
                $plugin->setRenderer($this->renderer);
            }
        } elseif (!is_callable($plugin)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects an callable or instance of PluginInterface', __METHOD__
            ));
        }
        $this->pluginMap[$name] = $plugin;
        return $this;
    }

    /**
     * Set renderer
     *
     * @param  Renderer $renderer
     * @return $this
     */
    public function setRenderer(Renderer $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Retrieve renderer instance
     *
     * @return Renderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Get plugin
     *
     * @param string $name
     * @param mixed $options
     * @return null|PluginInterface
     */
    public function get($name, $options = array())
    {
        $name = static::normalizeName($name);

        if (isset($this->pluginMap[$name]) && is_callable($this->pluginMap[$name])) {
            return $this->pluginMap[$name];
        }

        if (!($class = $this->getClassFromPath($name))) {
            throw new Exception\PluginNotFoundException(sprintf('Plugin "%s" is not found', $name));
        }

        if (!is_subclass_of($class, __NAMESPACE__ . '\\PluginInterface')) {
            throw new Exception\BadPluginException(sprintf(
                'Class "%s" is not Implements the PluginInterface', $class));
        }

        /** @var $plugin PluginInterface */
        $plugin = new $class($options);

        if ($this->renderer) {
            $plugin->setRenderer($this->renderer);
        }

        return $plugin;
    }

    protected function getClassFromPath($name)
    {
        foreach ($this->paths as $path => $namespace) {
            $filename = static::transformClassNameToFilename($name, $path);
            $class = $namespace . $name;
            if (is_file($filename)) {
                include $filename;
                if (class_exists($class, false)) {
                    return $class;
                }
            }
        }

        return null;
    }

    protected static function normalizeName($name)
    {
        $name = str_replace(array('.', '-', '_'), ' ', $name);
        $name = str_replace(' ', '', ucwords($name));
        return $name;
    }

    protected static function transformClassNameToFilename($class, $path)
    {
        return $path . str_replace('\\', '/', $class) . '.php';
    }

    protected static function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
        return $path;
    }
}