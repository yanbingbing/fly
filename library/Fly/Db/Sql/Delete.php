<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Sql;

use Fly\Db\Adapter\Driver\DriverInterface;
use Fly\Db\Adapter\Driver\StatementInterface;
use Fly\Db\Adapter\Parameters;
use Fly\Db\Adapter\Platform\PlatformInterface;
use Fly\Db\Adapter\Platform\Sql92;

class Delete extends AbstractSql
{
    /**@#+
     * @const
     */
    const SPECIFICATION_DELETE = 'delete';
    const SPECIFICATION_WHERE = 'where';
    /**@#-*/

    /**
     * @var array Specifications
     */
    protected $specifications = array(
        self::SPECIFICATION_DELETE => 'DELETE FROM %1$s',
        self::SPECIFICATION_WHERE => 'WHERE %1$s'
    );

    /**
     * @var TableIdentifier
     */
    protected $table = '';

    /**
     * @var bool
     */
    protected $emptyWhereProtection = true;

    /**
     * @var array
     */
    protected $set = array();

    /**
     * @var null|string|Where
     */
    protected $where = null;

    /**
     * Constructor
     *
     * @param  string|TableIdentifier $table
     */
    public function __construct($table = null)
    {
        if ($table) {
            $this->from($table);
        }
        $this->where = new Where();
    }

    /**
     * Create from statement
     *
     * @param  string|TableIdentifier $table
     * @return Delete
     */
    public function from($table)
    {
        $this->table = $table instanceof TableIdentifier ? $table : new TableIdentifier($table);
        return $this;
    }

    public function getRawState($key = null)
    {
        $rawState = array(
            'emptyWhereProtection' => $this->emptyWhereProtection,
            'table' => $this->table,
            'set' => $this->set,
            'where' => $this->where
        );
        return (isset($key) && array_key_exists($key, $rawState)) ? $rawState[$key] : $rawState;
    }

    /**
     * Create where clause
     *
     * @param  Where|\Closure|string|array $predicate
     * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return $this
     */
    public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } else {
            $this->where->addPredicates($predicate, $combination);
        }
        return $this;
    }

    /**
     * Prepare the delete statement
     *
     * @param null|PlatformInterface $platform
     * @param null|DriverInterface $driver
     * @return StatementInterface
     */
    public function prepareStatement(PlatformInterface $platform = null, DriverInterface $driver = null)
    {
        $platform = $platform ? : $this->platform ? : new Sql92;
        $driver = $driver ? : $this->driver;

        $statement = $driver->createStatement();
        $parameters = new Parameters;
        $statement->setParameters($parameters);

        list($table, $schema) = $this->table->getAll();

        $table = $platform->quoteIdentifier($table);

        if ($schema) {
            $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
        }

        $sql = sprintf($this->specifications[self::SPECIFICATION_DELETE], $table);

        // process where
        if ($this->where->count() > 0) {
            $part = $this->processExpression($this->where, $platform, $driver, 'where', $parameters);
            $sql .= ' ' . sprintf($this->specifications[self::SPECIFICATION_WHERE], $part);
        }
        $statement->setSql($sql);
        return $statement;
    }

    /**
     * Get the SQL string, based on the platform
     * Platform defaults to Sql92 if none provided
     *
     * @param  null|PlatformInterface $platform
     * @return string
     */
    public function getSqlString(PlatformInterface $platform = null)
    {
        $platform = $platform ? : $this->platform ? : new Sql92;

        list($table, $schema) = $this->table->getAll();

        $table = $platform->quoteIdentifier($table);

        if ($schema) {
            $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
        }

        $sql = sprintf($this->specifications[self::SPECIFICATION_DELETE], $table);

        if ($this->where->count() > 0) {
            $part = $this->processExpression($this->where, $platform, null, 'where');
            $sql .= ' ' . sprintf($this->specifications[self::SPECIFICATION_WHERE], $part);
        }

        return $sql;
    }
}
