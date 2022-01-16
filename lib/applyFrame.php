<?php

function applyFrame($sourceResource, $framePath) {
    $frame = imagecreatefrompng($framePath);
    $frame = resizePngImage($frame, imagesx($sourceResource), imagesy($sourceResource));
    $x = imagesx($sourceResource) / 2 - imagesx($frame) / 2;
    $y = imagesy($sourceResource) / 2 - imagesy($frame) / 2;
    imagecopy($sourceResource, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
    return $sourceResource;
}
