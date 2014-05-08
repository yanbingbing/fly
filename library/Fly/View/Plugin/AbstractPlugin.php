<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

use Fly\View\Renderer\Php as Renderer;

abstract class AbstractPlugin implements PluginInterface
{
    /**
     * Renderer object
     *
     * @var Renderer
     */
    protected $renderer = null;

    /**
     * Set the Renderer object
     *
     * @param  Renderer $renderer
     * @return $this
     */
    public function setRenderer(Renderer $renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Get the renderer object
     *
     * @return Renderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
}