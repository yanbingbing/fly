<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Router;

use ArrayAccess;
use Traversable;
use Fly\Mvc\Router\Route\RouteInterface;
use Fly\Mvc\Input\Http as Input;

class Router
{
	/**
	 * Stack containing all routes.
	 *
	 * @var RouteList
	 */
	protected $routes;

	protected static $typeMap = array(
		'~' => 'Regex',
		'-' => 'Segment',
		'=' => 'Literal'
	);

	public function __construct()
	{
		$this->routes = new RouteList;
	}

	/**
	 * addRoutes
	 *
	 * @param  array|Traversable $routes
	 * @return self
	 * @throws Exception\InvalidArgumentException
	 */
	public function addRoutes($routes)
	{
		if (!is_array($routes) && !$routes instanceof Traversable) {
			throw new Exception\InvalidArgumentException('addRoutes expects an array or Traversable set of routes');
		}

		foreach ($routes as $name => $route) {
			$this->addRoute($name, $route);
		}

		return $this;
	}

	/**
	 * addRoute
	 *
	 * @param  string $name
	 * @param  mixed $route
	 * @return $this
	 */
	public function addRoute($name, $route)
	{
		if (!$route instanceof RouteInterface) {
			$route = $this->routeFromArray($route);
		}

		$this->routes->insert($name, $route);

		return $this;
	}

	/**
	 * removeRoute
	 *
	 * @param  string $name
	 * @return self
	 */
	public function removeRoute($name)
	{
		$this->routes->remove($name);
		return $this;
	}

	/**
	 * setRoutes
	 *
	 * @param  array|Traversable $routes
	 * @return self
	 */
	public function setRoutes($routes)
	{
		$this->routes->clear();
		$this->addRoutes($routes);
		return $this;
	}

	/**
	 * Get the added routes
	 *
	 * @return Traversable list of all routes
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Check if a route with a specific name exists
	 *
	 * @param string $name
	 * @return boolean true if route exists
	 */
	public function hasRoute($name)
	{
		return $this->routes->get($name) !== null;
	}

	/**
	 * Get a route by name
	 *
	 * @param string $name
	 * @return RouteInterface the route
	 */
	public function getRoute($name)
	{
		return $this->routes->get($name);
	}

	/**
	 * Create a route from array specifications.
	 *
	 * @param  array|ArrayAccess $specs
	 * @return RouteInterface
	 * @throws Exception\InvalidArgumentException
	 */
	protected function routeFromArray($specs)
	{
		if (!is_string($specs) && !is_array($specs) && !$specs instanceof ArrayAccess) {
			throw new Exception\InvalidArgumentException(
				'Route definition must be an string or array or ArrayAccess object');
		}

		if (is_string($specs)) {
			$seg = preg_split(
				'/ *(['.preg_quote(implode('', array_keys(self::$typeMap)), '/').'])> */',
				$specs, 2, PREG_SPLIT_DELIM_CAPTURE
			);
			$type = isset($seg[1]) && isset(self::$typeMap[$seg[1]])
				? self::$typeMap[$seg[1]] : 'Literal';
			$specs = array(
				$seg[0] => (isset($seg[2]) ? $seg[2] : '')
			);
		} elseif (isset($specs['type'])) {
			$type = $specs['type'];
			unset($specs['type']);
		} else {
			$rule = isset($specs['rule']) ? $specs['rule'] : key($specs);
			if (isset(self::$typeMap[$rule[0]])) {
				$type = self::$typeMap[$rule[0]];
			} elseif (preg_match('(/:[^/:]+|\[.*:.*\]|\*$)', $rule)) {
				$type = 'Segment';
			} else {
				$type = 'Literal';
			}
		}

		$route = __NAMESPACE__ . '\\Route\\' . ucfirst($type);

		if (!class_exists($route, true)) {
			throw new Exception\InvalidArgumentException("Type of route '$route' not found");
		}

		return call_user_func(array($route, 'factory'), $specs);
	}

	/**
	 * match
	 *
	 * @param  Input $input
	 * @return RouteMatch|null
	 */
	public function match(Input $input)
	{
		/** @var $route RouteInterface */
		foreach ($this->routes as $name => $route) {
			$match = $route->match($input);
			if ($match instanceof RouteMatch) {
				$match->setMatchedRouteName($name);
				$input->setRouteMatch($match);
				return $match;
			}
		}

		return null;
	}

	/**
	 * assemble
	 *
	 * @param string $name
	 * @param array $params
	 * @throws Exception\RuntimeException
	 * @return mixed
	 */
	public function assemble($name, array $params = array())
	{
		$route = $this->routes->get($name);

		if (!$route) {
			throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $name));
		}

		return $route->assemble($params);
	}
}