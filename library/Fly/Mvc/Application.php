<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Mvc;

use Fly\MountManager\MountManager;
use Fly\Config\Config;
use Fly\Mvc\Input\Http as HttpInput;
use Fly\Mvc\Sender\Http as HttpSender;
use Fly\Mvc\Router\Router;
use Fly\Mvc\Controller\ControllerLoader;

class Application
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MountManager
     */
    protected $mountManager;

    /**
     * @var HttpInput
     */
    protected $input;

    /**
     * @var HttpSender
     */
    protected $sender;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ControllerLoader
     */
    protected $controllerLoader;

    /**
     * @param string|array|\Traversable $configure
     */
    public function __construct($configure = null)
    {
        $this->config = new Config($configure);
        if (self::$sharedConfig) {
            $sharedConfig = new Config(self::$sharedConfig);
            $this->config = $sharedConfig->merge($this->config);
        }

        $this->mountManager = MountManager::getInstance();

        if (isset($this->config['mounts'])) {
            foreach ((array)$this->config['mounts'] as $name => $factory) {
                $this->mountManager->mount($name, $factory);
            }
        }

        if (isset($this->config['inits'])) {
            if (is_callable($this->config['inits'])) {
                call_user_func($this->config['inits'], $this);
            } elseif (is_array($this->config['inits']) || $this->config['inits'] instanceof \Traversable) {
                foreach ($this->config['inits'] as $init) {
                    if (is_callable($init)) {
                        call_user_func($init, $this);
                    }
                }
            }
        }
    }

    /**
     * @var string|array|\Traversable
     */
    protected static $sharedConfig;
    /**
     * @param string|array|\Traversable $configure
     */
    public static function setSharedConfig($configure)
    {
        self::$sharedConfig = $configure;
    }

    /**
     * Get config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return MountManager
     */
    public function getMountManager()
    {
        return $this->mountManager;
    }

    /**
     * Set input instance
     *
     * @param HttpInput $input
     * @return $this
     */
    public function setInput(HttpInput $input)
    {
        $this->input = $input;
        $this->mountManager->mount('Input', $input);

        return $this;
    }

    /**
     * Get input
     *
     * @return HttpInput
     */
    public function getInput()
    {
        if (!$this->input) {
            $input = $this->mountManager->get('Input');
            if (!($input instanceof HttpInput)) {
                $input = new HttpInput;
                $this->mountManager->mount('Input', $input);
            }
            $this->input = $input;
        }
        return $this->input;
    }

    /**
     * Get Sender
     *
     * @return HttpSender
     */
    public function getSender()
    {
        if (!$this->sender) {
            $this->sender = new HttpSender;
        }
        return $this->sender;
    }

    /**
     * Set controller loader
     *
     * @param ControllerLoader $loader
     * @return $this
     */
    public function setControllerLoader(ControllerLoader $loader)
    {
        $this->controllerLoader = $loader;
        $this->mountManager->mount('ControllerLoader', $loader);

        return $this;
    }

    /**
     * Get controller loader
     *
     * @return ControllerLoader
     */
    public function getControllerLoader()
    {
        if (!$this->controllerLoader) {
            $loader = $this->mountManager->get('ControllerLoader');
            if (!($loader instanceof ControllerLoader)) {
                $loader = new ControllerLoader;
                $this->mountManager->mount('ControllerLoader', $loader);
            }
            if (isset($this->config['controller_paths'])) {
                $loader->registerPath($this->config['controller_paths']);
            }
            $this->controllerLoader = $loader;
        }

        return $this->controllerLoader;
    }

    /**
     * Set router
     *
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        $this->mountManager->mount('Router', $router);

        return $this;
    }

    /**
     * Get router
     *
     * @return Router
     */
    public function getRouter()
    {
        if (!$this->router) {
            $router = $this->mountManager->get('Router');
            if (!($router instanceof Router)) {
                $router = new Router;
                $this->mountManager->mount('Router', $router);
            }
            if (isset($this->config['routes'])) {
                $router->addRoutes($this->config['routes']);
            }
            $this->router = $router;
        }

        return $this->router;
    }

    /**
     * Run the application
     *
     * @throws Exception\DispatchException
     */
    public function run()
    {
        $input = $this->getInput();

        $match = $this->getRouter()->match($input);

        $loader = $this->getControllerLoader();

        $controllerName = $input->get('controller');

        if (empty($controllerName) || !($controller = $loader->get($controllerName, $this))) {
            $this->getSender()->setStatus(404)->send(true);
            return;
        }

        if ($match) {
            $controller->setRouteMatch($match);
        }

        try {
            $controller->dispatch();
            $controller->getSender()->send();
        } catch (\Exception $ex) {
            throw new Exception\DispatchException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}