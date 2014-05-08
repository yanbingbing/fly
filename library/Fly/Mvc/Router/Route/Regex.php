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

class Regex implements RouteInterface
{
    /**
     * Regex to match.
     *
     * @var string
     */
    protected $regex;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Specification for URL assembly.
     * Parameters accepting substitutions should be denoted as "%key%"
     *
     * @var string
     */
    protected $spec;

    /**
     * Create a new regex route.
     *
     * @param  string $rule
     * @param  string $spec
     * @param  array $defaults
     */
    public function __construct($rule, $spec, array $defaults = array())
    {
        $this->regex = trim($rule);
        $this->spec = $spec;
        $this->defaults = $defaults;
    }

    /**
     * factory
     *
     * @param  array|ArrayAccess $options
     * @throws Exception\InvalidArgumentException
     * @return Regex
     */
    public static function factory($options = array())
    {
        if (!is_array($options) && !($options instanceof ArrayAccess)) {
            throw new Exception\InvalidArgumentException(
                __METHOD__ . ' expects an array or ArrayAccess set of options');
        }

        $rule = null;
        $defaults = array();
        $spec = null;
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
        if ($rule[0] == '~') {
            $rule = substr($rule, 1);
        }

        if (isset($options['defaults']) && is_array($options['defaults'])) {
            $defaults = array_merge($defaults, $options['defaults']);
            unset($options['defaults']);
        }

        if (isset($options['spec'])) {
            $spec = $options['spec'];
            unset($options['spec']);
        }

        $defaults = array_merge($defaults, $options);

        return new static($rule, $spec, $defaults);
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

        $result = preg_match('(^' . $this->regex . '$)', $path, $matches);

        if (!$result) {
            return null;
        }

        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                unset($matches[$key]);
            } else {
                $matches[$key] = rawurldecode($value);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $matches));
    }

    /**
     * assemble
     *
     * @param  array $params
     * @return string
     */
    public function assemble(array $params = array())
    {
        if (empty($this->spec)) {
            throw new Exception\InvalidArgumentException('Missing "spec" defined in route');
        }
        $url = $this->spec;
        $mergedParams = array_merge($this->defaults, $params);

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (strpos($url, $spec) !== false) {
                $url = str_replace($spec, rawurlencode($value), $url);
            }
        }

        return $url;
    }
}
