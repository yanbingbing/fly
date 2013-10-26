<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Config;

use ArrayAccess;
use Countable;
use Iterator;
use Traversable;

class Config implements Countable, Iterator, ArrayAccess
{
	/**
	 * Number of elements in configuration data.
	 *
	 * @var integer
	 */
	protected $count;

	/**
	 * Data withing the configuration.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Used when unsetting values during iteration to ensure we do not skip
	 * the next element.
	 *
	 * @var bool
	 */
	protected $skipNextIteration;

	/**
	 * @var Reader\ReaderInterface[]
	 */
	protected static $reader = array();

	/**
	 * @param string $type
	 * @return null|Reader\ReaderInterface
	 */
	public static function getReader($type)
	{
		$type = strtolower($type);
		if (isset(self::$reader[$type])) {
			return self::$reader[$type];
		}
		$readerClass = __NAMESPACE__ . '\\Reader\\' . ucfirst($type);
		if (!class_exists($readerClass, true)) {
			return null;
		}
		$reader = new $readerClass;
		if (!$reader instanceof Reader\ReaderInterface) {
			return null;
		}
		self::$reader[$type] = $reader;
		return $reader;
	}

	/**
	 * Constructor.
	 *
	 * @param  array|string|self $data
	 */
	public function __construct($data = null)
	{
		if ($data) {
			if (is_array($data) || $data instanceof Traversable) {
				$this->merge($data);
			} elseif (is_string($data)) {
				$this->load($data);
			} else {
				throw new Exception\InvalidArgumentException("Argument data must be an array or an string filename");
			}
		}
	}

	/**
	 * @param $filename string
	 * @return Config
	 * @throws Exception\RuntimeException
	 */
	public function load($filename)
	{
		$pathinfo = pathinfo($filename);

		if (!isset($pathinfo['extension'])) {
			throw new Exception\RuntimeException(sprintf(
				'Filename "%s" is missing an extension and cannot be auto-detected',
				$filename
			));
		}

		$extension = strtolower($pathinfo['extension']);

		if ($extension === 'php') {
			if (!is_file($filename) || !is_readable($filename)) {
				throw new Exception\RuntimeException(sprintf("File '%s' doesn't exist or not readable", $filename));
			}

			$config = include $filename;
		} elseif (($reader = self::getReader($extension))) {
			$config = $reader->fromFile($filename);
		} else {
			throw new Exception\RuntimeException(sprintf('Unsupported config file extension: .%s',
				$pathinfo['extension']));
		}

		return $this->merge($config);
	}

	/**
	 * Retrieve a value and return $default if there is no element set.
	 *
	 * @param  string $name
	 * @param  mixed $default
	 * @return mixed
	 */
	public function get($name, $default = null)
	{
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		return $default;
	}

	/**
	 * Magic function so that $obj->value will work.
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Set a value in the config.
	 * Only allow setting of a property if $allowModifications  was set to true
	 * on construction. Otherwise, throw an exception.
	 *
	 * @param  string $name
	 * @param  mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (is_array($value)) {
			$value = new static($value, true);
		}

		if (null === $name) {
			$this->data[] = $value;
		} else {
			$this->data[$name] = $value;
		}

		$this->count++;
	}

	/**
	 * Deep clone of this instance to ensure that nested Zend\Configs are also
	 * cloned.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$array = array();

		foreach ($this->data as $key => $value) {
			if ($value instanceof self) {
				$array[$key] = clone $value;
			} else {
				$array[$key] = $value;
			}
		}

		$this->data = $array;
	}

	/**
	 * Return an associative array of the stored data.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$array = array();
		$data = $this->data;

		/** @var self $value */
		foreach ($data as $key => $value) {
			if ($value instanceof self) {
				$array[$key] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
		}

		return $array;
	}

	/**
	 * isset() overloading
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * unset() overloading
	 *
	 * @param  string $name
	 * @return void
	 */
	public function __unset($name)
	{
		if (isset($this->data[$name])) {
			unset($this->data[$name]);
			$this->count--;
			$this->skipNextIteration = true;
		}
	}

	/**
	 * count(): defined by Countable interface.
	 *
	 * @see    Countable::count()
	 * @return integer
	 */
	public function count()
	{
		return $this->count;
	}

	/**
	 * current(): defined by Iterator interface.
	 *
	 * @see    Iterator::current()
	 * @return mixed
	 */
	public function current()
	{
		$this->skipNextIteration = false;
		return current($this->data);
	}

	/**
	 * key(): defined by Iterator interface.
	 *
	 * @see    Iterator::key()
	 * @return mixed
	 */
	public function key()
	{
		return key($this->data);
	}

	/**
	 * next(): defined by Iterator interface.
	 *
	 * @see    Iterator::next()
	 * @return void
	 */
	public function next()
	{
		if ($this->skipNextIteration) {
			$this->skipNextIteration = false;
			return;
		}

		next($this->data);
	}

	/**
	 * rewind(): defined by Iterator interface.
	 *
	 * @see    Iterator::rewind()
	 * @return void
	 */
	public function rewind()
	{
		$this->skipNextIteration = false;
		reset($this->data);
	}

	/**
	 * valid(): defined by Iterator interface.
	 *
	 * @see    Iterator::valid()
	 * @return bool
	 */
	public function valid()
	{
		return ($this->key() !== null);
	}

	/**
	 * offsetExists(): defined by ArrayAccess interface.
	 *
	 * @see    ArrayAccess::offsetExists()
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->__isset($offset);
	}

	/**
	 * offsetGet(): defined by ArrayAccess interface.
	 *
	 * @see    ArrayAccess::offsetGet()
	 * @param  mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	/**
	 * offsetSet(): defined by ArrayAccess interface.
	 *
	 * @see    ArrayAccess::offsetSet()
	 * @param  mixed $offset
	 * @param  mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	/**
	 * offsetUnset(): defined by ArrayAccess interface.
	 *
	 * @see    ArrayAccess::offsetUnset()
	 * @param  mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		$this->__unset($offset);
	}

	/**
	 * Merge another Config with this one.
	 *
	 * @param  array|Traversable $merge
	 * @return Config
	 */
	public function merge($merge)
	{
		if (!is_array($merge) || $merge instanceof Traversable) {
			throw new Exception\InvalidArgumentException(sprintf(
				'%s: expects an array, or Traversable argument; received "%s"',
				__METHOD__, (is_object($merge) ? get_class($merge) : gettype($merge))
			));
		}

		foreach ($merge as $key => $value) {
			if (array_key_exists($key, $this->data)) {
				if (is_int($key)) {
					$this->data[] = $value;
				} elseif ($value instanceof self && $this->data[$key] instanceof self) {
					$this->data[$key]->merge($value);
				} else {
					if ($value instanceof self) {
						$this->data[$key] = new static($value->toArray());
					} else {
						$this->data[$key] = $value;
					}
				}
			} else {
				if ($value instanceof self) {
					$this->data[$key] = new static($value->toArray());
				} else {
					$this->data[$key] = $value;
				}

				$this->count++;
			}
		}

		return $this;
	}
}
