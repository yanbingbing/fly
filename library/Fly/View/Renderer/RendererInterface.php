<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Renderer;

use Fly\View\View;
use Fly\View\Resolver;

interface RendererInterface
{
    /**
     * Return the template engine object, if any
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @return mixed
     */
    public function getEngine();

    /**
     * Set the resolver used to map a template name to a resource the renderer may consume.
     *
     * @param  Resolver $resolver
     * @return $this
     */
    public function setResolver(Resolver $resolver);

    /**
     * Processes a view script and returns the output.
     *
     * @param  string|View $template
     * @param  null|array|\Traversable $values Values to use when rendering.
     * @return string The script output.
     */
    public function render($template, $values = null);

}