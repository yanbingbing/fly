<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Placeholder\Container;

use Fly\View\Exception;
use stdClass;

class Style extends AbstractContainer
{
    /**
     * Capture type and/or attributes (used for hinting during capture)
     *
     * @var string
     */
    protected $captureAttrs = null;

    /**
     * Capture lock
     *
     * @var bool
     */
    protected $captureLock;

    /**
     * Capture type (append, prepend, set)
     *
     * @var string
     */
    protected $captureType;

    /**
     * Constructor
     *
     * Set separator to PHP_EOL.
     */
    public function __construct()
    {
        $this->setSeparator(PHP_EOL);
    }

    /**
     * Return headStyle object
     *
     * Returns headStyle helper object; optionally, allows specifying
     *
     * @param  string       $content    Stylesheet contents
     * @param  string       $placement  Append, prepend, or set
     * @param  string|array $attributes Optional attributes to utilize
     * @return self
     */
    public function __invoke($content = null, $placement = 'APPEND', $attributes = array())
    {
        if ((null !== $content) && is_string($content)) {
            switch (strtoupper($placement)) {
                case 'SET':
                    $action = 'setStyle';
                    break;
                case 'PREPEND':
                    $action = 'prependStyle';
                    break;
                case 'APPEND':
                default:
                    $action = 'appendStyle';
                    break;
            }
            $this->$action($content, $attributes);
        }

        return $this;
    }

    /**
     * Overload method calls
     *
     * @param  string $method
     * @param  array  $args
     * @throws Exception\RuntimeException When no $content provided or invalid method
     * @return void
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend)(?P<mode>File|Style)$/', $method, $matches)) {
            if (1 > count($args)) {
                throw new Exception\RuntimeException(sprintf(
                    'Method "%s" requires at least one argument',
                    $method
                ));
            }
            $action = $matches['action'];
            $mode = strtolower($matches['mode']);
            $attrs   = array();
            $content = $args[0];

            if (isset($args[1])) {
                $attrs = (array) $args[1];
            }

            if ($mode == 'style') {
                $item = $this->createData($attrs, $content);
                $this->$action($item);
            } elseif (!$this->isDuplicate($content)) {
                $attrs['href'] = $content;
                $item = $this->createData($attrs);
                $this->$action($item);
            }

            return $this;
        }

        return $this;
    }

    /**
     * Create string representation
     *
     * @return string
     */
    public function toString()
    {
        $items = array();
        foreach ($this->items as $item) {
            if (!$this->isValid($item)) {
                continue;
            }
            $items[] = $this->itemToString($item);
        }

        return implode($this->getSeparator(), $items);
    }

    /**
     * Start capture action
     *
     * @param  string $type
     * @param  string $attrs
     * @throws Exception\RuntimeException
     * @return void
     */
    public function captureStart($type = self::APPEND, $attrs = null)
    {
        if ($this->captureLock) {
            throw new Exception\RuntimeException('Cannot nest Style captures');
        }

        $this->captureLock  = true;
        $this->captureAttrs = $attrs;
        $this->captureType  = $type;
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
        $attrs   = $this->captureAttrs;
        $this->captureAttrs = null;
        $this->captureLock  = false;

        switch ($this->captureType) {
            case self::SET:
            case self::PREPEND:
            case self::APPEND:
                $action = strtolower($this->captureType) . 'Style';
                break;
            default:
                $action = 'appendStyle';
                break;
        }
        $this->$action($content, $attrs);
    }

    /**
     * Create data item for use in stack
     *
     * @param  string $content
     * @param  array  $attributes
     * @return stdClass
     */
    public function createData(array $attributes, $source = null)
    {
        $data = new stdClass;
        $data->attributes = $attributes;
        $data->source = $source;

        return $data;
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
                && array_key_exists('href', $item->attributes)
                && ($file == $item->attributes['href'])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a value is a valid style tag
     *
     * @param  mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        return ($value instanceof stdClass) && (isset($value->source) || isset($value->attributes));
    }

    /**
     * Convert content and attributes into valid style|link tag
     *
     * @param  stdClass $item   Item to render
     * @return string
     */
    public function itemToString(stdClass $item)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                $attrString .= sprintf(' %s="%s"', $key, $value);
            }
        }

        if (!empty($item->source)) {
            $html = '<style type="text/css"' . $attrString . '>' . PHP_EOL
                . $item->source . PHP_EOL
                . '</style>';
        } else {
            $html = '<link rel="stylesheet" type="text/css" ' . $attrString .'>';
        }

        return $html;
    }
}