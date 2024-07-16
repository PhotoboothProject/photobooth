<?php

namespace Photobooth\Utility;

class FileUtility
{
    public const DIRECTORY_PERMISSIONS = 0755;

    public const FILE_UPLOAD_ERROR_MESSAGES = array(
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    );

    public static function createDirectory(string $directory): void
    {
        if (!file_exists($directory) && !is_dir($directory)) {
            if (!mkdir($directory, self::DIRECTORY_PERMISSIONS, true)) {
                throw new \Exception('Failed to create directory: ' . $directory);
            }
        } elseif (!is_writable($directory)) {
            if (!chmod($directory, self::DIRECTORY_PERMISSIONS)) {
                throw new \Exception('Failed to change permissions of directory: ' . $directory);
            }
        }
    }

    public static function getErrorMessage(int $errorCode): string
    {
        return self::FILE_UPLOAD_ERROR_MESSAGES[$errorCode];
    }
}
