<?php
require_once __DIR__ . '/config.php';

function applyFrame($sourceResource, $framePath, $skipExtend = true) {
    global $config;
    if ($config['picture']['extend_by_frame'] && !$skipExtend) {
        $frame_left_percentage = $config['picture']['frame_left_percentage'];
        $frame_right_percentage = $config['picture']['frame_right_percentage'];
        $frame_top_percentage = $config['picture']['frame_top_percentage'];
        $frame_bottom_percentage = $config['picture']['frame_bottom_percentage'];

        $new_width = imagesx($sourceResource) / (1 - 0.01 * ($frame_left_percentage + $frame_right_percentage));
        $new_height = imagesy($sourceResource) / (1 - 0.01 * ($frame_top_percentage + $frame_bottom_percentage));

        $img = imagecreatetruecolor($new_width, $new_height);
        $white = imagecolorallocate($img, 255, 255, 255);

        // We fill in the new white image
        imagefill($img, 0, 0, $white);

        $image_pos_x = imagesx($img) * 0.01 * $frame_left_percentage;
        $image_pos_y = imagesy($img) * 0.01 * $frame_top_percentage;

        // We copy the image to which we want to apply the frame in our new image.
        imagecopy($img, $sourceResource, $image_pos_x, $image_pos_y, 0, 0, imagesx($sourceResource), imagesy($sourceResource));

        // Clear cache
        imagedestroy($sourceResource);
    } else {
        $img = $sourceResource;
    }

    $pic_width = imagesx($img);
    $pic_height = imagesy($img);

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

    imagecopy($img, $frame, $dst_x, $dst_y, 0, 0, $frame_width, $frame_height);
    return $img;
}
