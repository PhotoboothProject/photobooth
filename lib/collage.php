<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/resize.php';
require_once __DIR__ . '/applyFrame.php';
require_once __DIR__ . '/applyText.php';
require_once __DIR__ . '/filter.php';
require_once __DIR__ . '/polaroid.php';

define('COLLAGE_LAYOUT', $config['collage']['layout']);
define('COLLAGE_FRAME', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $config['collage']['frame']));
define('COLLAGE_TAKE_FRAME', $config['collage']['take_frame']);
define('COLLAGE_LIMIT', $config['collage']['limit']);
define('PICTURE_KEEP_ORIGINAL', $config['picture']['keep_original'] === true ? 'keep' : 'discard');
define('PICTURE_FLIP', $config['picture']['flip']);
define('PICTURE_ROTATION', $config['picture']['rotation']);
define('PICTURE_POLAROID_EFFECT', $config['picture']['polaroid_effect'] === true ? 'enabled' : 'disabled');
define('PICTURE_POLAROID_ROTATION', $config['picture']['polaroid_rotation']);
define('TEXTONCOLLAGE_ENABLED', $config['textoncollage']['enabled'] === true ? 'enabled' : 'disabled');
define('TEXTONCOLLAGE_LINE1', $config['textoncollage']['line1']);
define('TEXTONCOLLAGE_LINE2', $config['textoncollage']['line2']);
define('TEXTONCOLLAGE_LINE3', $config['textoncollage']['line3']);
define('TEXTONCOLLAGE_LOCATIONX', $config['textoncollage']['locationx']);
define('TEXTONCOLLAGE_LOCATIONY', $config['textoncollage']['locationy']);
define('TEXTONCOLLAGE_ROTATION', $config['textoncollage']['rotation']);
define('TEXTONCOLLAGE_FONT', $config['textoncollage']['font']);
define('TEXTONCOLLAGE_FONT_COLOR', $config['textoncollage']['font_color']);
define('TEXTONCOLLAGE_FONT_SIZE', $config['textoncollage']['font_size']);
define('TEXTONCOLLAGE_LINESPACE', $config['textoncollage']['linespace']);

