<?php

namespace Photobooth;

use Photobooth\Enum\FolderEnum;

class Environment implements \JsonSerializable
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

    public static function getIp(): string
    {
        return self::isLinux() ? shell_exec('hostname -I | cut -d " " -f 1') : $_SERVER['HTTP_HOST'];
    }

    public static function getPublicFolders(): array
    {
        $data = [];
        foreach (FolderEnum::cases() as $folder) {
            $data[$folder->identifier()] = $folder->public();
        }

        return $data;
    }

    public static function getAbsoluteFolders(): array
    {
        $data = [];
        foreach (FolderEnum::cases() as $folder) {
            $data[$folder->identifier()] = $folder->absolute();
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return [
            'operatingSystem' => self::getOperatingSystem(),
            'ip' => self::getIp(),
            'publicFolders' => self::getPublicFolders(),
            'absoluteFolders' => self::getAbsoluteFolders(),
        ];
    }
}
