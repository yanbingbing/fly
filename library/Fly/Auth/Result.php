<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Auth;

class Result
{
	/**
	 * General Failure
	 */
	const FAILURE = 1;

	/**
	 * Failure due to identity not being found.
	 */
	const FAILURE_IDENTITY_NOT_FOUND = 2;

	/**
	 * Failure due to empty identity being supplied.
	 */
	const FAILURE_IDENTITY_EMPTY = 3;

	/**
	 * Failure due to identity being ambiguous.
	 */
	const FAILURE_IDENTITY_AMBIGUOUS = 4;

	/**
	 * Failure due to empty credential being supplied.
	 */
	const FAILURE_CREDENTIAL_EMPTY = 5;

	/**
	 * Failure due to invalid credential being supplied.
	 */
	const FAILURE_CREDENTIAL_INVALID = 6;

	/**
	 * Failure due to uncategorized reasons.
	 */
	const FAILURE_UNCATEGORIZED = 7;

	/**
	 * Authentication success.
	 */
	const SUCCESS = 0;

	/**
	 * Authentication result code
	 *
	 * @var int
	 */
	protected $code = self::FAILURE;

	/**
	 * The identity used in the authentication attempt
	 *
	 * @var mixed
	 */
	protected $identity;

	/**
	 * Sets the result code, identity
	 *
	 * @param int $code
	 * @param mixed $identity
	 */
	public function __construct($code, $identity)
	{
		$code = (int) $code;

		if ($code > self::FAILURE_UNCATEGORIZED || $code < 0) {
			$code = self::FAILURE;
		}

		$this->code = $code;
		$this->identity = $identity;
	}

	/**
	 * Returns whether the result represents a successful authentication attempt
	 *
	 * @return bool
	 */
	public function isValid()
	{
		return $this->code === self::SUCCESS;
	}

	/**
	 * getCode() - Get the result code for this authentication attempt
	 *
	 * @return int
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns the identity used in the authentication attempt
	 *
	 * @return mixed
	 */
	public function getIdentity()
	{
		return $this->identity;
	}
}
