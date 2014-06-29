<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

class Style extends AbstractPlugin
{
    public function __invoke($href = null, array $attrs = array(), $placement = 'APPEND')
    {
        $renderer = $this->getRenderer();
        $container = $renderer->getPlaceholder()->getContainer('style');

        if (null !== $href) {
            $placement = strtolower($placement);
            switch ($placement) {
                case 'set':
                case 'prepend':
                case 'append':
                    $action = $placement . 'File';
                    break;
                default:
                    $action = 'appendFile';
                    break;
            }
            $container->$action($href, $attrs);
        }

        return $container;
    }
}