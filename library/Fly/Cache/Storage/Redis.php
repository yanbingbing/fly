<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Cache\Storage;

use Redis as RedisSource;
use Fly\Cache\Exception;

class Redis extends AbstractStorage
{
    const DEFAULT_PORT = 6379;

    const DEFAULT_PERSISTENT = true;

    /**
     * @var RedisSource|array
     */
    protected $resource;

    /**
     * @param array|\ArrayAccess|RedisSource $resource
     */
    public function setResource($resource)
    {
        if ($resource instanceof RedisSource) {
            try {
                $resource->ping();
            } catch (\RedisException $ex) {
                throw new Exception\InvalidArgumentException('Invalid redis resource', $ex->getCode(), $ex);
            }
            if ($resource->getOption(RedisSource::OPT_SERIALIZER) == RedisSource::SERIALIZER_NONE) {
                $resource->setOption(RedisSource::OPT_SERIALIZER, RedisSource::SERIALIZER_PHP);
            }

            $this->resource = $resource;
            return $this;
        }
        if (is_string($resource)) {
            $resource = explode(':', $resource);
        }
        if (!is_array($resource) && !($resource instanceof \ArrayAccess)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects an string, array, or Traversable argument; received "%s"',
                __METHOD__, (is_object($resource) ? get_class($resource) : gettype($resource))
            ));
        }

        $host = $port = $auth = $persistent = null;
        // array(<host>[, <port>[, <auth>[, <persistent>]]])
        if (isset($resource[0])) {
            list($host, $port) = explode(':', (string)$resource[0]);
            if (isset($resource[1])) {
                $port = (int)$resource[1];
            }
            if (isset($resource[2])) {
                $auth = (string)$resource[2];
            }
            if (isset($resource[3])) {
                $persistent = (bool)$resource[3];
            }
        } // array('host' => <host>[, 'port' => <port>][, 'auth' => <auth>][, 'persistent' => <persistent>])
        elseif (isset($resource['host'])) {
            list($host, $port) = explode(':', (string)$resource['host']);
            if (isset($resource['port'])) {
                $port = (int)$resource['port'];
            }
            if (isset($resource['auth'])) {
                $auth = (string)$resource['auth'];
            }
            if (isset($resource['persistent'])) {
                $persistent = (bool)$resource['persistent'];
            }
        }

        if (!$host) {
            throw new Exception\InvalidArgumentException('Invalid redis resource, option "host" must be given');
        }

        $this->resource = array(
            'host' => $host,
            'port' => $port ?: self::DEFAULT_PORT,
            'auth' => $auth,
            'persistent' => $persistent === null ? self::DEFAULT_PERSISTENT : $persistent
        );
    }

    /**
     * @return RedisSource
     * @throws Exception\RuntimeException
     */
    public function getResource()
    {
        if (!$this->resource) {
            throw new Exception\RuntimeException('Redis resource must be set');
        }
        if (!($this->resource instanceof RedisSource)) {
            $resource = new RedisSource;

            $ret = $this->resource['persistent']
                ? $resource->pconnect($this->resource['host'], $this->resource['port'])
                : $resource->connect($this->resource['host'], $this->resource['port']);
            if (!$ret) {
                throw new Exception\RuntimeException(sprintf(
                    'Cannot connect to redis server on %s:%d',
                    $this->resource['host'], $this->resource['port']
                ));
            }
            if (isset($this->resource['auth']) && !$resource->auth($this->resource['auth'])) {
                throw new Exception\RuntimeException(sprintf(
                    'Auth failed on %s:%d, auth: %s',
                    $this->resource['host'], $this->resource['port'], $this->resource['auth']
                ));
            }

            $resource->setOption(RedisSource::OPT_SERIALIZER, RedisSource::SERIALIZER_PHP);

            $this->resource = $resource;
        }
        return $this->resource;
    }

    /**
     * @param string $key
     * @return mixed|false If key didn't exist, FALSE is returned
     */
    public function get($key)
    {
        $redis = $this->getResource();
        return $redis->get($this->getNamespace() . $key);
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @param null|int $ttl in second
     * @return bool
     */
    public function set($key, $value = null, $ttl = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if ($this->set($k, $v, $ttl) === false) {
                    return false;
                }
            }
            return true;
        }

        $redis = $this->getResource();
        $key = $this->getNamespace() . $key;
        if ($ttl === null) {
            $ttl =  $this->getTtl();
        }
        if ($ttl > 0) {
            return $redis->setex($key, $ttl, $value);
        } else {
            return $redis->set($key, $value);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $redis = $this->getResource();
        return $redis->exists($this->getNamespace() . $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function remove($key)
    {
        $redis = $this->getResource();
        return $redis->delete($this->getNamespace() . $key) > 0;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function touch($key)
    {
        $redis = $this->getResource();
        $key = $this->getNamespace() . $key;
        if ($this->getTtl() > 0) {
            return $redis->expireAt($key, time() + $this->getTtl());
        } else {
            return $redis->persist($key);
        }
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function increment($key, $value)
    {
        $redis = $this->getResource();
        $key = $this->getNamespace() . $key;
        if (is_float($value)) {
            return $redis->incrByFloat($key, $value);
        } else {
            return $redis->incrBy($key, $value);
        }
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function decrement($key, $value)
    {
        $redis = $this->getResource();
        $key = $this->getNamespace() . $key;
        if (is_float($value)) {
            return $redis->decrByFloat($key, $value);
        } else {
            return $redis->decrBy($key, $value);
        }
    }
}