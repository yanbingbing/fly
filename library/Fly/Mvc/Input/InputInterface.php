<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Input;

interface InputInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * @param $key string
     * @param $val mixed
     * @return $this
     */
    public function set($key, $val);

    /**
     * @param $key string
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param $key string
     * @return bool
     */
    public function has($key);
}