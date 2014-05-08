<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View;

use Traversable;

class View
{
    /**
     * Template to use when rendering this
     *
     * @var string
     */
    protected $template = null;

    /**
     * @var array
     */
    protected $variables = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Constructor
     *
     * @param null|array|object $variables
     * @param null|string $template
     * @param null|array $options
     */
    public function __construct($variables = null, $template = null, array $options = null)
    {
        if (null !== $variables) {
            $this->setVariables($variables, true);
        }

        if (null !== $template) {
            $this->setTemplate($template);
        }

        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Set renderer options/hints en masse
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get renderer options/hints
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array|object $variables
     * @param bool $overwrite
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function setVariables($variables, $overwrite = false)
    {
        if (is_object($variables)) {
            if (method_exists($variables, 'toArray')) {
                $variables = $variables->toArray();
            } elseif ($variables instanceof Traversable) {
                $temp = array();
                foreach ($variables as $key => $val) {
                    $temp[$key] = $val;
                }
                $variables = $temp;
            } else {
                $variables = (array)$variables;
            }
        }
        if (!is_array($variables)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects an array, or Traversable argument; received "%s"',
                __METHOD__, gettype($variables)
            ));
        }

        if ($overwrite) {
            $this->variables = $variables;
        } else {
            foreach ($variables as $key => $value) {
                $this->setVariable($key, $value);
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->setVariable($name, $value);
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getVariable($name);
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->variables[$name]);
    }

    /**
     * Get a single variable
     *
     * @param  string $name
     * @param  mixed|null $default (optional) default value if the variable is not present.
     * @return mixed
     */
    public function getVariable($name, $default = null)
    {
        $name = (string)$name;
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        return $default;
    }

    /**
     * Set view variable
     *
     * @param  string $name
     * @param  mixed $value
     * @return $this
     */
    public function setVariable($name, $value)
    {
        $this->variables[(string)$name] = $value;
        return $this;
    }

    /**
     * @param string|array|object $spec
     * @param null|mixed $value
     * @return $this
     */
    public function assign($spec, $value = null)
    {
        if (is_string($spec)) {
            $this->setVariable($spec, $value);
        } else {
            $this->setVariables($spec);
        }
        return $this;
    }

    /**
     * Get view variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Clear all variables
     * Resets the internal variable container to an empty container.
     *
     * @return $this
     */
    public function clearVariables()
    {
        $this->variables = array();
        return $this;
    }

    /**
     * Set the template to be used by this model
     *
     * @param  string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = (string)$template;
        return $this;
    }

    /**
     * Get the template to be used by this model
     *
     * @return null|string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}