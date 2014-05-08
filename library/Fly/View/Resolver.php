<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View;

use SplFileInfo;
use Traversable;

class Resolver
{
    /**
     * Suffix to use
     * Appends this suffix if the template requested does not use it.
     *
     * @var string
     */
    protected $suffix = 'phtml';

    /**
     * @var array
     */
    protected $paths = array();

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     * @var bool
     */
    protected $lfiProtectionOn = true;

    /**
     * Set file suffix
     *
     * @param  string $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = ltrim((string)$suffix, '.');
        return $this;
    }

    /**
     * Get file suffix
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Add many paths to the stack at once
     *
     * @param  array $paths
     * @return $this
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }

    /**
     * Normalize a path for insertion in the stack
     *
     * @param  string $path
     * @return string
     */
    protected static function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
        return $path;
    }

    /**
     * Add a single path to the stack
     *
     * @param  string $path
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function addPath($path)
    {
        if (!is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype($path)
            ));
        }
        $this->paths[] = static::normalizePath($path);
        return $this;
    }

    /**
     * Returns stack of paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set LFI protection flag
     *
     * @param  bool $flag
     * @return $this
     */
    public function setLfiProtection($flag)
    {
        $this->lfiProtectionOn = (bool)$flag;
        return $this;
    }

    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn()
    {
        return $this->lfiProtectionOn;
    }

    /**
     * Retrieve the filesystem path to a view script
     *
     * @param  string $name
     * @return string|bool
     * @throws Exception\RuntimeException
     * @throws Exception\DomainException
     */
    public function resolve($name)
    {
        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $name)) {
            throw new Exception\DomainException(
                'Requested scripts may not include parent directory traversal ("../", "..\\" notation)'
            );
        }

        if (empty($this->paths)) {
            throw new Exception\RuntimeException('Template paths is empty');
        }

        // Ensure we have the expected file extension
        $suffix = $this->getSuffix();
        if (pathinfo($name, PATHINFO_EXTENSION) != $suffix) {
            ;
            $name .= '.' . $suffix;
        }

        foreach ($this->paths as $path) {
            $file = new SplFileInfo($path . $name);
            if ($file->isReadable()) {
                return $file->getRealPath();
            }
        }

        return false;
    }
}