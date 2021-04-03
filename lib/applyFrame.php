<?php

function ApplyFrame($srcImagePath, $destImagePath, $framePath) {
    $image = imagecreatefromjpeg($srcImagePath);
    $frame = imagecreatefrompng($framePath);
    $frame = resizePngImage($frame, imagesx($image), imagesy($image));
    $x = imagesx($image) / 2 - imagesx($frame) / 2;
    $y = imagesy($image) / 2 - imagesy($frame) / 2;
    imagecopy($image, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
    imagejpeg($image, $destImagePath, 100);
}
