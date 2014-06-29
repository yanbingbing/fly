<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Placeholder\Container;

use Fly\View\Exception;
use stdClass;

class Script extends AbstractContainer
{
    /**#@+
     * Capture type and/or attributes (used for hinting during capture)
     * @var string
     */
    protected $captureLock;
    protected $captureScriptType = null;
    protected $captureScriptAttrs = null;
    protected $captureType;
    /**#@-*/

    /**
     * Constructor
     *
     * Set separator to PHP_EOL.
     *
     */
    public function __construct()
    {
        $this->setSeparator(PHP_EOL);
    }


    /**
     * Start capture action
     *
     * @param  mixed $captureType Type of capture
     * @param  string $type Type of script
     * @param  array $attrs Attributes of capture
     * @return void
     * @throws Exception\RuntimeException
     */
    public function captureStart($captureType = self::APPEND, $type = 'text/javascript', $attrs = array())
    {
        if ($this->captureLock) {
            throw new Exception\RuntimeException('Cannot nest Script captures');
        }

        $this->captureLock = true;
        $this->captureType = $captureType;
        $this->captureScriptType = $type;
        $this->captureScriptAttrs = $attrs;
        ob_start();
    }

    /**
     * End capture action and store
     *
     * @return void
     */
    public function captureEnd()
    {
        $content = ob_get_clean();
        $type = $this->captureScriptType;
        $attrs = $this->captureScriptAttrs;
        $this->captureScriptType = null;
        $this->captureScriptAttrs = null;
        $this->captureLock = false;

        switch ($this->captureType) {
            case self::SET:
            case self::PREPEND:
            case self::APPEND:
                $action = strtolower($this->captureType) . 'Script';
                break;
            default:
                $action = 'appendScript';
                break;
        }
        $this->$action($content, $type, $attrs);
    }

    /**
     * Overload method access
     *
     * Allows the following method calls:
     * - appendFile($src, $type = 'text/javascript', $attrs = array())
     * - prependFile($src, $type = 'text/javascript', $attrs = array())
     * - setFile($src, $type = 'text/javascript', $attrs = array())
     * - appendScript($script, $type = 'text/javascript', $attrs = array())
     * - prependScript($script, $type = 'text/javascript', $attrs = array())
     * - setScript($script, $type = 'text/javascript', $attrs = array())
     *
     * @param  string $method Method to call
     * @param  array $args Arguments of method
     * @return $this
     * @throws Exception\RuntimeException if too few arguments or invalid method
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend)(?P<mode>File|Script)$/', $method, $matches)) {
            if (1 > count($args)) {
                throw new Exception\RuntimeException(sprintf(
                    'Method "%s" requires at least one argument',
                    $method
                ));
            }

            $action = $matches['action'];
            $mode = strtolower($matches['mode']);
            $type = 'text/javascript';
            $attrs = array();

            $content = $args[0];

            if (isset($args[1])) {
                $type = (string)$args[1];
            }
            if (isset($args[2])) {
                $attrs = (array)$args[2];
            }

            if ($mode == 'script') {
                $item = $this->createData($type, $attrs, $content);
                $this->$action($item);
            } elseif (!$this->isDuplicate($content)) {
                $attrs['src'] = $content;
                $item = $this->createData($type, $attrs);
                $this->$action($item);
            }
        }

        return $this;
    }

    /**
     * Is the file specified a duplicate?
     *
     * @param  string $file Name of file to check
     * @return bool
     */
    protected function isDuplicate($file)
    {
        foreach ($this->items as $item) {
            if (($item->source === null)
                && array_key_exists('src', $item->attributes)
                && ($file == $item->attributes['src'])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is the script provided valid?
     *
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        return $value instanceof stdClass && isset($value->type) && (isset($value->source) || isset($value->attributes));
    }

    /**
     * Create script HTML
     *
     * @param  stdClass $item Item to convert
     * @return string
     */
    public function itemToString($item)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if ('defer' == $key) {
                    $value = 'defer';
                }
                $attrString .= sprintf(' %s="%s"', $key, $value);
            }
        }


        $html = '<script type="' . $item->type . '"' . $attrString . '>';
        if (!empty($item->source)) {
            $html .= PHP_EOL . $item->source . PHP_EOL;
        }
        $html .= '</script>';

        return $html;
    }

    /**
     * Retrieve string representation
     *
     * @return string
     */
    public function toString()
    {
        $items = array();
        foreach ($this->items as $item) {
            if ($this->isValid($item)) {
                $items[] = $this->itemToString($item);
            }
        }

        return implode($this->getSeparator(), $items);
    }

    /**
     * Create data item containing all necessary components of script
     *
     * @param  string $type Type of data
     * @param  array $attributes Attributes of data
     * @param  string $content Content of data
     * @return stdClass
     */
    public function createData($type, array $attributes, $content = null)
    {
        $data = new stdClass();
        $data->type = $type;
        $data->attributes = $attributes;
        $data->source = $content;
        return $data;
    }
}