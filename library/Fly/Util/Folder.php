<?php
/**
 * Fly Framework
 *
 * @copyright Copyright (c) 2014 Bingbing. (http://yanbingbing.com)
 */

namespace Fly\Util;

class Folder
{
    const FOLDER_ONLY = 'folder';

    const FILES_ONLY = 'files';

    const PATH_ABSOLUTE = 'absolute';

    const PATH_RELATIVE = 'relative';

    public static function readAsArray($folder, $flags = null)
    {
        $result = array();

        $handler = opendir($folder);
        if (!$handler) {
            throw new Exception\RuntimeException('Open folder failed');
        }

        while (($item = readdir($handler)) !== false) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $itemname = "{$folder}/{$item}";
            $is_file = is_file($itemname);
            $is_dir = is_dir($itemname);

            if (!$flags
                || ($is_file && $flags == static::FILES_ONLY)
                || ($is_dir && $flags == static::FOLDER_ONLY)) {
                $result[] = $itemname;
            }

            if ($is_dir) {
                $result = array_merge($result, static::readAsArray($itemname, $flags));
            }
        }
        closedir($handler);

        return $result;
    }

    public static function create($folder, $mode = 0755)
    {
        if ($folder === '') {
            throw new Exception\RuntimeException('Cannot create empty folder');
        }

        if (is_dir($folder)) {
            return true;
        }

        static::create(dirname($folder), $mode);

        if (!mkdir($folder, $mode)) {
            throw new Exception\RuntimeException(sprintf('Create folder %s with mode %o failed', $folder, $mode));
        }

        return true;
    }

    public static function delete($folder)
    {
        if (!is_dir($folder)) {
            return false;
        }

        $handler = opendir($folder);
        if (!$handler) {
            throw new Exception\RuntimeException('Open folder failed');
        }

        while (($item = readdir($handler)) !== false) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $itemname = "{$folder}/{$item}";

            if (is_dir($itemname)) {
                self::delete($itemname);
            } else {
                if (unlink($itemname) === false) {
                    throw new Exception\RuntimeException(sprintf('Delete file %s failed', $itemname));
                }
            }
        }
        closedir($handler);

        if (rmdir($folder) === false) {
            throw new Exception\RuntimeException(sprintf('Delete folder %s failed', $folder));
        }

        return true;
    }
}