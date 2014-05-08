<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Router\Route;

use Fly\Mvc\Input\Http as Input;
use Fly\Mvc\Router\RouteMatch;

interface RouteInterface
{

    /**
     * Create a new route with given options.
     *
     * @param  array|\Traversable $options
     * @return void
     */
    public static function factory($options = array());

    /**
     * Match a given input.
     *
     * @param  Input $input
     * @return RouteMatch
     */
    public function match(Input $input);

    /**
     * Assemble the route.
     *
     * @param  array $params
     * @return mixed
     */
    public function assemble(array $params = array());
}