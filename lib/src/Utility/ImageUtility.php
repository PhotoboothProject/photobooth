<?php

namespace Photobooth\Utility;

class ImageUtility
{
    // svg is not working as image
    public const supportedFileExtensions = [
        'gif',
        'png',
        'jpeg',
        'jpg',
    ];

    public const resourcePaths = [
        'resources/img/background',
        'resources/img/frames',
        'resources/img/demo'
    ];

    public static function getImagesFromPath(string $path): array
    {
        if (!PathUtility::isAbsolutePath($path)) {
            $path = PathUtility::getAbsolutePath($path);
        }
        if (!PathUtility::isAbsolutePath($path)) {
            throw new \Exception('Path ' . $path . ' does not exist.');
        }

        $files = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if(!$file->isFile() || !in_array(strtolower($file->getExtension()), self::supportedFileExtensions)) {
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
}
