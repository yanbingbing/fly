<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Db\Adapter\Exception;

use Fly\Db\Exception;

class InvalidConnectionParametersException extends RuntimeException implements ExceptionInterface
{

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param string $message
     * @param array $parameters
     */
    public function __construct($message, $parameters)
    {
        parent::__construct($message);
        $this->parameters = $parameters;
    }
}
