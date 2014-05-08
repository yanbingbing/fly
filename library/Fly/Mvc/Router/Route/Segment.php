<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Router\Route;

use ArrayAccess;
use Fly\Mvc\Router\RouteMatch;
use Fly\Mvc\Router\Exception;
use Fly\Mvc\Input\Http as Input;

class Segment implements RouteInterface
{
    /**
     * Map of allowed special chars in path segments.
     *
     * @var array
     */
    protected static $urlencodeCorrectionMap = array(
        '%21' => "!", // sub-delims
        '%24' => "$", // sub-delims
        '%26' => "&", // sub-delims
        '%27' => "'", // sub-delims
        '%28' => "(", // sub-delims
        '%29' => ")", // sub-delims
        //      '%2A' => "*", // sub-delims
        '%2B' => "+", // sub-delims
        '%2C' => ",", // sub-delims
        //      '%2D' => "-", // unreserved
        //      '%2E' => ".", // unreserved
        '%3A' => ":", // pchar
        '%3B' => ";", // sub-delims
        '%3D' => "=", // sub-delims
        '%40' => "@", // pchar
        //      '%5F' => "_", // unreserved
        //      '%7E' => "~", // unreserved
    );

    /**
     * Parts of the route.
     *
     * @var array
     */
    protected $parts;

    /**
     * Regex used for matching the route.
     *
     * @var string
     */
    protected $regex;

    /**
     * Map from regex groups to parameter names.
     *
     * @var array
     */
    protected $paramMap = array();

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * @var bool
     */
    protected $wildcard = false;

    /**
     * Create a new regex route.
     *
     * @param  string $rule
     * @param  array $constraints
     * @param  array $defaults
     */
    public function __construct($rule, array $constraints = array(), array $defaults = array())
    {
        $this->defaults = $defaults;
        $rule = trim($rule);
        if ($rule[strlen($rule) - 1] == '*') {
            $rule = rtrim($rule, '/*');
            $this->wildcard = true;
        }
        $this->parts = $this->parseRouteDefinition($rule);
        $this->regex = $this->buildRegex($this->parts, $constraints);
    }

    /**
     * factory
     *
     * @param  array|ArrayAccess $options
     * @throws Exception\InvalidArgumentException
     * @return Segment
     */
    public static function factory($options = array())
    {
        if (!is_array($options) && !($options instanceof ArrayAccess)) {
            throw new Exception\InvalidArgumentException(
                __METHOD__ . ' expects an array or ArrayAccess set of options');
        }

        $rule = null;
        $defaults = array();
        $constraints = array();
        if (isset($options['rule'])) {
            $rule = $options['rule'];
            unset($options['rule']);
        } else {
            reset($options);
            $rule = key($options);
            list($controller, $action) = explode('#', current($options), 2);
            $defaults['controller'] = $controller;
            $defaults['action'] = $action;
            unset($options[$rule]);
        }
        if ($rule[0] == '-') {
            $rule = substr($rule, 1);
        }

        if (isset($options['defaults']) && is_array($options['defaults'])) {
            $defaults = array_merge($defaults, $options['defaults']);
            unset($options['defaults']);
        }

        if (isset($options['constraints']) && is_array($options['constraints'])) {
            $constraints = $options['constraints'];
            unset($options['constraints']);
        }

        foreach ($options as $key => $val) {
            if ($key[0] == ':') {
                $constraints[substr($key, 1)] = $val;
            } else {
                $defaults[$key] = $val;
            }
        }

        return new static($rule, $constraints, $defaults);
    }

