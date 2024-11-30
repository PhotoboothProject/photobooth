<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\AssetService;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\ProcessService;
use Photobooth\Service\SoundService;
use Photobooth\Utility\FileUtility;
use Photobooth\Utility\PathUtility;

session_start();

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

FileUtility::createDirectory(PathUtility::getAbsolutePath('config'));
FileUtility::createDirectory(FolderEnum::DATA->absolute());
FileUtility::createDirectory(FolderEnum::IMAGES->absolute());
FileUtility::createDirectory(FolderEnum::KEYING->absolute());
FileUtility::createDirectory(FolderEnum::PRINT->absolute());
FileUtility::createDirectory(FolderEnum::QR->absolute());
FileUtility::createDirectory(FolderEnum::TEST->absolute());
FileUtility::createDirectory(FolderEnum::THUMBS->absolute());
FileUtility::createDirectory(FolderEnum::TEMP->absolute());
FileUtility::createDirectory(FolderEnum::PRIVATE->absolute());
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/fonts'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/background'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/frames'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/logo'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/placeholder'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/cheese'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/demo'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/videos/background'));
FileUtility::createDirectory(FolderEnum::VAR->absolute());
FileUtility::createDirectory(PathUtility::getAbsolutePath('var/log'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('var/run'));

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
//         $GLOBALS[self::class] = new self();
//     }
//
//     return $GLOBALS[self::class];
// }
//
// Example:
// $languageService = LanguageService::getInstance();
// $languageService->translate('abort');
//
$GLOBALS[ApplicationService::class] = new ApplicationService();
$GLOBALS[ConfigurationService::class] = new ConfigurationService();
$GLOBALS[AssetService::class] = new AssetService();
$GLOBALS[LanguageService::class] = new LanguageService();
$GLOBALS[SoundService::class] = new SoundService();
$GLOBALS[LoggerService::class] = new LoggerService();
$GLOBALS[PrintManagerService::class] = new PrintManagerService();
$GLOBALS[DatabaseManagerService::class] = new DatabaseManagerService();
$GLOBALS[MailService::class] = new MailService();
$GLOBALS[ProcessService::class] = new ProcessService();

$config = ConfigurationService::getInstance()->getConfiguration();
if ($config['dev']['loglevel'] > 0) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

date_default_timezone_set((string)$config['ui']['local_timezone']->value);
