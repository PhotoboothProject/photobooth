<?php

namespace Photobooth;

class Environment
{
    protected string $operatingSystem = '';

    public function __construct()
    {
        $this->operatingSystem = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
    }

    public function isLinux(): bool
    {
        return self::getOperatingSystem() === 'linux';
    }

    public function isWindows(): bool
    {
        return self::getOperatingSystem() === 'windows';
    }

    public function getOperatingSystem(): string
    {
        return $this->operatingSystem;
    }
}
