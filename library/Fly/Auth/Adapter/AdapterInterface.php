<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Auth\Adapter;

interface AdapterInterface
{
    /**
     * Performs an authentication attempt
     *
     * @return \Fly\Auth\Result
     */
    public function authenticate();
}
