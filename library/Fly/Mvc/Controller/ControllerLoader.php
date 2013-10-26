<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Controller;

use Fly\Mvc\Application;
use Traversable;

class ControllerLoader
{
	const NS_SEPARATOR = '\\';

	const NS_ROOT = '\\';

	protected $paths = array();

	/**
	 * Register a path of controller
	 *
	 * @param $path string|array|Traversable
	 * @param $namespace string
	 * @return $this
	 * @throws Exception\InvalidArgumentException
	 */
	public function registerPath($path, $namespace = self::NS_ROOT)
	{
		if (is_array($path) || $path instanceof Traversable) {
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
	 * Get controller instance
	 *
	 * @param $name
	 * @param Application $application
	 * @return null|AbstractController
	 * @throws Exception\RuntimeException
	 */
	public function get($name, Application $application)
	{
		$name = static::normalizeName($name);

		if (!($class = $this->getClassFromPath($name))) {
			throw new Exception\ControllerNotFoundException(sprintf('Controller "%s" is not found', $name));
		}

		if (!is_subclass_of($class, __NAMESPACE__ . '\\AbstractController')) {
			throw new Exception\BadControllerException(sprintf(
				'Class "%s" is not subclass of AbstractController', $class
			));
		}

		return new $class($application);
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
		return $name . 'Controller';
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