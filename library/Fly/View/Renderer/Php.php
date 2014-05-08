<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Renderer;

use Fly\Filter\FilterChain;
use Fly\View\Placeholder\Placeholder;
use Fly\View\View;
use Fly\View\Resolver;
use Fly\View\Plugin\PluginLoader;
use Fly\View\Plugin\PluginInterface;
use Fly\View\Exception;

class Php implements RendererInterface
{
    /**
     * @var array
     */
    private $__vars = array();

    /**
     * @var array Temporary variable stack; used when variables passed to render()
     */
    private $__varsCache = array();

    /**
     * Template resolver
     *
     * @var Resolver
     */
    private $__resolver;

    /**
     * @var FilterChain
     */
    private $__filterChain;

    /**
     * @var array Cache for the plugin call
     */
    private $__pluginCache = array();

    /**
     * @var PluginLoader
     */
    private $__pluginLoader;

    /**
     * @var bool
     */
    private $__inRender = false;

    /** @var Placeholder */
    private $__placeholder;

    /**
     * Return the template engine object, if any
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @return $this
     */
    public function getEngine()
    {
        return $this;
    }

    /**
     * Set script resolver
     *
     * @param  Resolver $resolver
     * @return $this
     */
    public function setResolver(Resolver $resolver)
    {
        $this->__resolver = $resolver;
        return $this;
    }

    /**
     * Retrieve template name
     *
     * @param  string $name
     * @return string
     */
    public function resolver($name)
    {
        if (null === $this->__resolver) {
            $this->setResolver(new Resolver);
        }

        return $this->__resolver->resolve($name);
    }

    /**
     * Set plugin loader
     *
     * @param PluginLoader $loader
     * @return $this
     */
    public function setPluginLoader(PluginLoader $loader)
    {
        $this->__pluginLoader = $loader;
        $loader->setRenderer($this);
        return $this;
    }

    /**
     * Get plugin loader
     *
     * @return PluginLoader
     */
    public function getPluginLoader()
    {
        if (null == $this->__pluginLoader) {
            $this->setPluginLoader(new PluginLoader);
        }
        return $this->__pluginLoader;
    }

    /**
     * Get plugin instance
     *
     * @param  string $name Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return PluginInterface
     */
    public function plugin($name, array $options = null)
    {
        return $this->getPluginLoader()->get($name, $options);
    }

    public function declarePlaceholder($name)
    {
        if ($this->__inRender) {
            $this->getPlaceholder()->declarePlaceholder($name);
        }
    }

    /**
     * Overloading: proxy to plugin
     *
     * @param  string $method
     * @param  array $argv
     * @return mixed
     */
    public function __call($method, $argv)
    {
        if (preg_match('/^(\w+)Placeholder$/i', $method, $matches)) {
            return $this->declarePlaceholder($matches[1]);
        }
        if (!isset($this->__pluginCache[$method])) {
            $this->__pluginCache[$method] = $this->plugin($method);
        }
        if (is_callable($this->__pluginCache[$method])) {
            return call_user_func_array($this->__pluginCache[$method], $argv);
        }
        return $this->__pluginCache[$method];
    }

    public function __set($key, $val)
    {
        if (strtolower($key) == 'placeholder') {
            $this->declarePlaceholder($val);
        }
    }

    /**
     * Set filter chain
     *
     * @param  FilterChain $filters
     * @return $this
     */
    public function setFilterChain(FilterChain $filters)
    {
        $this->__filterChain = $filters;
        return $this;
    }

    /**
     * Retrieve filter chain for post-filtering script content
     *
     * @return FilterChain
     */
    public function getFilterChain()
    {
        if (null === $this->__filterChain) {
            $this->setFilterChain(new FilterChain);
        }
        return $this->__filterChain;
    }

    /**
     * Set variable storage
     *
     * @param  array $variables
     * @return $this
     */
    public function setVars(array $variables)
    {
        $this->__vars = $variables;
        return $this;
    }

    /**
     * Get a single variable, or all variables
     *
     * @param  mixed $key
     * @return mixed
     */
    public function vars($key = null)
    {
        if (null === $key) {
            return $this->__vars;
        }
        return $this->__vars[$key];
    }

    /**
     * @return Placeholder
     */
    public function getPlaceholder()
    {
        if ($this->__placeholder === null) {
            $this->__placeholder = new Placeholder;
        }
        return $this->__placeholder;
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param  string|View $template
     * @param  null|array|\Traversable $values Values to use when rendering.
     * @return void|string The script output.
     */
    public function render($template, $values = null)
    {
        if ($template instanceof View) {
            $view = $template;
            $template = $view->getTemplate();
            $values = $view->getVariables();

            $options = $view->getOptions();
            foreach ($options as $setting => $value) {
                $method = 'set' . $setting;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
                unset($method, $setting, $value);
            }
            unset($options);

            unset($view);
        }

        if (empty($template)) {
            throw new Exception\RuntimeException(sprintf(
                '%s: Unable to render, template is empty', __METHOD__
            ));
        }

        $__file = $this->resolver($template);
        if (!$__file) {
            throw new Exception\RuntimeException(sprintf(
                '%s: Unable to render template "%s"; resolver could not resolve to a file',
                __METHOD__, $template
            ));
        }
        unset($template);

        $this->__varsCache[] = $this->vars();

        if (null !== $values) {
            $this->setVars($values);
        }
        unset($values);

        $__vars = $this->vars();
        if (array_key_exists('this', $__vars)) {
            unset($__vars['this']);
        }
        extract($__vars);
        unset($__vars);

        if ($this->__inRender) {
            include $__file;
            $this->setVars(array_pop($this->__varsCache));
        } else {
            $this->__inRender = true;
            $origLevel = ob_get_level();
            ob_start();
            try {
                include $__file;
                $__contents = array();
                while (($level = ob_get_level()) > $origLevel) {
                    $__contents[] = $this->getPlaceholder()->flushAtLevel($level) . ob_get_clean();
                }
                $this->getPlaceholder()->clean();
                $this->__inRender = false;
            } catch (\Exception $ex) {
                while (ob_get_level() > $origLevel) {
                    ob_end_clean();
                }
                $this->getPlaceholder()->clean();
                $this->__inRender = false;
                throw $ex;
            }

            $this->setVars(array_pop($this->__varsCache));

            return $this->getFilterChain()->filter(implode('', array_reverse($__contents)));
        }
    }
}