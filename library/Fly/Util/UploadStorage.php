<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Util;

class UploadStorage
{
    protected $resolves = array();
    protected $defaultResolve;

    public function __construct($defaultBasePath, $defaultBaseUrl)
    {
        $this->registerResolve($defaultBasePath, $defaultBaseUrl);
    }

    /**
     * @param string $basePath
     * @param string $baseUrl
     * @param null|string $type of upload
     * @param string $ext allow extension
     * @return $this
     */
    public function registerResolve($basePath, $baseUrl, $type = null, $ext = ".*")
    {
        $def = array(
            'ext' => $ext,
            'path' => self::normalize($basePath),
            'url' => self::normalize($baseUrl)
        );
        if ($type == null) {
            $this->defaultResolve = $def;
        } else {
            $this->resolves[$type] = $def;
        }
        return $this;
    }

    protected function resolve($name, $type)
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($type) {
            if (isset($this->resolves[$type])) {
                $resolve = $this->resolves[$type];
            } else {
                throw new Exception\RuntimeException('Unknow type of upload.', 10);
            }
        } else {
            $resolve = $this->defaultResolve;
        }

        if (!preg_match('/^('.$resolve['ext'].')$/i', $ext)) {
            throw new Exception\RuntimeException('Extension "'.$ext.'" is not allowed.', 8);
        }

        if ($ext) {
            $ext = ".$ext";
        }
        $basePath = $resolve['path'];
        $baseUrl = $resolve['url'];
        $name = date('Y/md/His');
        $path = $name . $ext;
        $i = 0;
        while (file_exists($basePath . $path)) {
            $path = $name . (++$i) . $ext;
        }
        return array($path, $basePath, $baseUrl);
    }

    public function save($tmpfile, $name, $type = null)
    {
        list($path, $basePath, $baseUrl) = $this->resolve($name, $type);
        $dest = $basePath . $path;
        self::checkDirectory(dirname($dest));
        if (is_uploaded_file($tmpfile)) {
            move_uploaded_file($tmpfile, $dest);
        } else {
            rename($tmpfile, $dest);
        }

        return new UploadFile($path, $basePath, $baseUrl);
    }

    protected static function normalize($path)
    {
        $path = str_replace('\\', '/', $path);
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
        return $path;
    }

    protected static function checkDirectory($directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}

class UploadFile extends \SplFileInfo
{
    protected $basePath;
    protected $baseUrl;
    protected $path;

    public function __construct($path, $basePath, $baseUrl)
    {
        $this->path = $path;
        $this->baseUrl = $baseUrl;
        $this->basePath = $basePath;
        parent::__construct($basePath . $path);
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getUrl()
    {
        return $this->baseUrl . $this->path;
    }
}