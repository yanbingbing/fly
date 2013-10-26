<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Placeholder\Container;

use Fly\View\Exception;

abstract class AbstractContainer
{
	/**
	 * Whether or not to override all contents of placeholder
	 * @const string
	 */
	const SET    = 'SET';

	/**
	 * Whether or not to append contents to placeholder
	 * @const string
	 */
	const APPEND = 'APPEND';

	/**
	 * Whether or not to prepend contents to placeholder
	 * @const string
	 */
	const PREPEND = 'PREPEND';

	/**
	 * What text to prefix the placeholder with when rendering
	 * @var string
	 */
	protected $prefix    = '';

	/**
	 * What text to append the placeholder with when rendering
	 * @var string
	 */
	protected $postfix   = '';

	/**
	 * What string to use between individual items in the placeholder when rendering
	 * @var string
	 */
	protected $separator = '';

	/**
	 * Whether or not we're already capturing for this given container
	 * @var bool
	 */
	protected $captureLock = false;

	/**
	 * What type of capture (overwrite (set), append, prepend) to use
	 * @var string
	 */
	protected $captureType;


	protected $items = array();

	/**
	 * @param  array|\Traversable $options
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function setOptions($options)
	{
		if (!is_array($options) && !$options instanceof \Traversable) {
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

	/**
	 * Set a single value
	 *
	 * @param  mixed $value
	 * @return void
	 */
	public function set($value)
	{
		$this->items = array($value);
		return $this;
	}

	/**
	 * Prepend a value to the top of the container
	 *
	 * @param  mixed $value
	 * @return void
	 */
	public function prepend($value)
	{
		array_unshift($this->items, $value);
		return $this;
	}

	public function append($value)
	{
		array_push($this->items, $value);
		return $this;
	}

	/**
	 * Start capturing content to push into placeholder
	 *
	 * @param  string $type How to capture content into placeholder; append, prepend, or set
	 * @return void
	 * @throws Exception\RuntimeException if nested captures detected
	 */
	public function captureStart($type = self::APPEND)
	{
		if ($this->captureLock) {
			throw new Exception\RuntimeException(
				'Cannot nest placeholder captures for the same placeholder'
			);
		}

		$this->captureLock = true;
		$this->captureType = $type;
		ob_start();
	}

	/**
	 * End content capture
	 *
	 * @return void
	 */
	public function captureEnd()
	{
		$data = ob_get_clean();
		$this->captureLock = false;
		switch ($this->captureType) {
			case self::SET:
				$this->set($data);
				break;
			case self::PREPEND:
				$this->prepend($data);
				break;
			case self::APPEND:
			default:
				$this->append($data);
				break;
		}
	}

	/**
	 * Set prefix
	 *
	 * @param  string $prefix
	 * @return $this
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = (string) $prefix;
		return $this;
	}

	/**
	 * Retrieve prefix
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Set postfix
	 *
	 * @param  string $postfix
	 * @return $this
	 */
	public function setPostfix($postfix)
	{
		$this->postfix = (string) $postfix;
		return $this;
	}

	/**
	 * Retrieve postfix
	 *
	 * @return string
	 */
	public function getPostfix()
	{
		return $this->postfix;
	}

	/**
	 * Set separator
	 *
	 * Used to implode elements in container
	 *
	 * @param  string $separator
	 * @return $this
	 */
	public function setSeparator($separator)
	{
		$this->separator = (string) $separator;
		return $this;
	}

	/**
	 * Retrieve separator
	 *
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->separator;
	}

	/**
	 * Render the placeholder
	 *
	 * @return string
	 */
	public function toString()
	{
		$return = $this->getPrefix()
			. implode($this->getSeparator(), $this->items)
			. $this->getPostfix();
		return $return;
	}

	/**
	 * Serialize object to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}
}