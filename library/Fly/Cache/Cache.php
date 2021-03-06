<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2013 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Cache;


abstract class Cache
{

    /**
     * @param array|\ArrayAccess $options
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @return Storage\StorageInterface
     */
    public static function factory($options)
    {
        if (!is_array($options) || $options instanceof \ArrayAccess) {
            throw new Exception\InvalidArgumentException(
                'The factory needs an associative array or a ArrayAccess object as an argument'
            );
        }

        // instantiate the adapter
        if (!isset($options['storage'])) {
            throw new Exception\InvalidArgumentException('Missing "storage"');
        }
        $storageName = $options['storage'];
        unset($options['storage']);
        $storageName = __NAMESPACE__ . '\\Storage\\' . self::normalizeName($storageName);

        if (!class_exists($storageName, true)) {
            throw new Exception\RuntimeException("Storage class '$storageName' not found");
        }

        if (!is_subclass_of($storageName, __NAMESPACE__ . '\\Storage\\StorageInterface')) {
            throw new Exception\RuntimeException("Storage '$storageName' is not implements StorageInterface");
        }

        /** @var $storage Storage\StorageInterface */
        $storage = new $storageName;

        $storage->setOptions($options);

        return $storage;
    }

    protected static function normalizeName($name)
    {
        $name = str_replace(array('-', '_', '.'), ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

}