<?php

function applyFrame($sourceResource, $framePath) {
    $pic_width = imagesx($sourceResource);
    $pic_height = imagesy($sourceResource);

    $frame = imagecreatefrompng($framePath);
    $frame = resizePngImage($frame, $pic_width, $pic_height);

    $frame_width = imagesx($frame);
    $frame_height = imagesy($frame);

    $src_x = 0;
    $src_y = 0;

    if($pic_height == $frame_height) {
        $src_x = ($pic_width - $frame_width) / 2;
    } else {
        $src_y = ($pic_height - $frame_height) / 2;
    }

    imagecopy($sourceResource, $frame, 0, 0, $src_x, $src_y, $frame_width, $frame_height);
    return $sourceResource;
}
