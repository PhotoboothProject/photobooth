<?php

namespace Photobooth\Utility;

use Exception;

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
        string $relativeVideoPath = '',
        array $attributes = [],
    ): string {

        if (!empty($relativeVideoPath)) {
            $absolutePath = PathUtility::getRootPath() . $relativeVideoPath;

            //check on fs if video exists
            if (!file_exists($absolutePath)) {
                $attributes['alt'] = 'Video not found: ' . htmlspecialchars($relativeVideoPath);
            } else {
                $videoPathPublic   = PathUtility::getPublicPath($relativeVideoPath);
                $attributes['src'] = $videoPathPublic;
            }
        } else {
            $attributes['alt'] = 'No video specified';
        }

        return '<video autoplay muted loop playsinline ' . ComponentUtility::renderAttributes($attributes) . '></video>';
    }

    /**
     * @throws Exception
     */
    public static function getVideosFromPath(string $path, bool $processing = true): array
    {
        $allowedExtensions = $processing ? self::supportedFileExtensionsProcessing : self::supportedFileExtensionsSelect;

        return FileUtility::getFilesFromPath($path, $allowedExtensions);
    }
}
