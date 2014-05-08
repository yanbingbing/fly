<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Config\Reader;

interface ReaderInterface
{
    /**
     * Read from a file and create an array
     *
     * @param  string $filename
     * @return array
     */
    public function fromFile($filename);
}
