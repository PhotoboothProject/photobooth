<?php

function createCollage($srcImagePaths, $destImagePath) {
    if (!is_array($srcImagePaths) || count($srcImagePaths) !== 4) {
        return false;
    }

    list($width, $height) = getimagesize($srcImagePaths[0]);

    $my_collage = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($my_collage, 0, 0, 0);
    imagecolortransparent($my_collage, $background);

    $positions = [[0, 0], [$width / 2, 0], [0, $height / 2], [$width / 2, $height / 2]];

    for ($i = 0; $i < 4; $i++) {
        $position = $positions[$i];

        if (!file_exists($srcImagePaths[$i])) {
            return false;
        }

        $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

        imagecopyresized($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width / 2, $height / 2, $width, $height);
        imagedestroy($tempSubImage);
    }

    imagejpeg($my_collage, $destImagePath);
    imagedestroy($my_collage);

    return true;
}