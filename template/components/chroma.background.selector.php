<?php

use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

echo '<div class="chroma-background-selector">';
$backgroundImages = ImageUtility::getImagesFromPath(PathUtility::getAbsolutePath($config['keying']['background_path']));
foreach ($backgroundImages as $backgroundImage) {
    echo '<img src="' . PathUtility::getPublicPath($backgroundImage) . '" class="chroma-background-selector-image rotaryfocus" onclick="setChromaImage(this.src)">';
}
echo '</div>';
