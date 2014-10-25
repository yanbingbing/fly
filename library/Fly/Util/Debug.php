<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Util;

abstract class Debug
{
    /**
     * dump a var
     *
     * @param mixed $var
     * @param null $label
     * @param bool $return use return instead output
     * @return string
     */
    public static function dump($var, $label = null, $return = false)
    {
        // format the label
        $label = ($label === null) ? '' : rtrim($label) . ' ';

        $output = print_r($var, true);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $label . PHP_EOL . $output . PHP_EOL;
        } else {
            $output = htmlspecialchars($output, ENT_QUOTES);
            $output = '<pre>' . $label . $output . '</pre>';
        }

        if ($return) {
            return $output;
        }
        echo $output;
        return '';
    }

    /**
     * @var callable
     */
    protected static $handler;

    /**
     * dump message to browser
     *
     * @param mixed $message
     */
    public static function console($message, $label = null)
    {
        if (is_callable(self::$handler)) {
            call_user_func(self::$handler, $message, $label);
        }
    }

    /**
     * @param callable $handler
     */
    public static function setConsoleHandler($handler)
    {
        if (is_callable($handler)) {
            self::$handler = $handler;
        }
    }
}
