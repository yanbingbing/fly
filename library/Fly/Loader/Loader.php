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
     * Register namespace/directory pair
     *
     * @param  string $namespace
     * @param  string|array $directories
     * @return $this
     */
    public function registerNamespace($namespace, $directories)
    {
        $namespace = rtrim($namespace, self::NS_SEPARATOR) . self::NS_SEPARATOR;
        if (!array_key_exists($namespace, $this->namespaces)) {
            $this->namespaces[$namespace] = array();
        }
        if (is_array($directories) || $directories instanceof \Traversable) {
            foreach ($directories as $directory) {
                $this->namespaces[$namespace][] = self::normalizeDirectory($directory);
            }
        } else {
            $this->namespaces[$namespace][] = self::normalizeDirectory($directories);
        }
        krsort($this->namespaces);
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
        foreach ($this->namespaces as $leader => $pathes) {
            if (0 === strpos($class, $leader)) {
                foreach ($pathes as $path) {
                    $filename = self::transformClassNameToFileName($class, $leader, $path);
                    if (($filename = stream_resolve_include_path($filename)) !== false) {
                        return include $filename;
                    }
                }
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

    protected static function normalizeDirectory($directory)
    {
        $directory = str_replace('\\', '/', $directory);
        if ($directory[strlen($directory) - 1] != '/') {
            $directory .= '/';
        }
        return $directory;
    }

    protected static function transformClassNameToFileName($className, $leader, $directory)
    {
        $lastNsPos = strrpos($className, self::NS_SEPARATOR) + 1;
        $prefixLen = strlen($leader);

        if ($lastNsPos > $prefixLen) {
            // Replacing '\' to '/' in namespace part
            $directory .= str_replace(
                self::NS_SEPARATOR,
                '/',
                substr($className, $prefixLen, $lastNsPos - $prefixLen)
            );
        }

        return $directory . substr($className, $lastNsPos) . '.php';
    }
}