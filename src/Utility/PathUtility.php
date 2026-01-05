<?php

namespace Photobooth\Utility;

use DirectoryIterator;
use InvalidArgumentException;
use IteratorIterator;
use Photobooth\Environment;
use SplFileInfo;

/**
 * Utility class for resolving and normalizing filesystem paths and public URLs
 * within the Photobooth project.
 *
 * Always pass a relative path when possible and use this utility to resolve it.
 */
class PathUtility
{
    /**
     * Cached project root path with trailing directory separator.
     *
     * @var string|null
     */
    public static $rootPathCache = null;

    /**
     * Returns the absolute filesystem path to the project root directory.
     *
     * The result is cached for subsequent calls and always includes a trailing
     * directory separator.
     *
     * @throws InvalidArgumentException If the root path cannot be resolved.
     */
    public static function getRootPath(): string
    {
        if (self::$rootPathCache === null) {
            self::$rootPathCache = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR;
        }
        if (!self::$rootPathCache) {
            throw new InvalidArgumentException('Rootpath could not be resolved.');
        }

        return self::$rootPathCache;
    }

    /**
     * Resolves a project-relative path to an absolute filesystem path.
     *
     * If an absolute path inside the project root is passed, it is returned as is.
     * If resolution via `realpath` fails, a best-effort concatenation with the
     * document root is returned.
     *
     * @param  string  $path  Relative or absolute path inside the project root.
     *
     * @return string Absolute filesystem path.
     */
    public static function getAbsolutePath(string $path = ''): string
    {
        if ($path === '') {
            return '';
        }

        $documentRoot = self::getRootPath();
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (self::isAbsolutePath($path) && str_starts_with($path, self::getRootPath())) {
            return $path;
        }

        $absolutePath = $documentRoot . ltrim($path, DIRECTORY_SEPARATOR);
        $absolutePath = preg_replace('#' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, (string) realpath($absolutePath));
        if ($absolutePath && strpos($absolutePath, $documentRoot) === 0) {
            return $absolutePath;
        }

        return $documentRoot . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Checks whether a path is an absolute filesystem path.
     *
     * On Windows, drive-prefixed paths (e.g. `C:\`) are detected.
     *
     * @param  string  $path  Path to inspect.
     *
     * @return bool `true` if the path is absolute, `false` otherwise.
     */
    public static function isAbsolutePath(string $path): bool
    {
        if (Environment::isWindows() && (substr($path, 1, 2) === ':/' || substr($path, 1, 2) === ':\\')) {
            return true;
        }

        return str_starts_with($path, '/');
    }

    /**
     * Checks whether a given path string looks like a URL.
     *
     * @param  string  $path  Path or URL to check.
     *
     * @return bool `true` if the value starts with `http`, `false` otherwise.
     */
    public static function isUrl(string $path): bool
    {
        return str_starts_with(strtolower($path), 'http');
    }

    /**
     * Builds a public URL (relative or absolute) from a given path.
     *
     * - If a URL is passed, it is returned unchanged.
     * - If an absolute path inside the project root is passed, it is converted
     *   to a path relative to the web root.
     * - Otherwise the path is appended to the Photobooth base URL.
     *
     * @param  string  $path      Relative path, absolute path or URL.
     * @param  bool    $absolute  When `true`, a fully qualified URL including
     *                            scheme and host is returned.
     *
     * @return string Public URL to the requested resource.
     */
    public static function getPublicPath(string $path = '', bool $absolute = false): string
    {
        if (self::isUrl($path)) {
            return $path;
        }

        if (self::isAbsolutePath($path)) {
            $rootPath = self::getRootPath();
            if (str_starts_with($path, $rootPath)) {
                $path = str_replace($rootPath, '', $path);
            }
        }

        $path = self::fixFilePath(self::getBaseUrl() . $path);
        if ($absolute) {
            $path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $path;
        }

        return $path;
    }

    /**
     * Returns the base URL of the Photobooth installation relative to the
     * current web server document root.
     *
     * The returned string always ends with a trailing slash and is normalized
     * to use forward slashes.
     *
     * @return string Base URL of the installation.
     */
    public static function getBaseUrl(): string
    {
        $documentRoot = (string)realpath($_SERVER['DOCUMENT_ROOT']);
        $rootPath = self::getRootPath();

        return self::fixFilePath(str_replace($documentRoot, '', $rootPath) . '/');
    }

    /**
     * Normalizes a filesystem or URL path to use forward slashes and removes
     * duplicate slashes.
     *
     * @param  string  $path  Path to normalize.
     *
     * @return string Normalized path string.
     */
    public static function fixFilePath(string $path): string
    {
        return str_replace(['\\', '//'], '/', $path);
    }

    /**
     * Converts a path to a project-relative, forward-slashed form.
     *
     * Behavior:
     * - URLs are returned unchanged.
     * - Absolute paths inside the project root are stripped to project-relative.
     * - Absolute paths outside the project are returned as-is.
     * - Relative paths are normalized (slashes fixed) and returned.
     */
    public static function toProjectRelative(string $path): string
    {
        if (self::isUrl($path)) {
            return $path;
        }

        $normalized = self::fixFilePath($path);
        $root = self::getRootPath();

        if (self::isAbsolutePath($normalized) && str_starts_with($normalized, $root)) {
            return self::fixFilePath(substr($normalized, strlen($root)));
        }

        return $normalized;
    }

    /**
     * Resolves a file path or URL to a readable absolute filesystem path.
     *
     * Resolution strategy:
     * - URLs are returned unchanged.
     * - Absolute paths are checked directly.
     * - Relative paths are resolved using `getAbsolutePath`.
     * - If unresolved, fall back to a project-relative path.
     * - As a last resort, two variants based on `$_SERVER['DOCUMENT_ROOT']`
     *   are tried.
     *
     * @param  string  $filePath  Relative path, absolute path or URL to a file.
     *
     * @return string Absolute, readable filesystem path or the original URL.
     *
     * @throws \Exception If the file cannot be found or is not readable.
     */
    public static function resolveFilePath(string $filePath): string
    {
        if (self::isUrl($filePath)) {
            return $filePath;
        }

        $absolutePath = self::isAbsolutePath($filePath) ? $filePath : self::getAbsolutePath($filePath);
        if (is_readable($absolutePath)) {
            return $absolutePath;
        }

        //fallback if absolute path is not resolvable, try relative path with root path
        $projectRelative = self::getAbsolutePath(ltrim($filePath, '/'));
        if (is_readable($projectRelative)) {
            return $projectRelative;
        }

        $altPath1 = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        $altPath2 = $_SERVER['DOCUMENT_ROOT'] . '/' . $filePath;

        if (is_readable($altPath1)) {
            return $altPath1;
        } elseif (is_readable($altPath2)) {
            return $altPath2;
        }

        throw new \Exception('File not found: ' . $filePath);
    }

    public static function countFilesInDirectory(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $iterator = new IteratorIterator(new DirectoryIterator($path));
        $count = 0;
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
