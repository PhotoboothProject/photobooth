<?php

namespace Photobooth;

class HZip
{
    /**
     * Add files and sub-directories in a folder to zip file.
     */
    private static function folderToZip(string $folder, \ZipArchive &$zipFile, int $exclusiveLength): void
    {
        $handle = opendir($folder);
        if ($handle === false) {
            throw new \Exception('Failed to open directory: ' . $folder);
        }

        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath) && $filePath != 'zip') {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     */
    public static function zipDir(string $sourcePath, string $outZipPath): void
    {
        // check if zip exist already and delete if it exist
        if (is_file($outZipPath)) {
            unlink($outZipPath);
        }

        $pathInfo = pathinfo($sourcePath);
        $parentPath = $pathInfo['dirname'] ?? '';
        $dirName = $pathInfo['basename'];

        $z = new \ZipArchive();
        $z->open($outZipPath, \ZipArchive::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }
}
