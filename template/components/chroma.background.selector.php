<?php

use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

echo '<div class="chroma-background-selector">';
$backgroundsPath = $config['keying']['private_backgrounds'] ? 'private/images/keyingBackgrounds' : 'resources/img/background';
$backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($backgroundsPath));
foreach ($backgroundImages as $backgroundImage) {
    echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="chroma-background-selector-image rotaryfocus" onclick="setChromaImage(this.src)">';
}
echo '</div>';
