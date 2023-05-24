<?php
require_once __DIR__ . '/log.php';

/**
 * A collection of helper functions used throughout the photobooth application.
 */
class Helper {
    /**
     * @var string[] Array of unit labels.
     */
    private static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    /**
     * Get the relative path of a file or directory.
     *
     * @param string $relative_path The path to the file or directory relative to the application root.
     *
     * @return string The relative path of the file or directory.
     */
    public static function getRootpath($relative_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) {
        return str_replace(Photobooth::getWebRoot(), '', realpath($relative_path));
    }

    /**
     * Fix path separators to use forward slashes instead of backslashes.
     *
     * @param string $fixPath The path to be fixed.
     *
     * @return string The fixed path.
     */
    public static function fixSeperator($fixPath) {
        $fixPath = stripslashes($fixPath);
        return str_replace('\\', '/', $fixPath);
    }

    /**
     * Set an absolute path by adding a leading slash if necessary.
     *
     * @param string $path The path to be set as absolute.
     *
     * @return string The absolute path.
     */
    public static function setAbsolutePath($path) {
        if (!empty($path) && $path[0] != '/') {
            $path = '/' . $path;
        }
        return $path;
    }

    /**
     * Recursively compares two arrays and returns the differences between them.
     *
     * @param array $array1 The first array to compare.
     * @param array $array2 The second array to compare.
     *
     * @return array The array containing the differences between $array1 and $array2.
     */
    public static function arrayRecursiveDiff($array1, $array2) {
        $returnArray = [];

        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursiveDiff = self::arrayRecursiveDiff($value, $array2[$key]);
                    if (count($recursiveDiff)) {
                        $returnArray[$key] = $recursiveDiff;
                    }
                } else {
                    if ($value != $array2[$key]) {
                        $returnArray[$key] = $value;
                    }
                }
            } else {
                $returnArray[$key] = $value;
            }
        }

        return $returnArray;
    }

    /**
     * Clears the cache for a specific file.
     *
     * @param string $file The path to the file for which the cache should be cleared.
     *
     * @return void
     */
    public static function clearCache($file) {
        if (function_exists('opcache_invalidate') && strlen(ini_get('opcache.restrict_api')) < 1) {
            opcache_invalidate($file, true);
        } elseif (function_exists('apc_compile_file')) {
            apc_compile_file($file);
        }
    }

    /**
     * Calculates the total size of a folder and its subfolders recursively.
     *
     * @param string $path The path to the folder.
     *
     * @return int The total size of the folder in bytes.
     *
     * @throws Exception If the provided path is not a valid directory.
     */
    public static function getFolderSize($path) {
        if (!is_dir($path)) {
            throw new Exception('Invalid directory path: ' . $path);
        }

        $totalSize = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';

        if ($files === false) {
            throw new Exception('Failed to read directory: ' . $path);
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
                        throw new Exception('Failed to get size of file: ' . $currentFile);
                    }
                    $totalSize += $size;
                }
            }
        }

        return $totalSize;
    }

    /**
     * Formats the given size in bytes to a human-readable format.
     *
     * @param int $size The size in bytes.
     * @return string The formatted size with unit label.
     */
    public static function formatSize($size) {
        $mod = 1024;

        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        $endIndex = strpos($size, '.') + 3;

        return substr($size, 0, $endIndex) . ' ' . self::$units[$i];
    }

    /**
     * Counts the number of files in the given directory.
     *
     * @param string $path The path to the directory.
     *
     * @return int The number of files in the directory.
     *
     * @throws Exception If the provided path is not a valid directory or an error occurs while reading the directory.
     *
     */
    public static function getFileCount($path) {
        if (!is_dir($path)) {
            throw new Exception('Invalid directory path: ' . $path);
        }

        $fileCount = 0;
        $fi = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        if ($fi === false) {
            throw new Exception('Failed to read directory: ' . $path);
        }

        foreach ($fi as $file) {
            if ($file->isFile()) {
                $fileCount++;
            }
        }

        return $fileCount;
    }

    /**
     * Trims leading and trailing slashes from a path.
     *
     * @param string $path The path to trim slashes from.
     * @return string The trimmed path.
     */
    public static function trimSlashes($path) {
        return trim($path, '/');
    }

    /**
     * Checks if a directory is mounted.
     *
     * @param string $path The path to the directory.
     * @return bool True if the directory is mounted, false otherwise.
     */
    public static function isDirMounted($path) {
        $path = realpath($path);
        $mounts = explode("\n", file_get_contents('/proc/mounts'));

        foreach ($mounts as $mount) {
            list($dev, $mountpoint) = array_pad(explode(' ', $mount), 2, null);
            if ($mountpoint == $path) {
                return true;
            }
        }

        return false;
    }
}
