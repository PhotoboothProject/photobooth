<?php

function applyFrame($sourceResource, $framePath) {
    $pic_width = imagesx($sourceResource);
    $pic_height = imagesy($sourceResource);

    $frame = imagecreatefrompng($framePath);
    $frame = resizePngImage($frame, $pic_width, $pic_height);

    $frame_width = imagesx($frame);
    $frame_height = imagesy($frame);

    $dst_x = 0;
    $dst_y = 0;

    if ($pic_height == $frame_height) {
        $dst_x = intval(($pic_width - $frame_width) / 2);
    } else {
        $dst_y = intval(($pic_height - $frame_height) / 2);
    }

    imagecopy($sourceResource, $frame, $dst_x, $dst_y, 0, 0, $frame_width, $frame_height);
    return $sourceResource;
}
