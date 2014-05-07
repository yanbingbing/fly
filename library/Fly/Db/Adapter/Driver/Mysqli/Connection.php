<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Driver\Mysqli;

use Fly\Db\Adapter\Driver\ConnectionInterface;
use Fly\Db\Adapter\Exception;

class Connection implements ConnectionInterface
{

    /**
     * @var Mysqli
     */
    protected $driver = null;

    /**
     * Connection parameters
     *
     * @var array
     */
    protected $connectionParameters = array();

    /**
     * @var \mysqli
     */
    protected $resource = null;

    /**
     * In transaction
     *
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * Constructor
     *
     * @param array|\mysqli|null $connectionInfo
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($connectionInfo = null)
    {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        } elseif ($connectionInfo instanceof \mysqli) {
            $this->setResource($connectionInfo);
        } elseif (null !== $connectionInfo) {
            throw new Exception\InvalidArgumentException(
                '$connection must be an array of parameters, a mysqli object or null');
        }
    }

    /**
     * @param Mysqli $driver
     * @return $this
     */
    public function setDriver(Mysqli $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Set connection parameters
     *
     * @param array $connectionParameters
     * @return $this
     */
    public function setConnectionParameters(array $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
        return $this;
    }

    /**
     * Get connection parameters
     *
     * @return array
     */
    public function getConnectionParameters()
    {
        return $this->connectionParameters;
    }

    /**
     * Get current schema
     *
     * @return string
     */
    public function getCurrentSchema()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        /** @var $result \mysqli_result */
        $result = $this->resource->query('SELECT DATABASE()');
        $r = $result->fetch_row();
        return $r[0];
    }

    /**
     * Set resource
     *
     * @param \mysqli $resource
     * @return $this
     */
    public function setResource(\mysqli $resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Get resource
     *
     * @return \mysqli
     */
    public function getResource()
    {
        $this->connect();
        return $this->resource;
    }

    /**
     * Connect
     *
     * @throws Exception\RuntimeException
     * @return $this
     */
    public function connect()
    {
        if ($this->resource instanceof \mysqli) {
            return $this;
        }

        // localize
        $p = $this->connectionParameters;

        // given a list of key names, test for existence in $p
        $findParameterValue = function (array $names) use ($p) {
            foreach ($names as $name) {
                if (isset($p[$name])) {
                    return $p[$name];
                }
            }
            return;
        };

        $hostname = $findParameterValue(array('hostname', 'host'));
        $username = $findParameterValue(array('username', 'user'));
        $password = $findParameterValue(array('password', 'passwd', 'pw'));
        $database = $findParameterValue(array('database', 'dbname', 'db', 'schema'));
        $port = (isset($p['port'])) ? (int) $p['port'] : null;
        $socket = (isset($p['socket'])) ? $p['socket'] : null;

        $this->resource = new \mysqli();
        $this->resource->init();

        if (!empty($p['driver_options'])) {
            foreach ($p['driver_options'] as $option => $value) {
                if (is_string($option)) {
                    $option = strtoupper($option);
                    if (!defined($option)) {
                        continue;
                    }
                    $option = constant($option);
                }
                $this->resource->options($option, $value);
            }
        }

        $this->resource->real_connect($hostname, $username, $password, $database, $port, $socket);

        if ($this->resource->connect_error) {
            throw new Exception\RuntimeException(
                'Connection error',
                null,
                new Exception\ErrorException($this->resource->connect_error, $this->resource->connect_errno)
            );
        }

        if (!empty($p['charset'])) {
            $this->resource->set_charset($p['charset']);
        }

        return $this;
    }

    /**
     * Is connected
     *
     * @return bool
     */
    public function isConnected()
    {
        return ($this->resource instanceof \mysqli);
    }

    /**
     * Disconnect
     *
     * @return $this
     */
    public function disconnect()
    {
        if ($this->resource instanceof \mysqli) {
            $this->resource->close();
        }
        $this->resource = null;
        return $this;
    }

    /**
     * Begin transaction
     *
     * @return $this
     */
    public function beginTransaction()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->resource->autocommit(false);
        $this->inTransaction = true;
        return $this;
    }

    /**
     * In transaction
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->inTransaction;
    }

    /**
     * Commit
     *
     * @return $this
     */
    public function commit()
    {
        if (!$this->resource) {
            $this->connect();
        }

        $this->resource->commit();
        $this->inTransaction = false;
        $this->resource->autocommit(true);
        return $this;
    }

    /**
     * Rollback
     *
     * @throws Exception\RuntimeException
     * @return $this
     */
    public function rollback()
    {
        if (!$this->resource) {
            throw new Exception\RuntimeException('Must be connected before you can rollback.');
        }

        if (!$this->inTransaction) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback.');
        }

        $this->resource->rollback();
        $this->resource->autocommit(true);
        return $this;
    }

    /**
     * Execute
     *
     * @param string $sql
     * @throws Exception\InvalidQueryException
     * @return Result
     */
    public function execute($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $resultResource = $this->resource->query($sql);

        // if the returnValue is something other than a mysqli_result, bypass wrapping it
        if ($resultResource === false) {
            throw new Exception\InvalidQueryException($this->resource->error);
        }

        $resultPrototype = $this->driver->createResult(($resultResource === true) ? $this->resource : $resultResource);
        return $resultPrototype;
    }

    /**
     * Get last generated id
     *
     * @param null $name Ignored
     * @return int
     */
    public function getLastGeneratedValue($name = null)
    {
        return $this->resource->insert_id;
    }
}
