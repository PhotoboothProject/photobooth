<?php

function createCollage($srcImagePaths, $destImagePath, $takeFrame, $framePath, $Layout, $background_image) {

    if (!is_array($srcImagePaths) || count($srcImagePaths) !== 4) {
        return false;
    }

    switch($Layout) {
        case '2x2':
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
            if ($takeFrame) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $width, $height);
                imagecopy($my_collage, $frame, 0, 0, 0, 0, $width, $height);
            }
            imagejpeg($my_collage, $destImagePath);
            imagedestroy($my_collage);
            break;
        case '2x4':
            $widthNew=321;
            $heightNew=482;
            $PositionsX = [63, 423, 785, 1146]; //X offset in Pixel
            $PositionsY =[57, 642];             //Y offset in Pixel
            $my_collage= imagecreatefrompng($background_image);

            for ($j = 0; $j < 2; $j++) { //delta Y
                $dY =$PositionsY[$j];
                for ($i = 0; $i < 4; $i++) { // delta X
                    $dX =$PositionsX[$i];
                    if (!file_exists($srcImagePaths[$i])) {
                        return false;
                    }
                    $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                    $tempSubRotated = imagerotate($tempSubImage, 90, 0);// Rotate image
                    list($width, $height) = getimagesize($srcImagePaths[0]);
                    imagecopyresized($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew, $height, $width); // copy image to background
                    imagedestroy($tempSubImage);  // Destroy temporary images
                    imagedestroy($tempSubRotated); // Destroy temporary images
                }
            }
            imagejpeg($my_collage, $destImagePath); // Transfer immage to destImagePath with returns the image to core
            imagedestroy($my_collage); // Destroy the created collage in memory
           break;
        default:
            list($width, $height) = getimagesize($srcImagePaths[0]);

            $my_collage = imagecreatetruecolor($width, $height);
            imagejpeg($my_collage, $destImagePath); // Transfer immage to destImagePath with returns the image to core
            imagedestroy($my_collage); // Destroy the created collage in memory
            break;
    }
    return true;
}
