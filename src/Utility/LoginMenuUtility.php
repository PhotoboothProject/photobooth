<?php

namespace Photobooth\Utility;

class LoginMenuUtility
{
    public static function isMenuSessionActive(array $session): bool
    {
        return ($session['auth'] ?? false) === true || !empty($session['rental']);
    }

    public static function shouldShowChromaMenuEntry(array $config, array $session, array $server): bool
    {
        if (!($config['chromaCapture']['enabled'] ?? false)) {
            return false;
        }

        return self::canAccessProtectedPage($config, 'index', $session, $server);
    }

    private static function canAccessProtectedPage(array $config, string $pageKey, array $session, array $server): bool
    {
        if (!($config['protect'][$pageKey] ?? false)) {
            return true;
        }

        $localhostProtectionKey = 'localhost_' . $pageKey;
        $serverAddress = $server['SERVER_ADDR'] ?? null;
        $remoteAddress = $server['REMOTE_ADDR'] ?? null;
        $isSameHost = $serverAddress !== null && $remoteAddress !== null && $remoteAddress === $serverAddress;

        if (!($config['protect'][$localhostProtectionKey] ?? false) && $isSameHost) {
            return true;
        }

        if (!($config['login']['enabled'] ?? false)) {
            return true;
        }

        return ($session['auth'] ?? false) === true;
    }
}
