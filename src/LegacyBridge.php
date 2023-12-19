<?php

namespace Photobooth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyBridge
{
    public static function getLegacyScript(Request $request): string
    {
        $requestPathInfo = $request->getPathInfo();
        $legacyRoot = __DIR__ . '/../';

        if ($requestPathInfo == '/') {
            return "{$legacyRoot}index-legacy.php";
        }

        throw new \Exception("Unhandled legacy mapping for $requestPathInfo");
    }

    public static function handleRequest(Request $request, Response $response, string $publicDirectory): void
    {
        global $config;
        $legacyScriptFilename = self::getLegacyScript($request);

        $p = $request->getPathInfo();
        $_SERVER['PHP_SELF'] = $p;
        $_SERVER['SCRIPT_NAME'] = $p;
        $_SERVER['SCRIPT_FILENAME'] = $legacyScriptFilename;

        require $legacyScriptFilename;
    }
}
