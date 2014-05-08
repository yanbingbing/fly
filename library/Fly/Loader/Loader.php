<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Loader;

class Loader
{

    const NS_SEPARATOR = '\\';

    /**
     * @var array Namespace/directory pairs to search
     */
    protected $namespaces = array();

    /**
     * @var self
     */
    protected static $instance = null;

    protected function __construct()
    {
        $this->registerNamespace('Fly', dirname(__DIR__));

        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a namespace/directory pair
     *
     * @param  string $namespace
     * @param  string $directory
     * @return $this
     */
    public function registerNamespace($namespace, $directory)
    {
        $namespace = rtrim($namespace, self::NS_SEPARATOR) . self::NS_SEPARATOR;
        $this->namespaces[$namespace] = $this->normalizeDirectory($directory);
        return $this;
    }

    /**
     * Load a class, based on namespaced
     *
     * @param  string $class
     * @return bool|string
     * @throws Exception\InvalidArgumentException
     */
    public function loadClass($class)
    {
        // Namespace and/or prefix autoloading
        foreach ($this->namespaces as $leader => $path) {
            if (0 === strpos($class, $leader)) {
                // Trim off leader (namespace or prefix)
                $trimmedClass = substr($class, strlen($leader));

                // create filename
                $filename = $this->transformClassNameToFilename($trimmedClass, $path);
                if (file_exists($filename)) {
                    return include $filename;
                }
                return false;
            }
        }
        return false;
    }

    public function autoload($class)
    {
        if (false !== strpos($class, self::NS_SEPARATOR)) {
            return $this->loadClass($class);
        }
        return false;
    }

    protected function normalizeDirectory($directory)
    {
        $directory = str_replace('\\', '/', $directory);
        if ($directory[strlen($directory) - 1] != '/') {
            $directory .= '/';
        }
        return $directory;
    }

    protected function transformClassNameToFilename($class, $directory)
    {
        // $class may contain a namespace portion, in which case we need
        // to preserve any underscores in that portion.
        $matches = array();
        preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

        $class = (isset($matches['class'])) ? $matches['class'] : '';
        $namespace = (isset($matches['namespace'])) ? $matches['namespace'] : '';

        return $directory . str_replace(self::NS_SEPARATOR, '/', $namespace) . $class . '.php';
    }
}