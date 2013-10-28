<?php

namespace Fly\Util;


abstract class ExceptionHandler
{
	protected static $_seted = false;

	public static function set($handler = null)
	{
		if (!self::$_seted) {
			set_error_handler(array(get_called_class(), '__errorHandler'));
			self::$_seted = true;
		}
		if (is_callable($handler)) {
			set_exception_handler($handler);
		}
	}

	public static function restore()
	{
		restore_exception_handler();
		restore_error_handler();
		self::$_seted = false;
	}

	public static function __errorHandler($errno, $errstr, $errfile, $errline)
	{
		switch ($errno) {
			case E_NOTICE:
			case E_STRICT:
			case E_DEPRECATED:
				return;
		}
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}