<?php

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

function applyFilter($imgfilter, $sourceResource)
{
    switch ($imgfilter) {
        case 'antique':
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, 0);
            imagefilter($sourceResource, IMG_FILTER_CONTRAST, -30);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 75, 50, 25);
            break;
        case 'aqua':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 0, 70, 0, 30);
            break;
        case 'blue':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 0, 0, 100);
            break;
        case 'blur':
            $blur = 5;
            for ($i = 0; $i < $blur; $i++) {
                // each 5th time apply '_FILTER_SMOOTH' with 'level of smoothness' set to -7
                if ($i % 5 == 0) {
                    imagefilter($sourceResource, IMG_FILTER_SMOOTH, -7);
                }
                imagefilter($sourceResource, IMG_FILTER_GAUSSIAN_BLUR);
            }
            break;
        case 'color':
            imagefilter($sourceResource, IMG_FILTER_CONTRAST, -40);
            break;
        case 'cool':
            imagefilter($sourceResource, IMG_FILTER_MEAN_REMOVAL);
            imagefilter($sourceResource, IMG_FILTER_CONTRAST, -50);
            break;
        case 'edge':
            $emboss = array(array(1, 1, 1), array(1, -7, 1), array(1, 1, 1));
            imageconvolution($sourceResource, $emboss, 1, 0);
            break;
        case 'emboss':
            $emboss = array(array(-2, -1, 0), array(-1, 1, 1), array(0, 1, 2));
            imageconvolution($sourceResource, $emboss, 1, 0);
            break;
        case 'everglow':
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, -30);
            imagefilter($sourceResource, IMG_FILTER_CONTRAST, -5);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 30, 30, 0);
            break;
        case 'grayscale':
            imagefilter($sourceResource, IMG_FILTER_GRAYSCALE);
            break;
        case 'green':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 0, 100, 0);
            break;
        case 'mean':
            imagefilter($sourceResource, IMG_FILTER_MEAN_REMOVAL);
            break;
        case 'negate':
            imagefilter($sourceResource, IMG_FILTER_NEGATE);
            break;
        case 'pink':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 50, -50, 50);
            break;
        case 'pixelate':
            imagefilter($sourceResource, IMG_FILTER_PIXELATE, 20);
            break;
        case 'red':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 100, 0, 0);
            break;
        case 'retro':
            imagefilter($sourceResource, IMG_FILTER_GRAYSCALE);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 100, 25, 25, 50);
            break;
        case 'selective-blur':
            $blur = 5;
            for ($i = 0; $i <= $blur; $i++) {
                imagefilter($sourceResource, IMG_FILTER_SELECTIVE_BLUR);
            }
            break;
        case 'sepia-dark':
            imagefilter($sourceResource, IMG_FILTER_GRAYSCALE);
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, -30);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 90, 55, 30);
            break;
        case 'sepia-light':
            imagefilter($sourceResource, IMG_FILTER_GRAYSCALE);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 90, 60, 40);
            break;
        case 'smooth':
            imagefilter($sourceResource, IMG_FILTER_SMOOTH, 2);
            break;
        case 'summer':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 0, 150, 0, 50);
            imagefilter($sourceResource, IMG_FILTER_NEGATE);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 25, 50, 0, 50);
            imagefilter($sourceResource, IMG_FILTER_NEGATE);
            break;
        case 'vintage':
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, 10);
            imagefilter($sourceResource, IMG_FILTER_GRAYSCALE);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 40, 10, -15);
            break;
        case 'washed':
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, 30);
            imagefilter($sourceResource, IMG_FILTER_NEGATE);
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, -50, 0, 20, 50);
            imagefilter($sourceResource, IMG_FILTER_NEGATE);
            imagefilter($sourceResource, IMG_FILTER_BRIGHTNESS, 10);
            break;
        case 'yellow':
            imagefilter($sourceResource, IMG_FILTER_COLORIZE, 100, 100, -100);
            break;
        default:
            break;
    }
}