    /**
     * Parse a route definition.
     *
     * @param  string $def
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseRouteDefinition($def)
    {
        $currentPos = 0;
        $length = strlen($def);
        $parts = array();
        $levelParts = array(&$parts);
        $level = 0;

        while ($currentPos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:\[\]]|$))', $def, $matches, 0, $currentPos);

            $currentPos += strlen($matches[0]);

            if (!empty($matches['literal'])) {
                $levelParts[$level][] = array('literal', $matches['literal']);
            }

            if ($matches['token'] === ':') {
                if (!preg_match('(\G(?P<name>[^:/{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)', $def, $matches, 0,
                    $currentPos)
                ) {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                $levelParts[$level][] = array(
                    'parameter', $matches['name'], isset($matches['delimiters']) ? $matches['delimiters'] : null
                );
                $currentPos += strlen($matches[0]);
            } elseif ($matches['token'] === '[') {
                $levelParts[$level][] = array('optional', array());
                $levelParts[$level + 1] = & $levelParts[$level][count($levelParts[$level]) - 1][1];

                $level++;
            } elseif ($matches['token'] === ']') {
                unset($levelParts[$level]);
                $level--;

                if ($level < 0) {
                    throw new Exception\RuntimeException('Found closing bracket without matching opening bracket');
                }
            } else {
                break;
            }
        }

        if ($level > 0) {
            throw new Exception\RuntimeException('Found unbalanced brackets');
        }

        return $parts;
    }

    /**
     * Build the matching regex from parsed parts.
     *
     * @param  array $parts
     * @param  array $constraints
     * @param  int $groupIndex
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function buildRegex(array $parts, array $constraints, &$groupIndex = 1)
    {
        $regex = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $regex .= preg_quote($part[1]);
                    break;

                case 'parameter':
                    $groupName = '?P<param' . $groupIndex . '>';

                    if (isset($constraints[$part[1]])) {
                        $regex .= '(' . $groupName . $constraints[$part[1]] . ')';
                    } elseif ($part[2] === null) {
                        $regex .= '(' . $groupName . '[^/]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }

                    $this->paramMap['param' . $groupIndex++] = $part[1];
                    break;

                case 'optional':
                    $regex .= '(?:' . $this->buildRegex($part[1], $constraints, $groupIndex) . ')?';
                    break;
            }
        }

        return $regex;
    }

    /**
     * Build a path.
     *
     * @param  array $parts
     * @param  array $mergedParams
     * @param  bool $isOptional
     * @return string
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    protected function buildPath(array $parts, array &$mergedParams, $isOptional = false)
    {
        $path = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $path .= $part[1];
                    break;

                case 'parameter':
                    if (!isset($mergedParams[$part[1]])) {
                        if (!$isOptional) {
                            throw new Exception\InvalidArgumentException(sprintf('Missing parameter "%s"', $part[1]));
                        }
                        return '';
                    }
                    $path .= strtr(rawurlencode($mergedParams[$part[1]]), static::$urlencodeCorrectionMap);
                    unset($mergedParams[$part[1]]);
                    break;

                case 'optional':
                    $optionalPart = $this->buildPath($part[1], $mergedParams, true);

                    if ($optionalPart !== '') {
                        $path .= $optionalPart;
                    }
                    break;
            }
        }

        return $path;
    }

    /**
     * match
     *
     * @param  Input $input
     * @return RouteMatch
     */
    public function match(Input $input)
    {
        $path = $input->getPathInfo();

        $rest = null;
        $matches = array();
        if ($this->wildcard) {
            $result = $this->regex ? preg_match('(^' . $this->regex . ')', $path, $matches) : 1;
            if ($matches) {
                $rest = trim(substr($path, strlen($matches[0])), '/');
            }
        } else {
            $result = preg_match('(^' . $this->regex . '$)', $path, $matches);
        }

        if (!$result) {
            return null;
        }

        $params = array();

        foreach ($this->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = rawurldecode($matches[$index]);
            }
        }

        if ($rest) {
            $splits = explode('/', $rest);
            $count = count($splits);

            for ($i = 0; $i < $count; $i += 2) {
                $params[rawurldecode($splits[$i])] = isset($splits[$i + 1]) ? rawurldecode($splits[$i + 1]) : null;
            }
        }

        return new RouteMatch(array_merge($this->defaults, $params));
    }

    /**
     * assemble
     *
     * @param  array $params
     * @return string
     */
    public function assemble(array $params = array())
    {
        $mergedParams = array_merge($this->defaults, $params);
        $url = $this->buildPath($this->parts, $mergedParams);

        $diffParams = array_diff_key($mergedParams, $this->defaults);
        if (!empty($diffParams)) {
            $url .= '?' . http_build_query($diffParams);
        }
        return $url;
    }
}
