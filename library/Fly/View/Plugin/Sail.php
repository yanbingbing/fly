<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

use Fly\MountManager\MountManager;
use Fly\Mvc\Input\Http as Input;
use Fly\View\Exception;

class Sail extends AbstractPlugin
{
    /************ configs *************/

    protected $alias = array();

    /**
     * @var string  *required*
     */
    protected $documentRootDirectory = 'APPLICATION_DIR/public/';

    protected $documentRootUrl = '/';

    /**
     * @var string relatived to $documentRootDirectory
     */
    protected $basePath = 'assets/';

    protected $cmdScript = 'cmd.js';


    /******* runtime variables *******/

    protected $required = array();

    protected $requiredCSS = array();

    protected $requiredJS = array();

    protected $captureLock;

    protected $isCmdPrepared;

    /**
     * @param  array|\Traversable|null $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * @param  array|\Traversable $options
     * @return $this
     */
    public function setOptions($options)
    {
        if (!is_array($options) && !($options instanceof \Traversable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
        $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if (!method_exists($this, $setter)) {
            throw new Exception\RuntimeException(sprintf(
                'The option "%s" does not have a matching "%s" setter method which must be defined',
                $key, $setter
            ));
        }
        $this->{$setter}($value);
    }

    public function setAlias(array $alias)
    {
        $this->alias = $alias;
    }

    /**
     * @param $dir string document root directory
     */
    public function setDocumentRootDirectory($dir)
    {
        $this->documentRootDirectory = self::normalizePath($dir);
    }

    /**
     * @param $url string like http://host/ or /
     */
    public function setDocumentRootUrl($url)
    {
        $this->documentRootUrl = self::normalizePath($url);
    }

    /**
     * @param $path string  documentRoot/assets/  $path = assets/
     */
    public function setBasePath($path)
    {
        $this->basePath = ltrim(self::normalizePath($path), '/');
    }

    public function setCmdScript($script)
    {
        $this->cmdScript = trim($script, '/');
    }

    protected function requireJS($file)
    {
        if (isset($this->required[$file])) {
            return;
        }
        $this->required[$file] = true;

        $this->resolveDepends($file);

        $this->requiredJS[] = $file;
    }

    protected function requireCSS($file)
    {
        if (isset($this->required[$file])) {
            return;
        }
        $this->required[$file] = true;

        $this->requiredCSS[] = $file;
    }

    protected function requireFile($file)
    {
        if (preg_match('~\.(css|js)(?:[?#]|$)~', $file, $m)) {
            $this->{'require'. strtoupper($m[1])}($file);
        }
    }

    protected function resolveDepends($file)
    {
        if (!$file || !($code = @file_get_contents($file))) {
            return;
        }

        $this->resolveCodeDepends($code, $file);
    }

    protected function resolveCodeDepends($code, $ref = null)
    {
        preg_replace_callback('~\brequire\s*\(\s*["\']([^\)\'"]+)["\']\s*\)\s*[;,]?~i', function ($matches) use ($ref) {
            $this->resolveDepend($matches[1], $ref);
            return '';
        }, $code);
    }

    protected function resolveDepend($id, $reffile = null)
    {
        $id = trim($id);
        if (empty($id) || !($f = $this->resolveId($id, $reffile)) || self::isAbsolute($f)) {
            return;
        }
        if (preg_match('~\.(css|js)(?:[?#]|$)~i', $f)) {
            $this->requireFile($f);
        }
    }

    protected function resolveId($id, $reffile = null)
    {
        $id = isset($this->alias[$id]) ? $this->alias[$id] : $id;
        if (self::isAbsolute($id)) {
            return $id;
        }
        if ($id[0] === '/') {
            $file = $this->documentRootDirectory . ltrim($id, '/');
        } elseif ($id[0] === '.') {
            $file = ($reffile ? self::dirname($reffile) : $this->documentRootDirectory) . $id;
        } else {
            $file = $this->documentRootDirectory . $this->basePath . $id;
        }

        $file = str_replace('/./', '/', $file);
        while (($newfile = preg_replace('~/[^/]+/\.\./~', '/', $file)) !== false && $newfile != $file) {
            $file = $newfile;
        }

        if (preg_match('/[?#]|\.\w+$|\/$/', $file)) {
            $file = preg_replace('/[?#].*$/', '', $file);
        } else {
            $file .= '.js';
        }

        return $file;
    }

    protected static function dirname($file)
    {
        if (preg_match('~/$~', $file)) {
            return $file;
        }
        return self::normalizePath(dirname($file));
    }

    protected static function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }
        return $path;
    }

    protected static function isAbsolute($path)
    {
        return preg_match('~^(?:\w{3,6}:)?//~', $path);
    }

    protected function fileToUrl($file)
    {
        if (self::isAbsolute($file)) {
            return $file;
        } else {
            return preg_replace('~^'.preg_quote($this->documentRootDirectory, '~').'~', $this->documentRootUrl, $file);
        }
    }

    protected function genConfig()
    {
        $config = array(
            'alias' => $this->alias,
            'base' => $this->documentRootUrl . $this->basePath
        );
        return 'define.config('.json_encode($config).')';
    }

    protected function getRequestPath()
    {
        /** @var Input $input */
        $input = MountManager::getInstance()->get('Input');
        return $this->resolveId(self::normalizePath($input->getBasePath()));
    }

    protected function prepareCmd()
    {
        if ($this->isCmdPrepared) {
            return;
        }
        $this->isCmdPrepared = true;
        $headScript = $this->getRenderer()->getPlaceholder()->getContainer('script:head');
        $headScript->appendFile($this->fileToUrl($this->resolveId($this->cmdScript)));
        $headScript->appendScript($this->genConfig());
    }

    protected function addContent($content, $isSource = false)
    {
        $this->requiredJS = array();
        $this->requiredCSS = array();

        if ($isSource) {
            $this->resolveCodeDepends($content, $this->getRequestPath());
        } else {
            $this->requireFile($this->resolveId($content, $this->getRequestPath()));
        }

        $placeholder = $this->getRenderer()->getPlaceholder();

        if (!empty($this->requiredCSS)) {
            $styleContainer = $placeholder->getContainer('style');

            foreach ($this->requiredCSS as $file) {
                $styleContainer->appendFile($this->fileToUrl($file));
            }
        }

        $this->prepareCmd();
        $bottomScript = $placeholder->getContainer('script:bottom');
        if (!empty($this->requiredJS)) {
            foreach ($this->requiredJS as $file) {
                $bottomScript->appendFile($this->fileToUrl($file));
            }
        }

        if ($isSource) {
            $bottomScript->appendScript($content);
        }
    }

    /**
     * Start capture action
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function captureStart()
    {
        if ($this->captureLock) {
            throw new Exception\RuntimeException('Cannot nest Script captures');
        }

        $this->captureLock = true;
        ob_start();
    }

    /**
     * End capture action and store
     *
     * @return void
     */
    protected function captureEnd()
    {
        $code = ob_get_clean();
        $this->captureLock = false;

        $this->addContent($code, true);
    }

    /**
     * @param null|string $main
     * @return $this
     */
    public function __invoke($main = null)
    {
        if ($main == null) {
            if ($this->captureLock) {
                $this->captureEnd();
            } else {
                $this->captureStart();
            }
            return $this;
        }

        if ($this->captureLock) {
            $this->captureEnd();
        }

        $this->addContent($main);

        return $this;
    }
}