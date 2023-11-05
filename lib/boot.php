<?php

use Photobooth\Service\AssetService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\PathUtility;

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

define('DB_FILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['database']['file'] . '.txt');
define('MAIL_FILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt');
define('IMG_DIR', $config['foldersAbs']['images']);

// Collage Config
define('COLLAGE_LAYOUT', $config['collage']['layout']);
define('COLLAGE_RESOLUTION', (int) substr($config['collage']['resolution'], 0, -3));
define('COLLAGE_BACKGROUND_COLOR', $config['collage']['background_color']);
define('COLLAGE_FRAME', str_starts_with($config['collage']['frame'], 'http') ? $config['collage']['frame'] : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['frame']);
define(
    'COLLAGE_BACKGROUND',
    (empty($config['collage']['background'])
            ? ''
            : str_starts_with($config['collage']['background'], 'http'))
        ? $config['collage']['background']
        : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['background']
);
define('COLLAGE_TAKE_FRAME', $config['collage']['take_frame']);
define('COLLAGE_PLACEHOLDER', $config['collage']['placeholder']);
// If a placeholder is set, decrease the value by 1 in order to reflect array counting at 0
define('COLLAGE_PLACEHOLDER_POSITION', (int) $config['collage']['placeholderposition'] - 1);
define(
    'COLLAGE_PLACEHOLDER_PATH',
    str_starts_with($config['collage']['placeholderpath'], 'http') ? $config['collage']['placeholderpath'] : $_SERVER['DOCUMENT_ROOT'] . $config['collage']['placeholderpath']
);
define('COLLAGE_DASHEDLINE_COLOR', $config['collage']['dashedline_color']);
// If a placholder image should be used, we need to increase the limit here in order to count the images correct
define('COLLAGE_LIMIT', $config['collage']['placeholder'] ? $config['collage']['limit'] + 1 : $config['collage']['limit']);
define('PICTURE_FLIP', $config['picture']['flip']);
define('PICTURE_ROTATION', $config['picture']['rotation']);
define('PICTURE_POLAROID_EFFECT', $config['picture']['polaroid_effect'] === true ? 'enabled' : 'disabled');
define('PICTURE_POLAROID_ROTATION', $config['picture']['polaroid_rotation']);
define('TEXTONCOLLAGE_ENABLED', $config['textoncollage']['enabled'] === true ? 'enabled' : 'disabled');
define('TEXTONCOLLAGE_LINE1', $config['textoncollage']['line1']);
define('TEXTONCOLLAGE_LINE2', $config['textoncollage']['line2']);
define('TEXTONCOLLAGE_LINE3', $config['textoncollage']['line3']);
define('TEXTONCOLLAGE_LOCATIONX', $config['textoncollage']['locationx']);
define('TEXTONCOLLAGE_LOCATIONY', $config['textoncollage']['locationy']);
define('TEXTONCOLLAGE_ROTATION', $config['textoncollage']['rotation']);
define('TEXTONCOLLAGE_FONT', PathUtility::getAbsolutePath($config['textoncollage']['font']));
define('TEXTONCOLLAGE_FONT_COLOR', $config['textoncollage']['font_color']);
define('TEXTONCOLLAGE_FONT_SIZE', $config['textoncollage']['font_size']);
define('TEXTONCOLLAGE_LINESPACE', $config['textoncollage']['linespace']);
