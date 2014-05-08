<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\View\Plugin;

class HeadScript extends AbstractPlugin
{
    public function __invoke($src = null, $type = 'text/javascript', array $attrs = array(), $placement = 'APPEND')
    {
        $renderer = $this->getRenderer();
        $container = $renderer->getPlaceholder()->getContainer('HeadScript');

        if (null !== $src) {
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
            $container->$action($src, $type, $attrs);
        }

        return $container;
    }
}