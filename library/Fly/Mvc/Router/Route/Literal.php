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

class Literal implements RouteInterface
{
    /**
     * RouteInterface to match.
     *
     * @var string
     */
    protected $rule;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Create a new literal route.
     *
     * @param  string $rule
     * @param  array $defaults
     */
    public function __construct($rule, array $defaults = array())
    {
        $this->rule = $rule;
        $this->defaults = $defaults;
    }

    /**
     * factory
     *
     * @param  array|ArrayAccess $options
     * @throws Exception\InvalidArgumentException
     * @return Literal
     */
    public static function factory($options = array())
    {
        if (!is_array($options) && !($options instanceof ArrayAccess)) {
            throw new Exception\InvalidArgumentException(
                __METHOD__ . ' expects an array or ArrayAccess set of options');
        }

        $rule = null;
        $defaults = array();
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
        if ($rule[0] == '=') {
            $rule = substr($rule, 1);
        }

        if (isset($options['defaults']) && is_array($options['defaults'])) {
            $defaults = array_merge($defaults, $options['defaults']);
            unset($options['defaults']);
        }

        $defaults = array_merge($defaults, $options);

        return new static($rule, $defaults);
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

        if ($path === $this->rule) {
            return new RouteMatch($this->defaults);
        }

        return null;
    }

    /**
     * assemble
     *
     * @param  array $params
     * @return string
     */
    public function assemble(array $params = array())
    {
        $url = $this->rule;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}
