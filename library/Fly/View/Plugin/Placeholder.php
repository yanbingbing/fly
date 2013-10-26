<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

class Placeholder extends AbstractPlugin
{

	public function __invoke($name, $value = null, $type = 'APPEND')
	{
		$renderer = $this->getRenderer();
		$container = $renderer->getPlaceholder()->getContainer($name);

		if (null !== $value) {
			switch (strtolower($type)) {
				case 'set':
					$container->set($value);
					break;
				case 'prepend':
					$container->prepend($value);
					break;
				case 'append':
				default:
					$container->append($value);
					break;
			}
		}
		return $container;
	}
}