<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Validator;

class NotNull extends AbstractValidator
{
    public function isValid($value)
    {
        return null !== $value;
    }
}