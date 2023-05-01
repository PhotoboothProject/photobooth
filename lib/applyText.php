<?php

function applyText($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $fontColor, $fontPath, $line1Text, $line2Text, $line3Text, $lineSpacing) {
    list($r, $g, $b) = sscanf($fontColor, '#%02x%02x%02x');
    $color = imagecolorallocate($sourceResource, $r, $g, $b);

    if (!empty($line1Text)) {
        imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $color, $fontPath, $line1Text);
    }
    if (!empty($line2Text)) {
        if ($fontRotation < 45 && $fontRotation > -45) {
            imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY + $lineSpacing, $color, $fontPath, $line2Text);
        } else {
            imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX + $lineSpacing, $fontLocationY, $color, $fontPath, $line2Text);
        }
    }
    if (!empty($line3Text)) {
        if ($fontRotation < 45 && $fontRotation > -45) {
            imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY + $lineSpacing * 2, $color, $fontPath, $line3Text);
        } else {
            imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX + $lineSpacing * 2, $fontLocationY, $color, $fontPath, $line3Text);
        }
    }
    return $sourceResource;
}
