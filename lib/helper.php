<?php
require_once __DIR__ . '/log.php';

/**
 * A collection of helper functions used throughout the photobooth application.
 */
class Helper {
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
}

function testFile($file) {
    if (is_dir($file)) {
        $ErrorData = [
            'error' => $file . ' is a path! Frames need to be PNG, Fonts need to be ttf!',
        ];
        logError($ErrorData);
        return false;
    }

    if (!file_exists($file)) {
        $ErrorData = [
            'error' => $file . ' does not exist!',
        ];
        logError($ErrorData);
        return false;
    }
    return true;
}
