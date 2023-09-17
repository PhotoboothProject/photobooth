<?php

namespace Photobooth;

use GdImage;

class ImageFilter
{
    public static function applyFilter(string $imgfilter, GdImage $sourceResource)
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
                $emboss = [[1, 1, 1], [1, -7, 1], [1, 1, 1]];
                imageconvolution($sourceResource, $emboss, 1, 0);
                break;
            case 'emboss':
                $emboss = [[-2, -1, 0], [-1, 1, 1], [0, 1, 2]];
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
}
