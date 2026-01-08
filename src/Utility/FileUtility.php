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
        if (array_key_exists($errorCode, self::FILE_UPLOAD_ERROR_MESSAGES) === false) {
            return 'file_upload:error_unknown';
        }
        return self::FILE_UPLOAD_ERROR_MESSAGES[$errorCode];
    }

    /**
     * Move a temporary file into data/tmp/deleted for safekeeping.
     *
     * @return bool true if moved, false if no move performed
     */
    public static function moveToDeleted(string $fromFile): bool
    {
        $basename = basename($fromFile);
        $basepath = dirname($fromFile);
        if (!is_file($fromFile)) {
            return false;
        }

        $targetDir = $basepath . DIRECTORY_SEPARATOR . 'deleted';
        self::createDirectory($targetDir);

        $target = $targetDir . DIRECTORY_SEPARATOR . $basename;
        if (file_exists($target)) {
            $target = $targetDir . DIRECTORY_SEPARATOR . pathinfo($basename, PATHINFO_FILENAME) . '-' . uniqid() . '.' . pathinfo($basename, PATHINFO_EXTENSION);
        }

        return @rename($fromFile, $target);
    }

    /**
     * @throws \Exception
     */
    public static function getFilesFromPath(string $path, array $allowedExtensions = []): array
    {
        if (!PathUtility::isAbsolutePath($path)) {
            $path = PathUtility::getAbsolutePath($path);
        }
        if (!PathUtility::isAbsolutePath($path)) {
            throw new \Exception('Path ' . $path . ' does not exist.');
        }

        $files = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if (!$file->isFile() || !in_array(strtolower($file->getExtension()), $allowedExtensions)) {
                continue;
            }
            $files[] = $path . '/' . $file->getFilename();
        }

        return $files;
    }
}
