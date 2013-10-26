<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Validator;

class Email extends AbstractValidator
{
	public function isValid($value)
	{
		return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
	}
}