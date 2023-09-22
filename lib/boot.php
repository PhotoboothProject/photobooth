<?php

use Photobooth\Utility\PathUtility;

session_start();

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Config
require_once dirname(__DIR__) . '/lib/config.php';

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

// Filter
define('FILTER_PLAIN', 'plain');
define('FILTER_ANTIQUE', 'antique');
define('FILTER_AQUA', 'aqua');
define('FILTER_BLUE', 'blue');
define('FILTER_BLUR', 'blur');
define('FILTER_COLOR', 'color');
define('FILTER_COOL', 'cool');
define('FILTER_EDGE', 'edge');
define('FILTER_EMBOSS', 'emboss');
define('FILTER_EVERGLOW', 'everglow');
define('FILTER_GRAYSCALE', 'grayscale');
define('FILTER_GREEN', 'green');
define('FILTER_MEAN', 'mean');
define('FILTER_NEGATE', 'negate');
define('FILTER_PINK', 'pink');
define('FILTER_PIXELATE', 'pixelate');
define('FILTER_RED', 'red');
define('FILTER_RETRO', 'retro');
define('FILTER_SELECTIVE_BLUR', 'selective-blur');
define('FILTER_SEPIA_LIGHT', 'sepia-light');
define('FILTER_SEPIA_DARK', 'sepia-dark');
define('FILTER_SMOOTH', 'smooth');
define('FILTER_SUMMER', 'summer');
define('FILTER_VINTAGE', 'vintage');
define('FILTER_WASHED', 'washed');
define('FILTER_YELLOW', 'yellow');
define('AVAILABLE_FILTERS', [
    FILTER_PLAIN => 'None',
    FILTER_ANTIQUE => 'Antique',
    FILTER_AQUA => 'Aqua',
    FILTER_BLUE => 'Blue',
    FILTER_BLUR => 'Blur',
    FILTER_COLOR => 'Color',
    FILTER_COOL => 'Cool',
    FILTER_EDGE => 'Edge',
    FILTER_EMBOSS => 'Emboss',
    FILTER_EVERGLOW => 'Everglow',
    FILTER_GRAYSCALE => 'Grayscale',
    FILTER_GREEN => 'Green',
    FILTER_MEAN => 'Mean',
    FILTER_NEGATE => 'Negate',
    FILTER_PINK => 'Pink',
    FILTER_PIXELATE => 'Pixelate',
    FILTER_RED => 'Red',
    FILTER_RETRO => 'Retro',
    FILTER_SELECTIVE_BLUR => 'Selective blur',
    FILTER_SEPIA_LIGHT => 'Sepia-light',
    FILTER_SEPIA_DARK => 'Sepia-dark',
    FILTER_SMOOTH => 'Smooth',
    FILTER_SUMMER => 'Summer',
    FILTER_VINTAGE => 'Vintage',
    FILTER_WASHED => 'Washed',
    FILTER_YELLOW => 'Yellow',
]);
