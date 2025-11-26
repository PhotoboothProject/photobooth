<?php

use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

echo '<div class="chroma-background-selector">';
$backgroundsPath = $config['keying']['private_backgrounds'] ? 'private/images/keyingBackgrounds' : 'resources/img/background';
$backgroundImages = [];
try {
    $backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($backgroundsPath));
} catch (\Exception $e) {
    // If no backgrounds are available, render nothing and keep UI usable.
}
foreach ($backgroundImages as $backgroundImage) {
    echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="chroma-background-selector-image rotaryfocus" onclick="setChromaImage(this.src)">';
}
echo '</div>';
