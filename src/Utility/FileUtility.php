<?php

namespace Photobooth\Utility;

class FileUtility
{
    public const DIRECTORY_PERMISSIONS = 0755;

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
}
