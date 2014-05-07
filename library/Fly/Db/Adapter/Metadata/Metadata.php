<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Metadata;

use Fly\Db\Adapter\AdapterInterface;
use Fly\Cache\Storage\StorageInterface as CacheStorage;
use Fly\Db\Adapter\Exception;

class Metadata implements MetadataInterface
{

	const DEFAULT_SCHEMA = '__DEFAULT_SCHEMA__';

	/**
	 * @var AdapterInterface
	 */
	protected $adapter = null;

	/**
	 * @var string
	 */
	protected $defaultSchema = null;

	/**
	 * @var array
	 */
	protected $data = array();

    /**
     * @var Source\AbstractSource
     */
    protected $source;

	/**
	 * @var CacheStorage
	 */
	protected $cacheStorage;

	/**
	 * Constructor
	 *
	 * @param AdapterInterface $adapter
	 */
	public function __construct(AdapterInterface $adapter, CacheStorage $cacheStorage = null)
	{
		$this->adapter = $adapter;
		$this->cacheStorage = $cacheStorage;
		$this->defaultSchema = ($adapter->getDriver()->getConnection()->getCurrentSchema()) ? : self::DEFAULT_SCHEMA;
	}

	/**
	 * Get columns
	 *
	 * @param  string $table
	 * @param  string $schema
	 * @return array
	 */
	public function getColumns($table, $schema = null)
	{
		if ($schema == null) {
			$schema = $this->defaultSchema;
		}

		$key = static::normalizeKey($table, $schema);
		if ($this->hasData($key)) {
			return $this->getData($key);
		} else {
			$data = $this->load($table, $schema);
			$this->setData($key, $data);
			return $data;
		}
	}

	/**
	 * @param $table
	 * @param string $schema
	 * @return array
	 */
	public function getPrimarys($table, $schema = null)
	{
		$columns = $this->getColumns($table, $schema);
		$primary = array();
		foreach ($columns as $name => $def) {
			if ($def['PRIMARY']) {
				$primary[] = $name;
			}
		}
		return $primary;
	}

    /**
     * get source
     *
     * @return Source\AbstractSource
     */
    protected function getSource()
    {
        if ($this->source) {
            return $this->source;
        }
        switch ($this->adapter->getPlatform()->getName()) {
            case 'MySQL':
                return $this->source = new Source\Mysql($this->adapter);

        }

        throw new \Exception('cannot create source');
    }

	protected function load($table, $schema)
    {
        return $this->getSource()->read($table, $schema);
    }

	protected function getData($key)
	{
		if ($this->cacheStorage) {
			return $this->cacheStorage->get($key);
		} else {
			return $this->data[$key];
		}
	}

	protected function hasData($key)
	{
		if ($this->cacheStorage) {
			return $this->cacheStorage->has($key);
		} else {
			return isset($this->data[$key]);
		}
	}

	protected function setData($key, $data)
	{
		if ($this->cacheStorage) {
			$this->cacheStorage->set($key, $data);
		} else {
			$this->data[$key] = $data;
		}
	}

	protected static function normalizeKey($table, $schema)
	{
		return sprintf("%s.%s", $schema, $table);
	}
}
