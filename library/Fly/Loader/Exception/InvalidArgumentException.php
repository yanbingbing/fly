<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
