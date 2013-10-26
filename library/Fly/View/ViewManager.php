<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View;

use Fly\View\Renderer\RendererInterface;

class ViewManager
{

	/**
	 * @var Resolver
	 */
	protected $resolver;

	/**
	 * @var RendererInterface
	 */
	protected $renderer;

	/**
	 * Set Renderer
	 *
	 * @param RendererInterface $renderer
	 * @return $this
	 */
	public function setRenderer(RendererInterface $renderer)
	{
		$this->renderer = $renderer;
		return $this;
	}

	/**
	 * Get Renderer
	 *
	 * @return Renderer\Php|RendererInterface
	 */
	public function getRenderer()
	{
		if (!$this->renderer instanceof RendererInterface) {
			$this->renderer = new Renderer\Php;
		}
		return $this->renderer;
	}

	/**
	 * @return Resolver
	 */
	public function getResolver()
	{
		if (!$this->resolver instanceof Resolver) {
			$this->resolver = new Resolver;
		}
		return $this->resolver;
	}

	/**
	 * Render the view
	 *
	 * @param View $view
	 * @return string
	 */
	public function render(View $view)
	{
		return $this->getRenderer()->setResolver($this->getResolver())->render($view);
	}
}