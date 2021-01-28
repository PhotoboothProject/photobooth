<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/resize.php';

define('LAYOUT', $config['collage']['layout']);

function createCollage($srcImagePaths, $destImagePath, $takeFrame, $takeFrameAlways, $framePath, $background_image) {
    if (!is_array($srcImagePaths) || count($srcImagePaths) !== 4) {
        return false;
    }

    $rotate_after_creation = false;

    list($width, $height) = getimagesize($srcImagePaths[0]);
    if ($width > $height) {
        $landscape = true;
    } else {
        $landscape = false;
        for ($i = 0; $i < 4; $i++) {
            $tempImage = imagecreatefromjpeg($srcImagePaths[$i]);
            $tempSubRotated = imagerotate($tempImage, 90, 0);
            imagejpeg($tempSubRotated, $srcImagePaths[$i]);
            imagedestroy($tempImage);
        }
        list($width, $height) = getimagesize($srcImagePaths[0]);
    }

    switch (LAYOUT) {
        case '2x2':
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
                    $x = imagesx($tempSubImage) / 2 - imagesx($frame) / 2;
                    $y = imagesy($tempSubImage) / 2 - imagesy($frame) / 2;
                    imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                }

                imagecopyresized($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width / 2, $height / 2, $width, $height);
                imagedestroy($tempSubImage);
            }
            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $width, $height);
                $x = imagesx($my_collage) / 2 - imagesx($frame) / 2;
                $y = imagesy($my_collage) / 2 - imagesy($frame) / 2;
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $width, $height);
            }
            break;
        case '2x4':
            if ($landscape) {
                $rotate_after_creation = true;
            }
            $degrees = 90;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 240, 240, 240);
            imagefill($my_collage, 0, 0, $background);

            $images_rotated = [];

            for ($i = 0; $i < 4; $i++) {
                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if ($takeFrame && $takeFrameAlways) {
                    $frame = imagecreatefrompng($framePath);
                    $frame = resizePngImage($frame, imagesx($tempSubImage), imagesy($tempSubImage));
                    $x = imagesx($tempSubImage) / 2 - imagesx($frame) / 2;
                    $y = imagesy($tempSubImage) / 2 - imagesy($frame) / 2;
                    imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                }

                $tempSubRotated = imagerotate($tempSubImage, $degrees, 0);
                $images_rotated[] = resizeImage($tempSubRotated, $height / 3.3, $width / 3.5);
            }

            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $width, $height);
                $x = imagesx($my_collage) / 2 - imagesx($frame) / 2;
                $y = imagesy($my_collage) / 2 - imagesy($frame) / 2;
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $width, $height);
            }

            $new_width = imagesx($images_rotated[0]);
            $new_height = imagesy($images_rotated[0]);

            $height_offset = ($height / 2 - $new_height) / 2;
            $width_offset = ($width - $new_width * 4) / 5;

            $positions_top = [
                [$width_offset, $height_offset],
                [$width_offset * 2 + $new_width, $height_offset],
                [$width_offset * 3 + 2 * $new_width, $height_offset],
                [$width_offset * 4 + 3 * $new_width, $height_offset],
            ];
            $positions_bottom = [
                [$width_offset, $new_height + 3 * $height_offset],
                [$width_offset * 2 + $new_width, $new_height + 3 * $height_offset],
                [$width_offset * 3 + 2 * $new_width, $new_height + 3 * $height_offset],
                [$width_offset * 4 + 3 * $new_width, $new_height + 3 * $height_offset],
            ];

            for ($i = 0; $i < 4; $i++) {
                $position_top = $positions_top[$i];
                $position_bottom = $positions_bottom[$i];

                imagecopy($my_collage, $images_rotated[$i], $position_top[0], $position_top[1], 0, 0, $new_width, $new_height);
                imagecopy($my_collage, $images_rotated[$i], $position_bottom[0], $position_bottom[1], 0, 0, $new_width, $new_height);
            }

            imagescale($my_collage, $width, $height);
            break;
        case '2x4BI':
            if ($landscape) {
                $rotate_after_creation = true;
            }
            $degrees = 90;
            $widthNew = 321;
            $heightNew = 482;
            $PositionsX = [63, 423, 785, 1146]; //X offset in Pixel
            $PositionsY = [57, 642]; //Y offset in Pixel
            $my_collage = imagecreatefrompng($background_image);
            list($bg_width, $bg_height) = getimagesize($background_image);

            for ($i = 0; $i < 4; $i++) {
                ResizeCropImage($heightNew, $widthNew, $srcImagePaths[$i], $srcImagePaths[$i]);
            }
            list($width, $height) = getimagesize($srcImagePaths[0]);

            for ($j = 0; $j < 2; $j++) {
                //delta Y
                $dY = $PositionsY[$j];
                for ($i = 0; $i < 4; $i++) {
                    // delta X
                    $dX = $PositionsX[$i];
                    if (!file_exists($srcImagePaths[$i])) {
                        return false;
                    }
                    $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);

                    if ($takeFrame && $takeFrameAlways) {
                        $frame = imagecreatefrompng($framePath);
                        $frame = resizePngImage($frame, imagesx($tempSubImage), imagesy($tempSubImage));
                        $x = imagesx($tempSubImage) / 2 - imagesx($frame) / 2;
                        $y = imagesy($tempSubImage) / 2 - imagesy($frame) / 2;
                        imagecopy($tempSubImage, $frame, $x, $y, 0, 0, imagesx($frame), imagesy($frame));
                    }

                    $tempSubRotated = imagerotate($tempSubImage, $degrees, 0); // Rotate image
                    imagecopy($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew); // copy image to background
                    imagedestroy($tempSubRotated); // Destroy temporary images
                    imagedestroy($tempSubImage); // Destroy temporary images
                }
            }
            if ($takeFrame && !$takeFrameAlways) {
                $frame = imagecreatefrompng($framePath);
                $frame = resizePngImage($frame, $bg_width, $bg_height);
                $x = imagesx($my_collage) / 2 - imagesx($frame) / 2;
                $y = imagesy($my_collage) / 2 - imagesy($frame) / 2;
                imagecopy($my_collage, $frame, $x, $y, 0, 0, $bg_width, $bg_height);
            }
            break;
        default:
            $my_collage = imagecreatetruecolor($width, $height);
            break;
    }

    imagejpeg($my_collage, $destImagePath); // Transfer image to destImagePath with returns the image to core
    imagedestroy($my_collage); // Destroy the created collage in memory

    // Rotate image if needed
    if ($rotate_after_creation) {
        $tempRotatedImage = imagecreatefromjpeg($destImagePath);
        $resultRotated = imagerotate($tempRotatedImage, -90, 0);
        imagejpeg($resultRotated, $destImagePath);
        imagedestroy($tempRotatedImage);
    }
    return true;
}
