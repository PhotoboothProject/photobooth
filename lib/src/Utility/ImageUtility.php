<?php

namespace Photobooth\Utility;

use GdImage;
use Photobooth\Enum\ImageFilterEnum;

class ImageUtility
{
    public const supportedFileExtensionsProcessing = [
        'gif',
        'png',
        'jpeg',
        'jpg',
    ];

    public const supportedMimeTypesSelect = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/svg+xml',
        'image/bmp',
        'image/webp',
    ];

    public const supportedFileExtensionsSelect = [
        'gif',
        'png',
        'jpeg',
        'jpg',
        'svg',
        'bmp',
        'webp',
    ];

    public const resourcePaths = [
        'resources/img/background',
        'resources/img/frames',
        'resources/img/demo'
    ];

    public static function getImagesFromPath(string $path, bool $processing = true): array
    {
        if (!PathUtility::isAbsolutePath($path)) {
            $path = PathUtility::getAbsolutePath($path);
        }
        if (!PathUtility::isAbsolutePath($path)) {
            throw new \Exception('Path ' . $path . ' does not exist.');
        }

        $files = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if(!$file->isFile() || !in_array(strtolower($file->getExtension()), $processing ? self::supportedFileExtensionsProcessing : self::supportedFileExtensionsSelect)) {
                continue;
            }
            $files[] = $path . '/' . $file->getFilename();
        }

        return $files;
    }

    public static function getRandomImageFromPath(string $path): string
    {
        if ($path === '' || $path === 'demoframes') {
            $path = 'resources/img/frames';
        }

        if (!in_array($path, self::resourcePaths)) {
            $path = 'private/' . $path;
        }

        $files = self::getImagesFromPath($path);
        if (count($files) === 0) {
            throw new \Exception('Path ' . $path . ' does not contain images.');
        }

        return $files[array_rand($files)];
    }

    public static function applyFilter(?ImageFilterEnum $filter, GdImage $image): void
    {
        switch ($filter) {
            case ImageFilterEnum::ANTIQUE:
                imagefilter($image, IMG_FILTER_BRIGHTNESS, 0);
                imagefilter($image, IMG_FILTER_CONTRAST, -30);
                imagefilter($image, IMG_FILTER_COLORIZE, 75, 50, 25);
                break;
            case ImageFilterEnum::AQUA:
                imagefilter($image, IMG_FILTER_COLORIZE, 0, 70, 0, 30);
                break;
            case ImageFilterEnum::BLUE:
                imagefilter($image, IMG_FILTER_COLORIZE, 0, 0, 100);
                break;
            case ImageFilterEnum::BLUR:
                $blur = 5;
                for ($i = 0; $i < $blur; $i++) {
                    // each 5th time apply '_FILTER_SMOOTH' with 'level of smoothness' set to -7
                    if ($i % 5 == 0) {
                        imagefilter($image, IMG_FILTER_SMOOTH, -7);
                    }
                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                }
                break;
            case ImageFilterEnum::COLOR:
                imagefilter($image, IMG_FILTER_CONTRAST, -40);
                break;
            case ImageFilterEnum::COOL:
                imagefilter($image, IMG_FILTER_MEAN_REMOVAL);
                imagefilter($image, IMG_FILTER_CONTRAST, -50);
                break;
            case ImageFilterEnum::EDGE:
                $emboss = [[1, 1, 1], [1, -7, 1], [1, 1, 1]];
                imageconvolution($image, $emboss, 1, 0);
                break;
            case ImageFilterEnum::EMBOSS:
                $emboss = [[-2, -1, 0], [-1, 1, 1], [0, 1, 2]];
                imageconvolution($image, $emboss, 1, 0);
                break;
            case ImageFilterEnum::EVERGLOW:
                imagefilter($image, IMG_FILTER_BRIGHTNESS, -30);
                imagefilter($image, IMG_FILTER_CONTRAST, -5);
                imagefilter($image, IMG_FILTER_COLORIZE, 30, 30, 0);
                break;
            case ImageFilterEnum::GRAYSCALE:
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                break;
            case ImageFilterEnum::GREEN:
                imagefilter($image, IMG_FILTER_COLORIZE, 0, 100, 0);
                break;
            case ImageFilterEnum::MEAN:
                imagefilter($image, IMG_FILTER_MEAN_REMOVAL);
                break;
            case ImageFilterEnum::NEGATE:
                imagefilter($image, IMG_FILTER_NEGATE);
                break;
            case ImageFilterEnum::PINK:
                imagefilter($image, IMG_FILTER_COLORIZE, 50, -50, 50);
                break;
            case ImageFilterEnum::PIXELATE:
                imagefilter($image, IMG_FILTER_PIXELATE, 20);
                break;
            case ImageFilterEnum::RED:
                imagefilter($image, IMG_FILTER_COLORIZE, 100, 0, 0);
                break;
            case ImageFilterEnum::RETRO:
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_COLORIZE, 100, 25, 25, 50);
                break;
            case ImageFilterEnum::SELECTIVE_BLUR:
                $blur = 5;
                for ($i = 0; $i <= $blur; $i++) {
                    imagefilter($image, IMG_FILTER_SELECTIVE_BLUR);
                }
                break;
            case ImageFilterEnum::SEPIA_DARK:
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_BRIGHTNESS, -30);
                imagefilter($image, IMG_FILTER_COLORIZE, 90, 55, 30);
                break;
            case ImageFilterEnum::SEPIA_LIGHT:
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_COLORIZE, 90, 60, 40);
                break;
            case ImageFilterEnum::SMOOTH:
                imagefilter($image, IMG_FILTER_SMOOTH, 2);
                break;
            case ImageFilterEnum::SUMMER:
                imagefilter($image, IMG_FILTER_COLORIZE, 0, 150, 0, 50);
                imagefilter($image, IMG_FILTER_NEGATE);
                imagefilter($image, IMG_FILTER_COLORIZE, 25, 50, 0, 50);
                imagefilter($image, IMG_FILTER_NEGATE);
                break;
            case ImageFilterEnum::VINTAGE:
                imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_COLORIZE, 40, 10, -15);
                break;
            case ImageFilterEnum::WASHED:
                imagefilter($image, IMG_FILTER_BRIGHTNESS, 30);
                imagefilter($image, IMG_FILTER_NEGATE);
                imagefilter($image, IMG_FILTER_COLORIZE, -50, 0, 20, 50);
                imagefilter($image, IMG_FILTER_NEGATE);
                imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
                break;
            case ImageFilterEnum::YELLOW:
                imagefilter($image, IMG_FILTER_COLORIZE, 100, 100, -100);
                break;
            default:
                break;
        }
    }
}
