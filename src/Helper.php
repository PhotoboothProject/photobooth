<?php

namespace Photobooth;

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
}
