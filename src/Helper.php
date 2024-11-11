<?php

namespace Photobooth;

use FTP\Connection;

/**
 * A collection of helper functions used throughout the photobooth application.
 */
class Helper
{
    /**
     * @var string[] Array of unit labels.
     */
    private static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    /**
     * Clears the cache for a specific file.
     */
    public static function clearCache(string $file): void
    {
        if (function_exists('opcache_invalidate') && ini_get('opcache.restrict_api') !== false && strlen(ini_get('opcache.restrict_api')) < 1) {
            opcache_invalidate($file, true);
        } elseif (function_exists('apc_compile_file')) {
            apc_compile_file($file);
        }
    }

    /**
     * Calculates the total size of a folder and its subfolders recursively.
     */
    public static function getFolderSize(string $path): int
    {
        if (!is_dir($path)) {
            throw new \Exception('Invalid directory path: ' . $path);
        }

        $totalSize = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';

        if ($files === false) {
            return $totalSize;
        }

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $currentFile = $cleanPath . $file;
                if (is_dir($currentFile)) {
                    $size = self::getFolderSize($currentFile);
                    $totalSize += $size;
                } else {
                    $size = filesize($currentFile);
                    if ($size === false) {
                        throw new \Exception('Failed to get size of file: ' . $currentFile);
                    }
                    $totalSize += $size;
                }
            }
        }

        return $totalSize;
    }

    /**
     * Formats the given size in bytes to a human-readable format.
     */
    public static function formatSize(int $size): string
    {
        $mod = 1024;
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        $endIndex = strpos((string) $size, '.') + 3;

        return substr((string) $size, 0, $endIndex) . ' ' . self::$units[$i];
    }

    /**
     * Counts the number of files in the given directory.
     */
    public static function getFileCount(string $path): int
    {
        if (!is_dir($path)) {
            throw new \Exception('Invalid directory path: ' . $path);
        }

        $fileCount = 0;
        $fi = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);

        if (!$fi->valid()) {
            throw new \Exception('Empty directory: ' . $path);
        }

        foreach ($fi as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $fileCount++;
            }
        }

        return $fileCount;
    }

    /**
     * Evaluates a mathematical expression represented as a string.
     *
     * @param string $expression The mathematical expression to evaluate.
     *                           Only numbers, math symbols (+, -, *, /, (), .) are allowed.
     * @return int The result of the evaluated expression as an integer.
     */
    public static function doMath(string $expression): int
    {
        $o = 0;
        eval('$o = ' . preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $expression) . ';');
        return intval($o);
    }

    /**
     * Navigate through the ftp folder system.
     */
    public static function cdFTPTree(Connection $conn, string $currentDir): void
    {
        if ($currentDir == '') {
            throw new \Exception('The path cannot be empty!');
        }

        if (ftp_chdir($conn, $currentDir)) {
            // the directory already exist and we are already in it
            return;
        }

        $exploded = explode(DIRECTORY_SEPARATOR, $currentDir);
        array_pop($exploded);

        $rejoined = implode(DIRECTORY_SEPARATOR, $exploded);
        self::cdFTPTree($conn, $rejoined);

        ftp_mkdir($conn, $currentDir);
        ftp_chdir($conn, $currentDir);
    }

    /**
     * Convert a text into a slug.
     */
    public static function slugify(string $text, string $divider = '-'): string
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', (string)$text);
        if ($text === false) {
            return 'n-a';
        }

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', (string)$text);

        // trim
        $text = trim((string)$text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, (string)$text);

        // lowercase
        $text = strtolower((string)$text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Check if the file exist, and it isn't a location.
     */
    public static function testFile(string $file_location): bool
    {
        if (is_dir($file_location)) {
            //throw new \Exception($file_location . ' is a path! Frames need to be PNG, Fonts need to be ttf!');
            return false;
        }

        if (!file_exists($file_location)) {
            //throw new \Exception($file_location . ' does not exist!');
            return false;
        }
        return true;
    }
}
