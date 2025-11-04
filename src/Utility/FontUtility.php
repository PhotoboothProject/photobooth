<?php

namespace Photobooth\Utility;

use Photobooth\Enum\FolderEnum;

class FontUtility
{
    public const supportedFileExtensionsProcessing = [
        'ttf',
    ];

    public const supportedFileExtensionsSelect = [
        'ttf',
    ];

    public const supportedMimeTypesSelect = [
        'font/ttf',
        'application/octet-stream',
    ];

    public static function getFontPath(string $font): string
    {
        $fontName = basename($font);
        $privateFontPath = FolderEnum::PRIVATE->absolute() . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $fontName;
        $fontPath = FolderEnum::RESOURCES->absolute() . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $fontName;

        if (is_readable($privateFontPath)) {
            return $privateFontPath;
        } elseif (is_readable($fontPath)) {
            return $fontPath;
        }

        return '';
    }

    public static function getFontPreviewImage(
        string $fontPath,
        int $width = 300,
        int $height = 200,
        int $fontSize = 34,
        array $textLines = [
            'Photobooth!',
            'We love',
            'OpenSource.'
        ],
        array $attributes = [],
    ): string {
        $absoluteFontPath = $fontPath ? self::getFontPath($fontPath) : '';
        $lineHeight = (int) round($fontSize * 1.5);

        $image = imagecreatetruecolor($width, $height);
        $backgroundColor = (int) imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $backgroundColor);

        if ($absoluteFontPath && is_readable($absoluteFontPath)) {
            $textColor = (int) imagecolorallocate($image, 0, 0, 0);
            $x = 10;
            $y = $lineHeight;
            foreach ($textLines as $line) {
                imagefttext($image, $fontSize, 0, $x, $y, $textColor, $absoluteFontPath, $line);
                $y += $lineHeight;
            }
        }

        ob_start();
        imagepng($image);
        $imageData = (string) ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        $attributes['src'] = 'data:image/png;base64,' . base64_encode($imageData);

        return '<img ' . ComponentUtility::renderAttributes($attributes) . '>';
    }

    public static function getFontsFromPath(string $path, bool $processing = true): array
    {
        if (!PathUtility::isAbsolutePath($path)) {
            $path = PathUtility::getAbsolutePath($path);
        }
        if (!PathUtility::isAbsolutePath($path)) {
            throw new \Exception('Path ' . $path . ' does not exist.');
        }

        $files = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if (!$file->isFile() || !in_array(strtolower($file->getExtension()), $processing ? self::supportedFileExtensionsProcessing : self::supportedFileExtensionsSelect)) {
                continue;
            }
            $files[$file->getBasename('.' . $file->getExtension())] = $path . '/' . $file->getFilename();
        }

        return $files;
    }
}
