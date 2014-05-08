<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Auth\Storage;

class Cookie implements StorageInterface
{
    /**
     * Cookie object member
     *
     * @var mixed
     */
    protected $member;

    /**
     * @var array
     */
    protected $cookie;

    /**
     * @var array
     */
    protected $cookieOptions = array(
        'expire' => null,
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'httponly' => true
    );

    public function __construct($member = 'Top_Auth_Storage', array $cookieOptions = array())
    {
        $this->cookie = & $_COOKIE;
        $this->member = $member;
        $this->cookieOptions = array_merge($this->cookieOptions, $cookieOptions);
    }

    /**
     * Returns the name of the cookie object member
     *
     * @return string
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !isset($this->cookie[$this->member]);
    }

    /**
     * @return mixed
     */
    public function read()
    {
        return isset($this->cookie[$this->member]) ? $this->cookie[$this->member] : null;
    }

    /**
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->cookie[$this->member] = $contents;

        $expire = time() + (int)$this->cookieOptions['expire'];
        $path = $this->cookieOptions['path'];
        $domain = $this->cookieOptions['domain'];
        $secure = (bool)$this->cookieOptions['secure'];
        $httponly = (bool)$this->cookieOptions['httponly'];

        setcookie($this->member, $contents, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @return void
     */
    public function clear()
    {
        unset($this->cookie[$this->member]);

        $expire = time() - 86400;
        $path = $this->cookieOptions['path'];
        $domain = $this->cookieOptions['domain'];
        $secure = (bool)$this->cookieOptions['secure'];
        $httponly = (bool)$this->cookieOptions['httponly'];

        setcookie($this->member, null, $expire, $path, $domain, $secure, $httponly);
    }
}