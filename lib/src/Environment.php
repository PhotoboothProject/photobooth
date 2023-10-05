<?php

namespace Photobooth;

class Environment
{
    public static function isLinux(): bool
    {
        return self::getOperatingSystem() === 'linux';
    }

    public static function isWindows(): bool
    {
        return self::getOperatingSystem() === 'windows';
    }

    public static function getOperatingSystem(): string
    {
        return (stripos(PHP_OS, 'darwin') === false
            && stripos(PHP_OS, 'cygwin') === false
            && stripos(PHP_OS, 'win') !== false)
            ? 'windows'
            : 'linux';
    }
}
