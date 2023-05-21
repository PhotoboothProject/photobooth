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
     * @param string $fix_path The path to be fixed.
     *
     * @return string The fixed path.
     */
    public static function fixSeperator($fix_path) {
        return str_replace('\\', '/', $fix_path);
    }

    /**
     * Set an absolute path by adding a leading slash if necessary.
     *
     * @param string $path The path to be set as absolute.
     *
     * @return string The absolute path.
     */
    public static function setAbsolutePath($path) {
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        return $path;
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
}
