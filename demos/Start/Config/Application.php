<?php
/**
 * Start config file
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */
return array(
	'mounts' => array(
		'ViewManager' => function(){
			$manager = new Fly\View\ViewManager;
			$manager->getResolver()->addPath(APPLICATION_DIR . '/View');

			return $manager;
		},
		'ControllerLoader' => function(){
			$loader = new Fly\Mvc\Controller\ControllerLoader;
			$loader->registerPath(APPLICATION_DIR . '/Controller',  'Demos\Start\Controller');
			return $loader;
		},
		'RowLoader' => function(){
			$loader = new Fly\Db\Row\RowLoader;
			$loader->registerPath(APPLICATION_DIR . '/Model', 'Demos\Start\Model');
			return $loader;
		}
	),
	'inits' => function(){
		$loader = Fly\Loader\Loader::getInstance();
		$loader->registerNamespace('Demos\Start\Library', APPLICATION_DIR . '/Library');
	},
	'routes' => array(
		'default' => '/[:controller[/:action]]/* -> index#index',
		'index' => '/index => index#index'
	)
);