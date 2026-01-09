<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\LanguageService;
use Photobooth\Service\ImageMetadataCacheService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$metadataCache = ImageMetadataCacheService::getInstance();

if (empty($imagelist)) {
    echo '<h1>' . $languageService->translate('gallery_no_image') . '</h1>';
} else {
    echo '<div class="gallery-list" id="galimages">';
    foreach ($imagelist as $image) {
        try {
            $date = 'Gallery';
            if ($config['picture']['naming'] === 'dateformatted' && $config['gallery']['show_date']) {
                if ($config['database']['file'] != 'db') {
                    $db = strlen($config['database']['file']);
                    $name = substr($image, ++$db);
                } else {
                    $name = $image;
                }
                $dateObject = DateTime::createFromFormat('Ymd_His', substr($name, 0, strlen($name) - 4));
                if ($dateObject) {
                    $date = '<i class="' . $config['icons']['date'] . '"></i> ' . $dateObject->format($config['gallery']['date_format']);
                }
            }

            $filename_photo = PathUtility::getAbsolutePath(FolderEnum::IMAGES->value . DIRECTORY_SEPARATOR . $image);
            $filename_thumb = PathUtility::getAbsolutePath(FolderEnum::THUMBS->value . DIRECTORY_SEPARATOR . $image);
            $useThumbs = $config['gallery']['use_thumb'];

            $imageinfo = $metadataCache->get($filename_photo);
            if ($imageinfo === null) {
                $rawInfo = @getimagesize($filename_photo);
                if (is_array($rawInfo)) {
                    $imageinfo = [
                        'width' => (int) $rawInfo[0],
                        'height' => (int) $rawInfo[1],
                    ];
                    $metadataCache->set($filename_photo, $imageinfo['width'], $imageinfo['height']);
                }
            }

            $imageinfoThumb = null;
            if ($useThumbs) {
                $imageinfoThumb = $metadataCache->get($filename_thumb);
                if ($imageinfoThumb === null) {
                    $rawThumbInfo = @getimagesize($filename_thumb);
                    if (is_array($rawThumbInfo)) {
                        $imageinfoThumb = [
                            'width' => (int) $rawThumbInfo[0],
                            'height' => (int) $rawThumbInfo[1],
                        ];
                        $metadataCache->set($filename_thumb, $imageinfoThumb['width'], $imageinfoThumb['height']);
                    }
                }
            }

            if (is_array($imageinfo)) {
                $thumbPath = $filename_thumb;
                if (!$useThumbs || !is_array($imageinfoThumb)) {
                    $thumbPath = $filename_photo;
                    $imageinfoThumb = $imageinfo;
                }
                // Calculate PSWP dimensions to max 800x600 while keeping aspect ratio
                $maxPswpWidth  = $config['gallery']['picture_width'];
                $maxPswpHeight = $config['gallery']['picture_height'];
                $aspectRatio   = $imageinfo['width'] / $imageinfo['height'];
                if ($imageinfo['width'] > $maxPswpWidth || $imageinfo['height'] > $maxPswpHeight) {
                    if ($aspectRatio >= 1) {
                        // Landscape or square
                        $pswpWidth  = $maxPswpWidth;
                        $pswpHeight = (int)($maxPswpWidth / $aspectRatio);
                    } else {
                        // Portrait
                        $pswpHeight = $maxPswpHeight;
                        $pswpWidth  = (int)($maxPswpHeight * $aspectRatio);
                    }
                } else {
                    $pswpWidth  = $imageinfo['width'];
                    $pswpHeight = $imageinfo['height'];
                }
                echo '<a href="' . PathUtility::getPublicPath($filename_photo) . '" class="gallery-list-item rotaryfocus" data-size="' . $imageinfo['width'] . 'x' . $imageinfo['height'] . '"';
                echo ' data-pswp-width="' . $pswpWidth . '" data-pswp-height="' . $pswpHeight . '"';
                echo ' data-med="' . PathUtility::getPublicPath($thumbPath) . '" data-med-size="' . $imageinfoThumb['width'] . 'x' . $imageinfoThumb['height'] . '">';
                echo '<figure>';
                echo '<img src="' . PathUtility::getPublicPath($thumbPath) . '" alt="' . $image . '" loading="lazy"';
                if ($imageinfo['height'] > $imageinfo['width']) {
                    echo 'style="padding-left: 25%;padding-right: 25%;"';
                }
                echo ' />';
                if ($config['gallery']['figcaption']) {
                    echo '<figcaption>' . $date . '</figcaption>';
                }
                echo '</figure>';
                echo '</a>';
            }
        } catch (\Exception $e) {
            // Empty catch block
            // ignore errors for niw
        }
    }
}
echo '</div>';
