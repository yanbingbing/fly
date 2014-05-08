<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Uri\Exception;

class InvalidUriPartException extends InvalidArgumentException
{
    /**
     * Part-specific error codes
     *
     * @var int
     */
    const INVALID_SCHEME = 1;
    const INVALID_USER = 2;
    const INVALID_PASSWORD = 4;
    const INVALID_USERINFO = 6;
    const INVALID_HOSTNAME = 8;
    const INVALID_PORT = 16;
    const INVALID_AUTHORITY = 30;
    const INVALID_PATH = 32;
    const INVALID_QUERY = 64;
    const INVALID_FRAGMENT = 128;
}
