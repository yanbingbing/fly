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

$loader = Loader::getInstance();
$loader->registerNamespace('Demos\Shared', __DIR__);

if ((defined('DEBUG_MODE') && DEBUG_MODE) || getenv('DEBUG_MODE')) {
	Fly\Util\ExceptionHandler::set(function(\Exception $e){
		while ($t = $e->getPrevious()) {
			$e = $t;
		}
		$error = get_class($e);
		$errstr = $e->getMessage();
		$errno = $e->getCode();
		$errfile = $e->getFile();
		$errline = $e->getLine();
		$trace = $e->getTrace();
		if ($e instanceof ErrorException) {
			array_slice($trace, 0, 2);
		}
		$errinfo = "Exception '{$error}'";
		if ($errstr != '') {
			$errinfo .= " with message '{$errstr}'";
		}
		$errinfo .= " in {$errfile}:{$errline}";
		$ix = count($trace);
		foreach ($trace as &$point) {
			$point['function'] = isset($point['class']) ? "{$point['class']}::{$point['function']}" : $point['function'];
			$argd = array();
			if (!isset($point['args'])) {
				$point['args'] = array();
			}
			if (is_array($point['args']) && count($point['args']) > 0) {
				foreach ($point['args'] as $arg) {
					switch (gettype($arg)) {
						case 'array':
							$argd[] = 'array(' . count($arg) . ')';
							break;
						case 'resource':
							$argd[] = gettype($arg);
							break;
						case 'object':
							$argd[] = get_class($arg);
							break;
						case 'string':
							if (strlen($arg) > 30) {
								$arg = substr($arg, 0, 27) . ' ...';
							}
							$argd[] = "'{$arg}'";
							break;
						default:
							$argd[] = $arg;
					}
				}
			}
			$point['argd'] = $argd;
			$point['index'] = $ix--;
		}
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		function dump($var, $label = null, $return = false)
		{
			// format the label
			$label = ($label === null) ? '' : rtrim($label) . ' ';

			$output = print_r($var, true);

			if (PHP_SAPI == 'cli') {
				$output = PHP_EOL . $label
					. PHP_EOL . $output
					. PHP_EOL;
			} else {
				$output = htmlspecialchars($output, ENT_QUOTES);
				$output = '<pre>'
					. $label
					. $output
					. '</pre>';
			}

			if ($return) {
				return $output;
			}
			echo $output;
		}
		function excerpt($file, $line) {
			if (!(file_exists($file) && is_file($file))) {
				return array();
			}
			$data = file($file);
			$start = $line - 10;
			if ($start < 0) {
				$start = 0;
			}
			$rv = array_slice($data, $start, 21);
			$rk = range($start + 1, $start + count($rv));
			return array_combine($rk, $rv);
		}
		include __DIR__ . '/Assets/exception.phtml';
		exit;
	});
}

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