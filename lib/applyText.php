<?php

function applyText($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $fontColor, $fontPath, $line1Text, $line2Text, $line3Text, $lineSpacing) {
    list($r, $g, $b) = sscanf($fontColor, '#%02x%02x%02x');
    $color = imagecolorallocate($sourceResource, $r, $g, $b);

    if (!empty($line1Text)) {
        imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $color, $fontPath, $line1Text);
    }
    if (!empty($line2Text)) {
        $line2Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $lineSpacing : $fontLocationY;
        $line2X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $lineSpacing;
        imagettftext($sourceResource, $fontSize, $fontRotation, $line2X, $line2Y, $color, $fontPath, $line2Text);
    }
    if (!empty($line3Text)) {
        $line3Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $lineSpacing * 2 : $fontLocationY;
        $line3X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $lineSpacing * 2;
        imagettftext($sourceResource, $fontSize, $fontRotation, $line3X, $line3Y, $color, $fontPath, $line3Text);
    }
    return $sourceResource;
}
