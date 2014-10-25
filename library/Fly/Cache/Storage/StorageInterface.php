<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Cache\Storage;


interface StorageInterface
{
    /**
     * Set options.
     *
     * @param array|\Traversable $options
     * @return $this
     */
    public function setOptions($options);

    /**
     * @param string $key
     * @return mixed|false If key didn't exist, FALSE is returned
     */
    public function get($key);

    /**
     * @param array|string $key
     * @param mixed $value
     * @param null|int $ttl in second
     * @return bool
     */
    public function set($key, $value = null, $ttl = null);

    /**
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     * @return bool
     */
    public function remove($key);

    /**
     * @param string $key
     * @return bool
     */
    public function touch($key);

    /**
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function increment($key, $value);

    /**
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function decrement($key, $value);

}