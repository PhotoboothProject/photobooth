<?php

function applyFrame($sourceResource, $framePath) {
    $frame = imagecreatefrompng($framePath);
    $frame = resizePngImage($frame, imagesx($sourceResource), imagesy($sourceResource));
    imagecopy($sourceResource, $frame, 0, 0, 0, 0, imagesx($frame), imagesy($frame));
    return $sourceResource;
}
