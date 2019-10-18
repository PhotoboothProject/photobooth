<?php

function createCollage($srcImagePaths, $destImagePath) {
    if (!is_array($srcImagePaths) || count($srcImagePaths) !== 4) {
        return false;
    }

    list($width, $height) = getimagesize($srcImagePaths[0]);
    $my_collage_height = $height * 2;
    $my_collage_width = $width * 2;

    $my_collage = imagecreatetruecolor($my_collage_width, $my_collage_height);
    $background = imagecolorallocate($my_collage, 0, 0, 0);
    imagecolortransparent($my_collage, $background);

    $positions = [[0, 0], [$width, 0], [0, $height], [$width, $height]];

    for ($i = 0; $i < 4; $i++) {
        $position = $positions[$i];

        if (!file_exists($srcImagePaths[$i])) {
            return false;
        }

        $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

        imagecopy($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width, $height);
        imagedestroy($tempSubImage);
    }

    imagejpeg($my_collage, $destImagePath);
    imagedestroy($my_collage);

    return true;
}