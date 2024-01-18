<?php

use Photobooth\Factory\ProcessFactory;
use Photobooth\Service\AssetService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\PathUtility;

session_start();

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Fix Permissions
@chmod(PathUtility::getAbsolutePath('config'), 0755);
@chmod(PathUtility::getAbsolutePath('data'), 0755);
@chmod(PathUtility::getAbsolutePath('private'), 0755);

// Config
require_once dirname(__DIR__) . '/lib/config.php';

// Shared instances
//
// We are assigning shared instances to $GLOBALS
// to avoid needing to construct them multiple times
// through the runtime and provide an easy way
// to use them as Singleton.
//
// Instances assigned to $GLOBALS should implement
// a getInstance method to recieve the shared state
// again.
//
// public static function getInstance(): self
// {
//     if (!isset($GLOBALS[self::class])) {
//         throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
//     }
//
//     return $GLOBALS[self::class];
// }
//
// Example:
// $languageService = LanguageService::getInstance();
// $languageService->translate('abort');
//
$GLOBALS[AssetService::class] = new AssetService();
$GLOBALS[LanguageService::class] = new LanguageService(
    $config['ui']['language'] ?? 'en',
    isset($config['ui']['folders_lang']) && $config['ui']['folders_lang'] !== '' ? $config['ui']['folders_lang'] : 'resources/lang'
);
$GLOBALS[LoggerService::class] = new LoggerService(
    $config['dev']['loglevel'] ?? 0
);
$GLOBALS[PrintManagerService::class] = new PrintManagerService(
    $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'printed.csv',
    $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'print.count',
    $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'print.lock',
);
$GLOBALS[DatabaseManagerService::class] = new DatabaseManagerService(
    $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['database']['file'] . '.txt',
    $config['foldersAbs']['images'],
);
$GLOBALS[MailService::class] = new MailService(
    $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt'
);
$GLOBALS[ProcessService::class] = new ProcessService([
    ProcessFactory::fromConfig([
        'name' => 'remotebuzzer',
        'command' => $config['nodebin']['cmd'] . ' ' . PathUtility::getAbsolutePath('resources/js/remotebuzzer-server.js'),
        'enabled' => ($config['remotebuzzer']['startserver'] && ($config['remotebuzzer']['usebuttons'] || $config['remotebuzzer']['userotary'] || $config['remotebuzzer']['usenogpio'])),
        'killSignal' => 9,
    ]),
    ProcessFactory::fromConfig([
        'name' => 'synctodrive',
        'command' => $config['nodebin']['cmd'] . ' ' . PathUtility::getAbsolutePath('resources/js/sync-to-drive.js'),
        'enabled' => ($config['synctodrive']['enabled']),
        'killSignal' => 15,
    ]),
]);
