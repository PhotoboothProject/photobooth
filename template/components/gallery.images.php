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

            if (is_array($imageinfo)) {
                if (!is_array($imageinfoThumb)) {
                    $imageinfoThumb = $imageinfo;
                }
                echo '<a href="' . PathUtility::getPublicPath($filename_photo) . '" class="gallery-list-item rotaryfocus" data-size="' . $imageinfo['width'] . 'x' . $imageinfo['height'] . '"';
                echo ' data-pswp-width="' . $imageinfo['width'] . '" data-pswp-height="' . $imageinfo['height'] . '"';
                echo ' data-med="' . PathUtility::getPublicPath($filename_thumb) . '" data-med-size="' . $imageinfoThumb['width'] . 'x' . $imageinfoThumb['height'] . '">';
                echo '<figure>';
                echo '<img src="' . PathUtility::getPublicPath($filename_thumb) . '" alt="' . $image . '" loading="lazy"';
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
