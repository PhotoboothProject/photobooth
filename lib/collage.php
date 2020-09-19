<?php
require_once(__DIR__ . '/config.php');

define('LAYOUT', $config['collage_layout']);

function createCollage($srcImagePaths, $destImagePath, $takeFrame, $takeFrameAlways, $framePath, $background_image) {

    if (!is_array($srcImagePaths) || count($srcImagePaths) !== 4) {
        return false;
    }

    switch(LAYOUT) {
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

                if ($takeFrame && $takeFrameAlways) {
                    $frame = imagecreatefrompng($framePath);
                    $frame = resizePngImage($frame, imagesx($tempSubImage), imagesy($tempSubImage));
                    $x = (imagesx($tempSubImage)/2) - (imagesx($frame)/2);
                    $y = (imagesy($tempSubImage)/2) - (imagesy($frame)/2);
                    imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                }

                imagecopyresized($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width / 2, $height / 2, $width, $height);
                imagedestroy($tempSubImage);
            }
            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $width, $height);
                $x = (imagesx($my_collage)/2) - (imagesx($frame)/2);
                $y = (imagesy($my_collage)/2) - (imagesy($frame)/2);
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $width, $height);
            }
            imagejpeg($my_collage, $destImagePath);
            imagedestroy($my_collage);
            break;
        case '2x4':
            $degrees = 90;

            list($width_before, $height_before) = getimagesize($srcImagePaths[0]);

            $my_collage_width = $width_before;
            $my_collage_height = $height_before;

            $my_collage = imagecreatetruecolor($my_collage_width, $my_collage_height);
            $background = imagecolorallocate($my_collage, 240, 240, 240);
            imagefill($my_collage, 0, 0, $background);

            $images_rotated = array();

            for ($i = 0; $i < 4; $i++) {
                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if ($takeFrame && $takeFrameAlways) {
                    $frame = imagecreatefrompng($framePath);
                    $frame = resizePngImage($frame, imagesx($tempSubImage), imagesy($tempSubImage));
                    $x = (imagesx($tempSubImage)/2) - (imagesx($frame)/2);
                    $y = (imagesy($tempSubImage)/2) - (imagesy($frame)/2);
                    imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                }

                $tempSubRotated = imagerotate($tempSubImage, $degrees, 0);
                $images_rotated[] = resizeImage($tempSubRotated, $height_before/3.3, $width_before/3.5);
            }

            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $my_collage_width, $my_collage_height);
                $x = (imagesx($my_collage)/2) - (imagesx($frame)/2);
                $y = (imagesy($my_collage)/2) - (imagesy($frame)/2);
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $my_collage_width, $my_collage_height);
            }

            $new_width = imagesx($images_rotated[0]);
            $new_height = imagesy($images_rotated[0]);

            $height_offset = ((($my_collage_height/2)-$new_height)/2);
            $width_offset = (($my_collage_width-($new_width*4))/5);

            $positions_top = [[$width_offset, $height_offset], [($width_offset*2+$new_width), $height_offset], [($width_offset*3+2*$new_width), $height_offset], [($width_offset*4+3*$new_width), $height_offset]];
            $positions_bottom = [[$width_offset, ($new_height+(3*$height_offset))], [($width_offset*2+$new_width), ($new_height+(3*$height_offset))], [($width_offset*3+2*$new_width), ($new_height+(3*$height_offset))], [($width_offset*4+3*$new_width), ($new_height+(3*$height_offset))]];

            for ($i = 0; $i < 4; $i++) {
                $position_top = $positions_top[$i];
                $position_bottom = $positions_bottom[$i];

                imagecopy( $my_collage, $images_rotated[$i],$position_top[0],$position_top[1],0,0,$new_width,$new_height);
                imagecopy( $my_collage, $images_rotated[$i],$position_bottom[0],$position_bottom[1],0,0,$new_width,$new_height);
            }

            imagescale($my_collage, $width_before, $height_before);

            imagejpeg($my_collage, $destImagePath);
            imagedestroy($my_collage);
            break;
        case '2x4BI':
            $degrees = 90;
            $widthNew=321;
            $heightNew=482;
            $PositionsX = [63, 423, 785, 1146]; //X offset in Pixel
            $PositionsY =[57, 642];             //Y offset in Pixel
            $my_collage= imagecreatefrompng($background_image);
            list($bg_width, $bg_height) = getimagesize($background_image);

            for ($j = 0; $j < 2; $j++) { //delta Y
                $dY =$PositionsY[$j];
                for ($i = 0; $i < 4; $i++) { // delta X
                    $dX =$PositionsX[$i];
                    if (!file_exists($srcImagePaths[$i])) {
                        return false;
                    }
                    $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

                    if ($takeFrame && $takeFrameAlways) {
                        $frame = imagecreatefrompng($framePath);
                        $frame = resizePngImage($frame, imagesx($tempSubImage), imagesy($tempSubImage));
                        $x = (imagesx($tempSubImage)/2) - (imagesx($frame)/2);
                        $y = (imagesy($tempSubImage)/2) - (imagesy($frame)/2);
                        imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                    }

                    $tempSubRotated = imagerotate($tempSubImage, $degrees, 0);// Rotate image
                    list($width, $height) = getimagesize($srcImagePaths[0]);
                    imagecopyresized($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew, $height, $width); // copy image to background
                    imagedestroy($tempSubRotated); // Destroy temporary images
                    imagedestroy($tempSubImage);  // Destroy temporary images
                }
            }
            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $bg_width, $bg_height);
                $x = (imagesx($my_collage)/2) - (imagesx($frame)/2);
                $y = (imagesy($my_collage)/2) - (imagesy($frame)/2);
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $bg_width, $bg_height);
            }
            imagejpeg($my_collage, $destImagePath); // Transfer image to destImagePath with returns the image to core
            imagedestroy($my_collage); // Destroy the created collage in memory
           break;
        default:
            list($width, $height) = getimagesize($srcImagePaths[0]);

            $my_collage = imagecreatetruecolor($width, $height);
            imagejpeg($my_collage, $destImagePath); // Transfer image to destImagePath with returns the image to core
            imagedestroy($my_collage); // Destroy the created collage in memory
            break;
    }
    return true;
}