function createCollage($srcImagePaths, $destImagePath, $filter = 'plain') {
    $rotate_after_creation = false;
    $quality = 100;
    $image_filter = false;
    $imageModified = false;
    if (!empty($filter) && $filter !== 'plain') {
        $image_filter = $filter;
    }

    // colors for background while rotating jpeg images
    $white = 16777215;
    $black = 0;

    if (!is_array($srcImagePaths) || count($srcImagePaths) !== COLLAGE_LIMIT) {
        return false;
    }

    for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
        if (!file_exists($srcImagePaths[$i])) {
            $errormsg = 'File ' . $srcImagePaths[$i] . ' does not exist';
            logErrorAndDie($errormsg);
        }

        $imageResource = imagecreatefromjpeg($srcImagePaths[$i]);
        // Only jpg/jpeg are supported
        if (!$imageResource) {
            $errormsg = 'Could not read jpeg file. Are you taking raws?';
            logErrorAndDie($errormsg);
        }

        if (PICTURE_KEEP_ORIGINAL === 'keep') {
            $singleimage = substr($srcImagePaths[$i], 0, -4);
            $origfilename = $singleimage . '-orig.jpg';
            copy($srcImagePaths[$i], $origfilename);
        }

        if (PICTURE_FLIP !== 'off') {
            if (PICTURE_FLIP === 'horizontal') {
                imageflip($imageResource, IMG_FLIP_HORIZONTAL);
            } elseif (PICTURE_FLIP === 'vertical') {
                imageflip($imageResource, IMG_FLIP_VERTICAL);
            } elseif (PICTURE_FLIP === 'both') {
                imageflip($imageResource, IMG_FLIP_BOTH);
            }
            $imageModified = true;
        }

        // apply filter
        if ($image_filter) {
            applyFilter($image_filter, $imageResource);
            $imageModified = true;
        }

        if (PICTURE_ROTATION !== '0') {
            $rotatedImg = imagerotate($imageResource, PICTURE_ROTATION, $white);
            $imageResource = $rotatedImg;
            $imageModified = true;
        }

        if (PICTURE_POLAROID_EFFECT === 'enabled') {
            $polaroid_rotation = PICTURE_POLAROID_ROTATION;
            $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
            $imageModified = true;
        }

        if ($imageModified) {
            imagejpeg($imageResource, $srcImagePaths[$i], $quality);
        }

        imagedestroy($imageResource);
    }

    list($width, $height) = getimagesize($srcImagePaths[0]);
    if ($width > $height) {
        $landscape = true;
    } else {
        $landscape = false;
        for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
            $tempImage = imagecreatefromjpeg($srcImagePaths[$i]);
            $tempSubRotated = imagerotate($tempImage, 90, $white);
            imagejpeg($tempSubRotated, $srcImagePaths[$i], $quality);
            imagedestroy($tempImage);
        }
        list($width, $height) = getimagesize($srcImagePaths[0]);
    }

    switch (COLLAGE_LAYOUT) {
        case '2x2':
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 0, 0, 0);
            imagecolortransparent($my_collage, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $positions = [[0, 0], [$width / 2, 0], [0, $height / 2], [$width / 2, $height / 2]];

            for ($i = 0; $i < 4; $i++) {
                $position = $positions[$i];

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                imagecopyresized($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width / 2, $height / 2, $width, $height);
                imagedestroy($tempSubImage);
            }
            break;
        case '2x2-2':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $degrees = 0;
            $heightp = 469;
            $widthp = 636;
            $PositionsX = [125, 810, 125, 810]; //X offset in Pixel
            $PositionsYtop = 111; // y offset for top pictures
            $PositionsYbot = 625; //y offset for bottom pictures

            for ($i = 0; $i < 4; $i++) {
                if ($i < 2) {
                    ResizeCropImage($widthp, $heightp, $srcImagePaths[$i], $srcImagePaths[$i]);
                    $dX = $PositionsX[$i];
                    $dY = $PositionsYtop;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                } else {
                    ResizeCropImage($widthp, $heightp, $srcImagePaths[$i], $srcImagePaths[$i]);
                    $dX = $PositionsX[$i];
                    $dY = $PositionsYbot;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                }

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                $tempSubRotated = imagerotate($tempSubImage, $degrees, $white); // Rotate image
                imagecopy($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew); // copy image to background
                imagedestroy($tempSubRotated); // Destroy temporary images
                imagedestroy($tempSubImage); // Destroy temporary images
            }
            break;
        case '2x4':
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape) {
                $rotate_after_creation = true;
            }
            $degrees = 90;
            $images_rotated = [];

            for ($i = 0; $i < 4; $i++) {
                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                $tempSubRotated = imagerotate($tempSubImage, $degrees, $white);
                $images_rotated[] = resizeImage($tempSubRotated, $height / 3.3, $width / 3.5);
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
        case '2x4-2':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape) {
                $rotate_after_creation = true;
            }
            $degrees = 90;
            $widthNew = 321;
            $heightNew = 482;
            $PositionsX = [63, 423, 785, 1146]; //X offset in Pixel
            $PositionsY = [64, 652]; //Y offset in Pixel

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

                    if (COLLAGE_TAKE_FRAME === 'always') {
                        ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                    }

                    $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                    $tempSubRotated = imagerotate($tempSubImage, $degrees, $white); // Rotate image
                    imagecopy($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew); // copy image to background
                    imagedestroy($tempSubRotated); // Destroy temporary images
                    imagedestroy($tempSubImage); // Destroy temporary images
                }
            }
            break;
        case '1+3':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $degrees = 0;
            $heightbig = 519;
            $widthbig = 813;
            $heightsmall = 361;
            $widthsmall = 527;
            $PositionsX = [0, 81, 638, 1196]; //X offset in Pixel for Small
            $PositionsXB = 910; // X offset in Pixel for Big
            $PositionsYB = 71; //Y offset in Pixel for Big Pic
            $PositionsYS = 749; //Y offset in Pixel for Small Pic

            for ($i = 0; $i < 4; $i++) {
                if ($i == 0) {
                    ResizeCropImage($widthbig, $heightbig, $srcImagePaths[$i], $srcImagePaths[$i]);
                    // delta X
                    $dX = $PositionsXB;
                    $dY = $PositionsYB;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[0]);
                } else {
                    ResizeCropImage($widthsmall, $heightsmall, $srcImagePaths[$i], $srcImagePaths[$i]);
                    $dX = $PositionsX[$i];
                    $dY = $PositionsYS;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                }
                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                $tempSubRotated = imagerotate($tempSubImage, $degrees, $white); // Rotate image
                imagecopy($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew); // copy image to background
                imagedestroy($tempSubRotated); // Destroy temporary images
                imagedestroy($tempSubImage); // Destroy temporary images
            }
            break;
        case '1+3-2':
        case '3+1':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }

            if (COLLAGE_LAYOUT === '1+3-2') {
                $positions = [[60, 60], [60, 730], [640, 730], [1220, 730]];
            } else {
                // 3+1 Layout
                $positions = [[60, 60], [640, 60], [1220, 60], [60, 505]];
            }

            for ($i = 0; $i < 4; $i++) {
                $position = $positions[$i];

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                list($picWidth, $picHeight) = getimagesize($srcImagePaths[$i]);
                if (COLLAGE_LAYOUT === '1+3-2') {
                    switch ($i) {
                        // Picture 1, // Picture 2, // Picture 3,
                        case 0:
                            $widthNew = $picWidth * 0.65;
                            $heightNew = $picHeight * 0.65;
                            break;
                        // Picture 2, // Picture 3, // Picture 4
                        case 1:
                        case 2:
                        case 3:
                            $widthNew = $picWidth * 0.4;
                            $heightNew = $picHeight * 0.4;
                            break;
                    }
                } else {
                    // 3+1 Layout
                    switch ($i) {
                        // Picture 1, // Picture 2, // Picture 3
                        case 0:
                        case 1:
                        case 2:
                            $widthNew = $picWidth * 0.4;
                            $heightNew = $picHeight * 0.4;
                            break;
                        // Picture 4
                        case 3:
                            $widthNew = $picWidth * 0.75;
                            $heightNew = $picHeight * 0.75;
                            break;
                    }
                }
                ResizeCropImage($widthNew, $heightNew, $srcImagePaths[$i], $srcImagePaths[$i]);

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                imagecopy($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $widthNew, $heightNew); // copy image to background
                imagedestroy($tempSubImage);
            }
            break;
        case '1+2':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, 255, 255, 255);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $degrees = 0;
            $heightbig = 626;
            $widthbig = 965;
            $heightsmall = 422;
            $widthsmall = 624;
            $PositionsX = 1125; //X offset in Pixel for Small
            $PositionsXB = 22; // X offset in Pixel for Big
            $PositionsYB = 65; //Y offset in Pixel for Big Pic
            $PositionsYS = 133; //Y offset in Pixel for Small Pic
            $PositionsYSs = 634; //Y offset in Pixel for Small Pic

            for ($i = 0; $i < 3; $i++) {
                if ($i == 0) {
                    ResizeCropImage($widthbig, $heightbig, $srcImagePaths[$i], $srcImagePaths[$i]);
                    // delta X
                    $dX = $PositionsXB;
                    $dY = $PositionsYB;
                } elseif ($i == 1) {
                    ResizeCropImage($widthsmall, $heightsmall, $srcImagePaths[$i], $srcImagePaths[$i]);
                    // delta X
                    $dX = $PositionsX;
                    $dY = $PositionsYS;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                } elseif ($i == 2) {
                    ResizeCropImage($widthsmall, $heightsmall, $srcImagePaths[$i], $srcImagePaths[$i]);
                    // delta X
                    $dX = $PositionsX;
                    $dY = $PositionsYSs;
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                }

                if (!file_exists($srcImagePaths[$i])) {
                    return false;
                }

                if (COLLAGE_TAKE_FRAME === 'always') {
                    ApplyFrame($srcImagePaths[$i], $srcImagePaths[$i], COLLAGE_FRAME);
                }

                if ($i == 0) {
                    $degrees = 11;
                    $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                    // Rotate image and add white background
                    $tempRotate = imagerotate($tempSubImage, $degrees, $white);
                    imagejpeg($tempRotate, $srcImagePaths[$i], $quality);
                    // get new width and height after rotation
                    list($widthNew, $heightNew) = getimagesize($srcImagePaths[$i]);
                    imagedestroy($tempRotate);
                    imagedestroy($tempSubImage);
                }
                $degrees = 0;
                $tempSubImage = imagecreatefromjpeg($srcImagePaths[$i]);
                $tempSubRotated = imagerotate($tempSubImage, $degrees, $white); // Rotate image
                imagecopy($my_collage, $tempSubRotated, $dX, $dY, 0, 0, $widthNew, $heightNew); // copy image to background
                imagedestroy($tempSubRotated); // Destroy temporary images
                imagedestroy($tempSubImage); // Destroy temporary images
            }
            break;
        default:
            $my_collage = imagecreatetruecolor($width, $height);
            break;
    }

    imagejpeg($my_collage, $destImagePath, $quality); // Transfer image to destImagePath with returns the image to core

    if (COLLAGE_TAKE_FRAME === 'once') {
        ApplyFrame($destImagePath, $destImagePath, COLLAGE_FRAME);
    }

    if (TEXTONCOLLAGE_ENABLED === 'enabled') {
        ApplyText(
            $destImagePath,
            TEXTONCOLLAGE_FONT_SIZE,
            TEXTONCOLLAGE_ROTATION,
            TEXTONCOLLAGE_LOCATIONX,
            TEXTONCOLLAGE_LOCATIONY,
            TEXTONCOLLAGE_FONT_COLOR,
            TEXTONCOLLAGE_FONT,
            TEXTONCOLLAGE_LINE1,
            TEXTONCOLLAGE_LINE2,
            TEXTONCOLLAGE_LINE3,
            TEXTONCOLLAGE_LINESPACE
        );
    }

    imagedestroy($my_collage); // Destroy the created collage in memory

    // Rotate image if needed
    if ($rotate_after_creation) {
        $tempRotatedImage = imagecreatefromjpeg($destImagePath);
        $resultRotated = imagerotate($tempRotatedImage, -90, $white);
        imagejpeg($resultRotated, $destImagePath, $quality);
        imagedestroy($tempRotatedImage);
    }
    return true;
}
