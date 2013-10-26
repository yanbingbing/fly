<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Filter;

interface FilterInterface
{
	/**
	 * Returns the result of filtering $value
	 *
	 * @param  mixed $value
	 * @throws Exception\RuntimeException If filtering $value is impossible
	 * @return mixed
	 */
	public function filter($value);
}
