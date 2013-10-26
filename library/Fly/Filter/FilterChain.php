<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Filter;

class FilterChain extends AbstractFilter
{
	/**
	 * Default priority at which filters are added
	 */
	const DEFAULT_PRIORITY = 1000;

	protected $filters = array();

	protected $sorted = false;

	/**
	 * Attach a filter to the chain
	 *
	 * @param  callable|FilterInterface $callback A Filter implementation or valid PHP callback
	 * @param  int $priority Priority at which to enqueue filter; defaults to 1000 (higher executes earlier)
	 * @throws Exception\InvalidArgumentException
	 * @return $this
	 */
	public function attach($callback, $priority = self::DEFAULT_PRIORITY)
	{
		if (!is_callable($callback)) {
			if (!$callback instanceof FilterInterface) {
				throw new Exception\InvalidArgumentException(sprintf(
					'Expected a valid PHP callback; received "%s"',
					(is_object($callback) ? get_class($callback) : gettype($callback))
				));
			}
			$callback = array($callback, 'filter');
		}
		$this->filters[] = array($callback, $priority);
		$this->sorted = false;
		return $this;
	}

	protected function sort()
	{
		if ($this->sorted) {
			return;
		}
		uasort($this->filters, array($this, 'compare'));
		$this->sorted = true;
	}

	/**
	 * Compare the priority of two routes.
	 *
	 * @param array $filter1
	 * @param array $filter2
	 * @return int
	 */
	protected function compare(array $filter1, array $filter2)
	{
		return ($filter1[1] > $filter2[1] ? -1 : 1);
	}

	/**
	 * Returns $value filtered through each filter in the chain
	 * Filters are run in the order in which they were added to the chain (FIFO)
	 *
	 * @param  mixed $value
	 * @return mixed
	 */
	public function filter($value)
	{
		$this->sort();
		$valueFiltered = $value;
		foreach ($this->filters as $filter) {
			$valueFiltered = call_user_func($filter[0], $valueFiltered);
		}

		return $valueFiltered;
	}
}
