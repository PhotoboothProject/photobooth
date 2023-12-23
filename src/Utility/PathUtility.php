<?php

namespace Photobooth\Utility;

use InvalidArgumentException;
use Photobooth\Environment;

class PathUtility
{
    public static function getRootPath(): string
    {
        $path = realpath(__DIR__ . '/../../');
        if ($path === false) {
            throw new InvalidArgumentException('Rootpath could not be resolved.');
        }

        return $path;
    }

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

        $absolutePath = $documentRoot . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        $absolutePath = preg_replace('#' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, (string) realpath($absolutePath));
        if ($absolutePath && strpos($absolutePath, $documentRoot) === 0) {
            return $absolutePath;
        }

        return $documentRoot . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    public static function isAbsolutePath(string $path): bool
    {
        if (Environment::isWindows() && (substr($path, 1, 2) === ':/' || substr($path, 1, 2) === ':\\')) {
            return true;
        }

        return str_starts_with($path, '/');
    }

    public static function isUrl(string $path): bool
    {
        return str_starts_with($path, 'http');
    }

    public static function getPublicPath(string $path = '', bool $absolute = false): string
    {
        if (self::isUrl($path)) {
            return $path;
        }

        if (self::isAbsolutePath($path)) {
            $path = str_replace(self::getRootPath(), '', $path);
        }

        $path = self::fixFilePath(self::getBaseUrl() . $path);
        if ($absolute) {
            $path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $path;
        }

        return $path;
    }

    public static function getBaseUrl(): string
    {
        $documentRoot = (string) realpath($_SERVER['DOCUMENT_ROOT']);
        $rootPath = self::getRootPath();
        return self::fixFilePath(str_replace($documentRoot, '', $rootPath) . '/');
    }

    public static function fixFilePath(string $path): string
    {
        return str_replace(['\\', '//'], '/', $path);
    }
}
