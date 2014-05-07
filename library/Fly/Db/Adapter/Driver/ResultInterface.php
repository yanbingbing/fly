<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Driver;

interface ResultInterface extends \Countable, \Iterator
{
    /**
     * Force buffering
     *
     * @return void
     */
    public function buffer();

    /**
     * Check if is buffered
     *
     * @return bool|null
     */
    public function isBuffered();

    /**
     * Is query result?
     *
     * @return bool
     */
    public function isQueryResult();

    /**
     * Get affected rows
     *
     * @return int
     */
    public function getAffectedRows();

    /**
     * Get generated value
     *
     * @return mixed|null
     */
    public function getGeneratedValue();

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Get field count
     *
     * @return int
     */
    public function getFieldCount();
}
