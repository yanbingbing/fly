<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

include __DIR__ . '/Config/Define.php';

if ($flyPath = defined('FLY_PATH') ? FLY_PATH : getenv('FLY_PATH')) {
	include $flyPath . '/Fly/Loader/Loader.php';
} else {
	include 'Fly/Loader/Loader.php';
}

if (!class_exists('Fly\Loader\Loader', false)) {
	throw new RuntimeException('Unable to load FLY. Define a FLY_PATH environment variable.');
}

use Fly\Loader\Loader;
use Fly\Db\Sql\Expression;
use Fly\Db\Sql\Sql as DbSql;
use Fly\Db\Sql\Select as DbSelect;
use Fly\Db\Sql\TableIdentifier;
use Fly\Db\Table\Table as DbTable;
use Fly\Db\Row\AbstractRow;
use Fly\Util\Debug;

/**
 * Get Expression object
 *
 * @param string $expression
 * @return Expression
 */
function expr($expression = '')
{
	return new Expression($expression);
}

/**
 * @param string|TableIdentifier $table
 * @return DbTable
 */
function table($table)
{
	/** @var DbTable[] $instances */
	static $instances = array();
	$table = $table instanceof Fly\Db\Sql\TableIdentifier ? $table : new Fly\Db\Sql\TableIdentifier($table);
	$key = (string) $table;
	if (isset($instances[$key])) {
		return $instances[$key];
	}
	return $instances[$key] = new DbTable($table);
}

/**
 * Get Sql object
 *
 * @param null|string|TableIdentifier $table
 * @param bool $new
 * @return DbSql
 */
function sql($table = null, $new = false)
{
	/** @var DbSql[] $instances */
	static $instances = array();
	if ($new) {
		return new DbSql(null, $table);
	}
	$key = $table ?: ' ';
	if (isset($instances[$key])) {
		return $instances[$key];
	}
	return $instances[$key] = new DbSql(null, $table);
}

/**
 * Get Select object
 *
 * @param string|TableIdentifier $table
 * @return DbSelect
 */
function select($table)
{
	return sql()->select($table);
}

/**
 * Prepare a row for create
 *
 * @param string|TableIdentifier $table
 * @param null|array|object $data
 * @return AbstractRow
 */
function row($table, $data = null)
{
	return table($table)->create($data);
}

/**
 * Console a data to header
 *
 * @param mixed $data
 */
function console($data)
{
	Debug::console($data);
}


/**
 * Get Expression object
 *
 * @param string $expression
 * @return Expression
 */
function E($expression = '')
{
	return expr($expression);
}

/**
 * @param string|TableIdentifier $table
 * @return DbTable
 */
function T($table)
{
	return table($table);
}

/**
 * Get Sql object
 *
 * @param null|string|TableIdentifier $table
 * @param bool $new
 * @return DbSql
 */
function S($table = null, $new = false)
{
	return sql($table, $new);
}

/**
 * Get Select object
 *
 * @param string|TableIdentifier $table
 * @return DbSelect
 */
function Q($table)
{
	return select($table);
}

/**
 * Prepare a row for create
 *
 * @param string|TableIdentifier $table
 * @param null|array|object $data
 * @return AbstractRow
 */
function R($table, $data = null)
{
	return row($table, $data);
}

$loader = Loader::getInstance();
$loader->registerNamespace('Demos\Shared', __DIR__);