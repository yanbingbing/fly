<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

use Fly\View\Exception;
use Fly\MountManager\MountManager;
use Fly\Mvc\Router\Router;
use Fly\Mvc\Input\Http as Input;

class Url extends AbstractPlugin
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Input
     */
    protected $input;

    public function __construct()
    {
        $mounts = MountManager::getInstance();
        $this->setRouter($mounts->get('Router'));
        $this->setInput($mounts->get('Input'));
    }

    /**
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @param Input $input
     * @return $this
     */
    public function setInput(Input $input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @param null|string|array $name
     * @param array $params
     * @param bool $absolute
     * @return string
     * @throws Exception\RuntimeException
     */
    public function __invoke($name = null, $params = array(), $absolute = false)
    {
        if (is_bool($name)) {
            $absolute = $name;
            $name = null;
        }
        if (is_bool($params)) {
            $absolute = $params;
            $params = array();
        }
        if (is_array($name)) {
            if (is_bool($params)) {
                $absolute = $params;
            }
            $params = $name;
            $name = null;
        }
        if ($name == null) {
            $match = $this->input->getRouteMatch();
            if ($match === null) {
                throw new Exception\RuntimeException('No RouteMatch instance or router name provided');
            }
            $name = $match->getMatchedRouteName();
            if ($name === null) {
                throw new Exception\RuntimeException('RouteMatch does not contain a matched route name');
            }
        }

        $url = $this->input->getBaseUrl() . $this->router->assemble($name, $params);
        if ($absolute) {
            $uri = clone $this->input->getUri();
            $parts = explode('?', $url);
            $uri->setPath($parts[0]);
            $uri->setQuery(empty($parts[1]) ? null : $parts[1]);
            $uri->setFragment(null);
            return $uri->toString();
        } else {
            return $url;
        }
    }
}