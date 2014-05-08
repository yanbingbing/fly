<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter;

use Fly\Cache\Cache;
use Fly\Cache\Storage\StorageInterface as CacheStorage;

class Adapter implements AdapterInterface
{

    /**
     * @var Driver\DriverInterface
     */
    protected $driver;

    /**
     * @var Platform\PlatformInterface|array
     */
    protected $platform;

    /**
     * @var Metadata\MetadataInterface
     */
    protected $metadata;

    /**
     * @param Driver\DriverInterface|array $driver
     * @param Platform\PlatformInterface $platform
     */
    public function __construct($driver, Platform\PlatformInterface $platform = null)
    {
        // first argument can be an array of parameters
        $parameters = array();

        if (is_array($driver)) {
            $parameters = $driver;
            $driver = $this->createDriver($parameters);
        } elseif (!($driver instanceof Driver\DriverInterface)) {
            throw new Exception\InvalidArgumentException(
                'The supplied or instantiated driver object does not implement DriverInterface');
        }

        $driver->checkEnvironment();
        $this->driver = $driver;

        $this->platform = $platform ? : $parameters;
    }

    /**
     * Get driver
     *
     * @throws Exception\RuntimeException
     * @return Driver\DriverInterface
     */
    public function getDriver()
    {
        if ($this->driver == null) {
            throw new Exception\RuntimeException('Driver has not been set or configured for this adapter.');
        }
        return $this->driver;
    }

    /**
     * @return Platform\PlatformInterface
     */
    public function getPlatform()
    {
        if (is_array($this->platform)) {
            $this->platform = $this->createPlatform($this->platform);
        }
        return $this->platform;
    }

    /**
     * @return Metadata\MetadataInterface
     */
    public function getMetadata()
    {
        if (!$this->metadata) {
            $this->metadata = new Metadata\Metadata($this, self::getMetaCacheStorage());
        }
        return $this->metadata;
    }

    /**
     * Query
     *
     * @param string|Driver\StatementInterface $stmt
     * @param array|Parameters $parameters
     * @return Driver\ResultInterface
     */
    public function query($stmt, $parameters = null)
    {
        if (!($stmt instanceof Driver\StatementInterface)) {
            $stmt = $this->driver->createStatement($stmt);
        }

        if (is_array($parameters) || $parameters instanceof Parameters) {
            $stmt->setParameters((is_array($parameters)) ? new Parameters($parameters) : $parameters);
        }
        return $stmt->execute();
    }

    /**
     * Create statement
     *
     * @param null|string $sql
     * @param null|Parameters|array $parameters
     * @internal param array|\Fly\Db\Adapter\Parameters $initialParameters
     * @return Driver\StatementInterface
     */
    public function createStatement($sql = null, $parameters = null)
    {
        $statement = $this->driver->createStatement($sql);
        if ($parameters == null || !($parameters instanceof Parameters) && is_array($parameters)) {
            $parameters = new Parameters((is_array($parameters) ? $parameters : array()));
        }
        $statement->setParameters($parameters);
        return $statement;
    }

    /**
     * @param array $parameters
     * @return Driver\DriverInterface
     * @throws Exception\InvalidArgumentException
     */
    protected function createDriver($parameters)
    {
        if (!isset($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(
                __FUNCTION__ . ' expects a "driver" key to be present inside the parameters');
        }

        if ($parameters['driver'] instanceof Driver\DriverInterface) {
            return $parameters['driver'];
        }

        if (!is_string($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(
                __FUNCTION__ . ' expects a "driver" to be a string or instance of DriverInterface');
        }

        $options = array();
        if (isset($parameters['options'])) {
            $options = (array)$parameters['options'];
            unset($parameters['options']);
        }

        $driverName = strtolower($parameters['driver']);
        switch ($driverName) {
            case 'mysqli':
                $driver = new Driver\Mysqli\Mysqli($parameters, null, null, $options);
                break;
            case 'pdo':
            default:
                if ($driverName == 'pdo' || strpos($driverName, 'pdo') === 0) {
                    $driver = new Driver\Pdo\Pdo($parameters);
                }
        }

        if (!isset($driver) || !($driver instanceof Driver\DriverInterface)) {
            throw new Exception\InvalidArgumentException('DriverInterface expected', null, null);
        }

        return $driver;
    }

    /**
     * @param array $parameters
     * @throws Exception\InvalidArgumentException
     * @return Platform\PlatformInterface
     */
    protected function createPlatform($parameters)
    {
        if (isset($parameters['platform'])) {
            $platformName = $parameters['platform'];
        } elseif ($this->driver instanceof Driver\DriverInterface) {
            $platformName = $this->driver->getDatabasePlatformName(Driver\DriverInterface::NAME_FORMAT_CAMELCASE);
        } else {
            throw new Exception\InvalidArgumentException(
                'A platform could not be determined from the provided configuration');
        }

        switch ($platformName) {
            case 'Mysql':
                // mysqli or pdo_mysql driver
                $driver = ($this->driver instanceof Driver\Mysqli\Mysqli || $this->driver instanceof Driver\Pdo\Pdo) ? $this->driver : null;
                return new Platform\Mysql($driver);
            default:
                return new Platform\Sql92();
        }
    }

    /**
     * @var callable|CacheStorage|array|\ArrayAccess
     */
    protected static $metaCacheStorage;

    /**
     * @param $cacheStorage callable|CacheStorage|array|\ArrayAccess
     */
    public static function setMetaCacheStorage($cacheStorage)
    {
        self::$metaCacheStorage = $cacheStorage;
    }

    /**
     * @return null|CacheStorage
     */
    public static function getMetaCacheStorage()
    {
        if (is_null(self::$metaCacheStorage) || (self::$metaCacheStorage instanceof CacheStorage)) {
            return self::$metaCacheStorage;
        }

        if (is_callable(self::$metaCacheStorage)) {
            $factory = self::$metaCacheStorage;
            try {
                $instance = $factory();
            } catch (\Exception $e) {
                throw new Exception\RuntimeException(
                    'An exception was raised while creating cacheStorage', $e->getCode(), $e
                );
            }
            if (!($instance instanceof CacheStorage)) {
                throw new Exception\InvalidArgumentException('Invalid CacheStorage return from callable');
            }
            self::$metaCacheStorage = $instance;
        } else {
            self::$metaCacheStorage = Cache::factory(self::$metaCacheStorage);
        }
        return self::$metaCacheStorage;
    }

    /**
     * @var callable|AdapterInterface|Driver\DriverInterface|array
     */
    protected static $defaultAdapter;

    /**
     * @param $adapter callable|AdapterInterface|Driver\DriverInterface|array
     */
    public static function setDefaultAdapter($adapter)
    {
        self::$defaultAdapter = $adapter;
    }

    /**
     * @return null|AdapterInterface
     */
    public static function getDefaultAdapter()
    {
        if (is_null(self::$defaultAdapter) || (self::$defaultAdapter instanceof AdapterInterface)) {
            return self::$defaultAdapter;
        }
        if (is_callable(self::$defaultAdapter)) {
            $factory = self::$defaultAdapter;
            try {
                $instance = $factory();
            } catch (\Exception $e) {
                throw new Exception\RuntimeException(
                    'An exception was raised while creating adapter', $e->getCode(), $e
                );
            }
            if (!($instance instanceof AdapterInterface)) {
                throw new Exception\InvalidArgumentException('Invalid adapter return from callable');
            }
            self::$defaultAdapter = $instance;
        } else {
            self::$defaultAdapter = new Adapter(self::$defaultAdapter);
        }

        return self::$defaultAdapter;
    }
}
