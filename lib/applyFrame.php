<?php

function applyFrame($sourceResource, $framePath) {
    try {
        $img = $sourceResource;
        $pic_width = imagesx($img);
        $pic_height = imagesy($img);

        $frame = imagecreatefrompng($framePath);
        $frame = resizePngImage($frame, $pic_width, $pic_height);
        if (!$frame) {
            throw new Exception('Cannot resize Frame.');
        }
        $frame_width = imagesx($frame);
        $frame_height = imagesy($frame);

        $dst_x = 0;
        $dst_y = 0;

        if ($pic_height == $frame_height) {
            $dst_x = intval(($pic_width - $frame_width) / 2);
        } else {
            $dst_y = intval(($pic_height - $frame_height) / 2);
        }

        if (!imagecopy($img, $frame, $dst_x, $dst_y, 0, 0, $frame_width, $frame_height)) {
            throw new Exception('Error applying frame to image.');
        }
    } catch (Exception $e) {
        // Clear cache
        imagedestroy($img);
        // Return unmodified resource
        return $sourceResource;
    }

    // Return resource with text applied
    return $img;
}
