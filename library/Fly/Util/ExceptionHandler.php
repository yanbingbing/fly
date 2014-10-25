<?php

namespace Fly\Util;


abstract class ExceptionHandler
{
    protected static $started = false;

    public static function start($handler = null)
    {
        if (!self::$started) {
            ini_set('display_errors', false);
            ini_set('html_errors', false);
            set_error_handler(function ($severity = null, $text = null, $file = null, $line = null) {
                if (error_reporting() === 0) {
                    return;
                }
                throw new \ErrorException($text, 0, $severity, $file, $line);
            }, E_ALL ^ E_STRICT ^ E_NOTICE ^ E_DEPRECATED);
            self::$started = true;
        }
        if (is_callable($handler)) {
            set_exception_handler($handler);
        }
    }

    public static function restore()
    {
        restore_exception_handler();
        restore_error_handler();
        self::$started = false;
    }
}