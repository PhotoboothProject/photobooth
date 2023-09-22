<?php

namespace Photobooth\Utility;

class PathUtility
{
    public static function getRootPath(): string
    {
        return realpath(self::fixFilePath(__DIR__ . '/../../../'));
    }

    public static function getAbsolutePath(string $path = ''): string
    {
        if (self::isAbsolutePath($path)) {
            return $path;
        }

        $path = self::fixFilePath('/' . $path);
        $path = self::fixFilePath(str_replace(self::getRootPath(), '', $path));
        $path = self::fixFilePath(self::getRootPath() . $path);

        return $path;
    }

    public static function isAbsolutePath(string $path): bool
    {
        return realpath($path) !== false;
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

        $path = self::fixFilePath(self::getBaseUrl() . $path);
        $path = self::fixFilePath(str_replace(self::getRootPath(), '', $path));
        $path = self::fixFilePath(self::resolveRelativePath($path));

        if ($absolute) {
            $path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '//' . $_SERVER['SERVER_NAME'] . $path;
        }

        return $path;
    }

    public static function getBaseUrl(): string
    {
        return self::fixFilePath(str_replace($_SERVER['DOCUMENT_ROOT'], '', self::getAbsolutePath()));
    }

    public static function fixFilePath(string $path): string
    {
        return str_replace(['\\', '//'], '/', $path);
    }

    protected static function resolveRelativePath(string $relativePath): string
    {
        $segments = explode('/', $relativePath);
        $resolvedPath = '';
        foreach ($segments as $segment) {
            if ($segment === '..') {
                $resolvedPath = dirname($resolvedPath);
            } elseif ($segment !== '.') {
                $resolvedPath .= '/' . $segment;
            }
        }

        return $resolvedPath;
    }
}
