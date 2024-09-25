<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();

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

            $imageinfo = @getimagesize($filename_photo);
            $imageinfoThumb = @getimagesize($filename_thumb);

            if (is_array($imageinfo)) {
                if (!is_array($imageinfoThumb)) {
                    $imageinfoThumb = $imageinfo;
                }
                echo '<a href="' . PathUtility::getPublicPath($filename_photo) . '" class="gallery-list-item rotaryfocus" data-size="' . $imageinfo[0] . 'x' . $imageinfo[1] . '"';
                echo ' data-pswp-width="' . $imageinfo[0] . '" data-pswp-height="' . $imageinfo[1] . '"';
                echo ' data-med="' . PathUtility::getPublicPath($filename_thumb) . '" data-med-size="' . $imageinfoThumb[0] . 'x' . $imageinfoThumb[1] . '">';
                echo '<figure>';
                echo '<img src="' . PathUtility::getPublicPath($filename_thumb) . '" alt="' . $image . '" loading="lazy"';
                if ($imageinfo[1] > $imageinfo[0]) {
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
