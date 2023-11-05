<?php

use Photobooth\Service\AssetService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;

session_start();

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

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
    $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['dev']['logfile'],
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

define('MAIL_FILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt');
