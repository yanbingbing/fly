<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc\Controller;

use Fly\Mvc\Application;
use Fly\Mvc\Router\RouteMatch;
use Fly\Mvc\Router\Router;
use Fly\Mvc\Output;
use Fly\View\View;
use Fly\View\ViewManager;
use Fly\Mvc\Input\Http as HttpInput;
use Fly\Mvc\Sender\Http as HttpSender;

abstract class AbstractController
{
	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var RouteMatch
	 */
	protected $routeMatch;

	/**
	 * @var HttpInput
	 */
	protected $input;

	/**
	 * @var HttpSender
	 */
	protected $sender;

	/**
	 * @var ViewManager
	 */
	protected $viewManager;

	public function __construct(Application $application)
	{
		$this->application = $application;
		$this->init();
	}

	protected function init()
	{
	}

	protected function beforeDispatch($method)
	{
	}

	public function notFoundAction()
	{
		return 404;
	}

	/**
	 * @return Router
	 */
	public function getRouter()
	{
		if ($this->router == null) {
			$this->router = $this->application->getRouter();
		}
		return $this->router;
	}

	/**
	 * @param RouteMatch $router
	 * @return $this
	 */
	public function setRouteMatch(RouteMatch $router)
	{
		$this->routeMatch = $router;
		return $this;
	}

	/**
	 * @return null|RouteMatch
	 */
	public function getRouteMatch()
	{
		return $this->routeMatch;
	}

	/**
	 * @return HttpInput
	 */
	public function getInput()
	{
		if ($this->input == null) {
			$this->input = $this->application->getInput();
		}
		return $this->input;
	}

	/**
	 * @return HttpSender
	 */
	public function getSender()
	{
		if ($this->sender == null) {
			$this->sender = new HttpSender;
		}
		return $this->sender;
	}

	/**
	 * @return ViewManager
	 * @throws Exception\RuntimeException
	 */
	protected function getViewManager()
	{
		if ($this->viewManager == null) {
			$this->viewManager = $this->application->getMountManager()->get('ViewManager');
			if (!$this->viewManager instanceof ViewManager) {
				throw new Exception\RuntimeException("Must suply an instance of viewManger");
			}
		}

		return $this->viewManager;
	}

	/**
	 * @param HttpInput $input
	 * @return string
	 */
	protected function getDefaultTemplate(HttpInput $input)
	{
		$controller = $input->get('controller');
		$controller = str_replace(array('.', '-', '_'), ' ', $controller);
		$controller = str_replace(' ', '', ucwords($controller));

		$action = $input->get('action');
		$action = str_replace(array('.', '-', '_'), ' ', $action);
		$action = lcfirst(str_replace(' ', '', ucwords($action)));

		return $controller . '/' . $action;
	}

	/**
	 * @param null|string|array $name
	 * @param array $params
	 * @param bool $absolute
	 * @throws Exception\RuntimeException
	 * @return string
	 */
	protected function url($name = null, $params = array(), $absolute = false)
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
			$match = $this->getRouteMatch();
			if ($match === null) {
				throw new Exception\RuntimeException('No RouteMatch instance or router name provided');
			}
			$name = $match->getMatchedRouteName();
			if ($name === null) {
				throw new Exception\RuntimeException('RouteMatch does not contain a matched route name');
			}
		}
		$input = $this->getInput();
		$url = $input->getBaseUrl() . $this->getRouter()->assemble($name, $params);
		if ($absolute) {
			$uri = clone $input->getUri();
			list($path, $query) = explode('?', $url);
			$uri->setPath($path);
			$uri->setQuery($query);
			$uri->setFragment(null);
			return $uri->toString();
		} else {
			return $url;
		}
	}

	/**
	 * @param null|string|array $url
	 * @param array $params
	 * @return int
	 */
	protected function redirect($url = null, array $params = null)
	{
		if (is_null($url) || is_array($url) || is_array($params)) {
			$url = $this->url($url, $params ?: array());
		}
		$sender = $this->getSender();
		$sender->getHeaders()->addHeaderLine('Location', $url);
		$sender->setStatus(302);
		return 302;
	}

	/**
	 * Dispatcher
	 */
	public function dispatch()
	{
		$input = $this->getInput();

		$action = $input->get('action');

		if (empty($action) || !method_exists($this, ($method = static::transformActionToMethod($action))))
		{
			$action = 'notFound';
			$input->set('action', $action);
			$method = static::transformActionToMethod($action);
		}

		if (false === $this->beforeDispatch($method)) {// 直接返回
			return;
		}

		$obLevel = ob_get_level();
		ob_start();
		try {
			$return = $this->$method();
		} catch (\Exception $ex) {
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
			throw $ex;
		}

		while (ob_get_level() - $obLevel > 1) {
			ob_end_flush();
		}
		$text = ob_get_clean();

		$sender = $this->getSender();

		switch (true) {
			/*
			/// redirect
			case method_exists($sender, 'isRedirect') && $sender->isRedirect():
				return;
			*/
			// status code
			case is_int($return):
				if (100 > $return || 599 < $return) {
					$return = 200;
				}
				$sender->setStatus($return);
				$sender->setContent($text);
				return;
			// string or stream
			case is_string($return) || (is_resource($return) && get_resource_type($return) == 'stream'):
				$sender->setContent($return);
				return;
			// view
			case $return instanceof View:
				/** @var $return View */
				if (!$return->getTemplate()) {
					$return->setTemplate($this->getDefaultTemplate($input));
				}
				$output = new Output\ViewOutput($return, $this->getViewManager(), $input);
				$sender->setContent($output);
				return;
			// output
			case $return instanceof Output\OutputInterface:
				$sender->setContent($return);
				return;
			// json - array or traversable
			case is_array($return) || $return instanceof \Traversable:
				$output = new Output\JsonOutput($return);
				if (($callback = $input->get('jsoncallback'))) {
					$output->setCallback($callback);
				}
				$sender->setContent($output);
				return;
			// ob of echo
			case $text !== '':
				$sender->setContent($text);
				return;
			// auto find view
			default:
				$template = $this->getDefaultTemplate($input);
				$viewManager = $this->getViewManager();
				if ($viewManager->getResolver()->resolve($template) !== false) {
					$view = new View(null, $template);
					$output = new Output\ViewOutput($view, $viewManager, $input);
					$sender->setContent($output);
				}
				return;
		}
	}

	/**
	 * Transform an "action" token into a method name
	 *
	 * @param  string $action
	 * @return string
	 */
	protected static function transformActionToMethod($action)
	{
		$method = str_replace(array('.', '-', '_'), ' ', $action);
		$method = ucwords($method);
		$method = str_replace(' ', '', $method);
		$method = lcfirst($method);
		$method .= 'Action';

		return $method;
	}
}