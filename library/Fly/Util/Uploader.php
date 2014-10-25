<?php
/**
 * ywy
 *
 * @copyright Copyright (c) 2014 github.com/yanbingbing
 */

namespace Fly\Util;

class Uploader
{
    protected static $errors = array(
        UPLOAD_ERR_INI_SIZE => 'Larger than upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE => 'Larger than form MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL => 'Partial upload.',
        UPLOAD_ERR_NO_FILE => 'No file.',
        UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory.',
        UPLOAD_ERR_CANT_WRITE => 'Canot write to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
    );

    /**
     * @var UploadStorage
     */
    protected $storage;

    public function __construct(UploadStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param $file
     * @return UploadFile
     * @throws Exception\RuntimeException
     */
    public function execute($file, $type = null)
    {
        if (empty($file) || !isset($file['tmp_name'])) {
            throw new Exception\RuntimeException(self::$errors[UPLOAD_ERR_NO_FILE], UPLOAD_ERR_NO_FILE);
        }

        if (is_array($file['tmp_name'])) {
            foreach ($file as $key => $val) {
                $file[$key] = end($val);
            }
        }

        if (!empty($file['error'])) {
            throw new Exception\RuntimeException(self::$errors[$file['error']], $file['error']);
        }

        return $this->storage->save($file['tmp_name'], $file['name'], $type);
    }
}