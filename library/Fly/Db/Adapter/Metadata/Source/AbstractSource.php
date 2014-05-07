<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Metadata\Source;

use Fly\Db\Adapter\AdapterInterface;

abstract class AbstractSource
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $table
     * @param string $schema
     * @return array
     */
    abstract public function read($table, $schema);
}
