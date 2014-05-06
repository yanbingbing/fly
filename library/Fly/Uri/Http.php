<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Uri;

class Http extends Uri
{
	/**
	 * @var array
	 */
	protected static $validSchemes = array('http', 'https');

	/**
	 * @var array
	 */
	protected static $defaultPorts = array('http' => 80, 'https' => 443);

	/**
	 * User name as provided in authority of URI
	 *
	 * @var null|string
	 */
	protected $user;

	/**
	 * Password as provided in authority of URI
	 *
	 * @var null|string
	 */
	protected $password;

	/**
	 * Get the username part (before the ':') of the userInfo URI part
	 *
	 * @return null|string
	 */
	public function getUser()
	{
		if (null !== $this->user) {
			return $this->user;
		}

		$this->parseUserInfo();
		return $this->user;
	}

	/**
	 * Get the password part (after the ':') of the userInfo URI part
	 *
	 * @return string
	 */
	public function getPassword()
	{
		if (null !== $this->password) {
			return $this->password;
		}

		$this->parseUserInfo();
		return $this->password;
	}

	/**
	 * Set the username part (before the ':') of the userInfo URI part
	 *
	 * @param  string $user
	 * @return Http
	 */
	public function setUser($user)
	{
		$this->user = $user;
		return $this;
	}

	/**
	 * Set the password part (after the ':') of the userInfo URI part
	 *
	 * @param  string $password
	 * @return Http
	 */
	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}

	/**
	 * Parse the user info into username and password segments
	 * Parses the user information into username and password segments, and
	 * then sets the appropriate values.
	 *
	 * @return void
	 */
	protected function parseUserInfo()
	{
		// No user information? we're done
		if (null === $this->userInfo) {
			return;
		}

		// If no ':' separator, we only have a username
		if (false === strpos($this->userInfo, ':')) {
			$this->setUser($this->userInfo);
			return;
		}

		// Split on the ':', and set both user and password
		list($user, $password) = explode(':', $this->userInfo, 2);
		$this->setUser($user);
		$this->setPassword($password);
	}

	/**
	 * Return the URI port
	 * If no port is set, will return the default port according to the scheme
	 *
	 * @return integer
	 */
	public function getPort()
	{
		if (empty($this->port)) {
			if (array_key_exists($this->scheme, static::$defaultPorts)) {
				return static::$defaultPorts[$this->scheme];
			}
		}
		return $this->port;
	}

	/**
	 * Parse a URI string
	 *
	 * @param  string $uri
	 * @return Http
	 */
	public function parse($uri)
	{
		parent::parse($uri);

		if (empty($this->path)) {
			$this->path = '/';
		}

		return $this;
	}
}