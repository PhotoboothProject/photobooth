<?php

namespace Photobooth\Utility;

class VideoUtility
{
    public const supportedFileExtensionsProcessing = [
        'mp4',
        '3gp',
        'mov',
        'avi',
        'wmv',
    ];

    public const supportedMimeTypesSelect = [
        'video/mp4',
        'video/3gpp',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv'
    ];

    public const supportedFileExtensionsSelect = [
        'mp4',
        '3gp',
        'mov',
        'avi',
        'wmv',
    ];

    public static function getVideoPreview(
        string $videoPath = '',
        array $attributes = [],
    ): string {
        $absoluteVideoPath = PathUtility::getAbsolutePath($videoPath);

        if (is_readable($absoluteVideoPath)) {
            $attributes['src'] = $videoPath;
        }

        return '<video autoplay muted loop playsinline ' . ComponentUtility::renderAttributes($attributes) . '></video>';
    }

    public static function getVideosFromPath(string $path, bool $processing = true): array
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
            $files[] = $path . '/' . $file->getFilename();
        }

        return $files;
    }
}
