<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\SlugUtility;

require_once '../lib/boot.php';

$config = $configurationService = ConfigurationService::getInstance()->getConfiguration();
$languageService = LanguageService::getInstance();

$templateLocation = PathUtility::getAbsolutePath($config['ftp']['template_location']);
$templateConfig = [
    'meta' => [
        'sitename' => 'Photobooth',
        'lang' => $config['ui']['language'],
        'title' => $config['ftp']['title'],
        'max-age' => 60,
    ],
    'paths' => [
        'images' => '../' . FolderEnum::IMAGES->value,
        'thumbs' => '../' . FolderEnum::THUMBS->value,
    ],
    'files' => [
        'download_prefix' => SlugUtility::create($config['ftp']['title']),
    ],
    'labels' => [
        'close' => $languageService->translate('close'),
        'share' => $languageService->translate('shareMessage'),
        'download' => $languageService->translate('download'),
        'download_confirmation_images' => $languageService->translate('download_confirmation_images'),
    ],
    'theme' => [
        '--primary-color' => $config['colors']['primary'],
        '--secondary-color' => $config['colors']['secondary'],
        '--button-font-color' => $config['colors']['button_font'],
        '--font-color' => $config['colors']['font'],
    ]
];

unset($config);
require_once $templateLocation;
