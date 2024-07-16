<?php

namespace Photobooth\Utility;

class FileUtility
{
    public const DIRECTORY_PERMISSIONS = 0755;

    public const FILE_UPLOAD_ERROR_MESSAGES = [
        UPLOAD_ERR_OK => 'file_upload:no_error',
        UPLOAD_ERR_INI_SIZE => 'file_upload:error_ini_size',
        UPLOAD_ERR_FORM_SIZE => 'file_upload:error_form_size',
        UPLOAD_ERR_PARTIAL => 'file_upload:error_partial',
        UPLOAD_ERR_NO_FILE => 'file_upload:error_no_file',
        UPLOAD_ERR_NO_TMP_DIR => 'file_upload:error_no_tmp_dir',
        UPLOAD_ERR_CANT_WRITE => 'file_upload:error_cant_write',
        UPLOAD_ERR_EXTENSION => 'file_upload:error_extension',
    ];

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
