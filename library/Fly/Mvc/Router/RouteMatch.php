<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Router;

class RouteMatch
{
    /**
     * Match parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * Matched route name.
     *
     * @var string
     */
    protected $matchedRouteName;

    /**
     * Create a Match with given parameters.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Set name of matched route.
     *
     * @param  string $name
     * @return self
     */
    public function setMatchedRouteName($name)
    {
        $this->matchedRouteName = $name;
        return $this;
    }

    /**
     * Get name of matched route.
     *
     * @return string
     */
    public function getMatchedRouteName()
    {
        return $this->matchedRouteName;
    }

    /**
     * Set a parameter.
     *
     * @param  string $name
     * @param  mixed $value
     * @return self
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get all parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Check param exists
     *
     * @param string $name
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Get a specific parameter.
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return $this->hasParam($name) ? $this->params[$name] : $default;
    }
}
