<?php

function applyText($sourceResource, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolor, $fontpath, $line1text, $line2text, $line3text, $linespacing) {
    $font = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $fontpath);
    list($r, $g, $b) = sscanf($fontcolor, '#%02x%02x%02x');
    $color = imagecolorallocate($sourceResource, $r, $g, $b);

    if (!empty($line1text)) {
        imagettftext($sourceResource, $fontsize, $fontrot, $fontlocx, $fontlocy, $color, $font, $line1text);
    }
    if (!empty($line2text)) {
        if ($fontrot < 45 && $fontrot > -45) {
            imagettftext($sourceResource, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $color, $font, $line2text);
        } else {
            imagettftext($sourceResource, $fontsize, $fontrot, $fontlocx + $linespacing, $fontlocy, $color, $font, $line2text);
        }
    }
    if (!empty($line3text)) {
        if ($fontrot < 45 && $fontrot > -45) {
            imagettftext($sourceResource, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing * 2, $color, $font, $line3text);
        } else {
            imagettftext($sourceResource, $fontsize, $fontrot, $fontlocx + $linespacing * 2, $fontlocy, $color, $font, $line3text);
        }
    }
    return $sourceResource;
}
