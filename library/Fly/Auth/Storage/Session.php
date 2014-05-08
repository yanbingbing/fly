<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Auth\Storage;

use Fly\Session\Session as SessionManager;

class Session implements StorageInterface
{
    /**
     * Session object member
     *
     * @var mixed
     */
    protected $member;

    /**
     * @var array
     */
    protected $session;

    public function __construct($member = 'Top_Auth_Storage')
    {
        SessionManager::start();
        $this->session = & $_SESSION;
        $this->member = $member;
    }

    /**
     * Returns the name of the session object member
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
        return !isset($this->session[$this->member]);
    }

    /**
     * @return mixed
     */
    public function read()
    {
        return isset($this->session[$this->member]) ? $this->session[$this->member] : null;
    }

    /**
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->session[$this->member] = $contents;
    }

    /**
     * @return void
     */
    public function clear()
    {
        unset($this->session[$this->member]);
    }
}