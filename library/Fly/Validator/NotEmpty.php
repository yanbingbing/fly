<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Validator;

class NotEmpty extends AbstractValidator
{
    public function isValid($value)
    {
        return empty($value) === false;
    }
}