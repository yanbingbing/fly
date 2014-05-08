<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

use Fly\View\Renderer\Php as Renderer;

interface PluginInterface
{
    /**
     * Set the Renderer object
     *
     * @param Renderer $renderer
     * @return $this
     */
    public function setRenderer(Renderer $renderer);

    /**
     * Get the Renderer object
     *
     * @return Renderer
     */
    public function getRenderer();
}