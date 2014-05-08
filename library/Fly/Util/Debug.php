<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Util;

use Fly\Util\FirePHP\FirePHP;
use Fly\Util\ChromePHP\ChromePHP;

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
     * use FirePHP or ChomePHP dump message to browser
     *
     * @param mixed $message
     */
    public static function console($message)
    {
        if (strstr($_SERVER['HTTP_USER_AGENT'], ' Firefox/')) {
            self::consoleViaFirePHP($message);
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], ' Chrome/')) {
            self::consoleViaChromePHP($message);
        }
    }

    protected static function consoleViaFirePHP($message)
    {
        static $fb = null;

        if (is_null($fb)) {
            $fb = FirePHP::getInstance(true);
        }
        $fb->info($message);
    }

    protected static function consoleViaChromePHP($message)
    {
        ChromePHP::info($message);
    }
}
