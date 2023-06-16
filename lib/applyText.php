<?php

function applyText($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $fontColor, $fontPath, $line1Text, $line2Text, $line3Text, $lineSpacing) {
    try {
        // Convert hex color string to RGB values
        list($r, $g, $b) = sscanf($fontColor, '#%02x%02x%02x');

        // Allocate color and set font
        $color = imagecolorallocate($sourceResource, $r, $g, $b);

        // Add first line of text
        if (!empty($line1Text)) {
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $color, $fontPath, $line1Text)) {
                throw new Exception('Could not add first line of text to resource.');
            }
        }

        // Add second line of text
        if (!empty($line2Text)) {
            $line2Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $lineSpacing : $fontLocationY;
            $line2X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $lineSpacing;
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $line2X, $line2Y, $color, $fontPath, $line2Text)) {
                throw new Exception('Could not add second line of text to resource.');
            }
        }

        // Add third line of text
        if (!empty($line3Text)) {
            $line3Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $lineSpacing * 2 : $fontLocationY;
            $line3X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $lineSpacing * 2;
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $line3X, $line3Y, $color, $fontPath, $line3Text)) {
                throw new Exception('Could not add third line of text to resource.');
            }
        }
    } catch (Exception $e) {
        // Return unmodified resource
        return $sourceResource;
    }

    // Return resource with text applied
    return $sourceResource;
}
