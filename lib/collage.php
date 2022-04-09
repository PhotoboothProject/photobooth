<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/resize.php';
require_once __DIR__ . '/applyFrame.php';
require_once __DIR__ . '/applyText.php';
require_once __DIR__ . '/filter.php';
require_once __DIR__ . '/polaroid.php';

define('COLLAGE_LAYOUT', $config['collage']['layout']);
define('COLLAGE_BACKGROUND_COLOR', $config['collage']['background_color']);
define('COLLAGE_FRAME', $config['collage']['frame']);
define('COLLAGE_TAKE_FRAME', $config['collage']['take_frame']);
define('COLLAGE_DASHEDLINE_COLOR', $config['collage']['dashedline_color']);
define('COLLAGE_LIMIT', $config['collage']['limit']);
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

function createCollage($srcImagePaths, $destImagePath, $filter = 'plain')
{
    $editImages = [];
    $rotate_after_creation = false;
    $quality = 100;
    $image_filter = false;
    $imageModified = false;
    if (!empty($filter) && $filter !== 'plain') {
        $image_filter = $filter;
    }

    // colors for background and while rotating jpeg images
    list($bg_r, $bg_g, $bg_b) = sscanf(COLLAGE_BACKGROUND_COLOR, '#%02x%02x%02x');
    $bg_color_hex = hexdec(substr(COLLAGE_BACKGROUND_COLOR, 1));

    // dashedline color on 2x4 collage layouts
    list($dashed_r, $dashed_g, $dashed_b) = sscanf(COLLAGE_DASHEDLINE_COLOR, '#%02x%02x%02x');

    if (!is_array($srcImagePaths) || count($srcImagePaths) !== COLLAGE_LIMIT) {
        return false;
    }

    for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
        if (!file_exists($srcImagePaths[$i])) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $srcImagePaths[$i] . ' does not exist';
            logErrorAndDie($errormsg);
        }

        $singleimage = substr($srcImagePaths[$i], 0, -4);
        $editfilename = $singleimage . '-edit.jpg';
        copy($srcImagePaths[$i], $editfilename);
        $editImages[] = $editfilename;
    }

    for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
        $imageResource = imagecreatefromjpeg($editImages[$i]);
        // Only jpg/jpeg are supported
        if (!$imageResource) {
            $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not read jpeg file. Are you taking raws?';
            logErrorAndDie($errormsg);
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
            $imageResource = rotateResizeImage($imageResource, PICTURE_ROTATION, COLLAGE_BACKGROUND_COLOR);
            $imageModified = true;
        }

        if (PICTURE_POLAROID_EFFECT === 'enabled') {
            $imageResource = effectPolaroid($imageResource, PICTURE_POLAROID_ROTATION, 200, 200, 200);
            $imageModified = true;
        }

        $width = imagesx($imageResource);
        $height = imagesy($imageResource);

        if ($width > $height) {
            $landscape = true;
        } else {
            $landscape = false;
            $imageResource = imagerotate($imageResource, 90, $bg_color_hex);
            $width = imagesx($imageResource);
            $height = imagesy($imageResource);
            $imageModified = true;
        }

        if ($imageModified) {
            imagejpeg($imageResource, $editImages[$i], $quality);
        }

        imagedestroy($imageResource);
    }

    switch (COLLAGE_LAYOUT) {
        // old 2x2 are now named 2+2 as 2x means images are duplicated
        case '2x2':
        case '2+2':
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagecolortransparent($my_collage, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }

            $positions = [[0, 0], [$width / 2, 0], [0, $height / 2], [$width / 2, $height / 2]];

            for ($i = 0; $i < 4; $i++) {
                $position = $positions[$i];

                if (!file_exists($editImages[$i])) {
                    return false;
                }

                $tempSubImage = imagecreatefromjpeg($editImages[$i]);

                if (COLLAGE_TAKE_FRAME === 'always' && testFile(COLLAGE_FRAME)) {
                    $tempSubImage = applyFrame($tempSubImage, COLLAGE_FRAME);
                }

                // copy image to background
                imagecopyresized($my_collage, $tempSubImage, $position[0], $position[1], 0, 0, $width / 2, $height / 2, $width, $height);
                // destroy temporary images
                imagedestroy($tempSubImage);
            }
            break;
        case '2x2-2':
        case '2+2-2':
            // TODO create a new image for every collage type. 1800x1200 is 300dpi for 6x4 - less than the quality of my printer. add an option for higher quality collages?
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $heightp = 469;
            $widthp = $heightp * 1.5;
            $pictureOptions = [
                [170, 111, $heightp, $widthp, 0],
                [925, 111, $heightp, $widthp, 0],
                [170, 625, $heightp, $widthp, 0],
                [925, 625, $heightp, $widthp, 0]
            ];

            for ($i = 0; $i < 4; $i++) {
                addPicture($editImages[$i], $pictureOptions[$i], $my_collage);
            }
            break;
        case '1+3':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $heightNewBig = 520;
            $widthNewBig = $heightNewBig * 1.5;
            $heightNewSmall = 360;
            $widthNewSmall = $heightNewSmall * 1.5;
            $pictureOptions = [
                [950, 71, $widthNewBig, $heightNewBig, 0],
                [80, 749, $widthNewSmall, $heightNewSmall, 0],
                [637, 749, $widthNewSmall, $heightNewSmall, 0],
                [1195, 749, $widthNewSmall, $heightNewSmall, 0]
            ];

            for ($i = 0; $i < 4; $i++) {
                addPicture($editImages[$i], $pictureOptions[$i], $my_collage);
            }
            break;
        case '1+3-2':
        case '3+1':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }

            list($picWidth, $picHeight) = getimagesize($editImages[$i]);
            $widthNewBig = $picWidth * 0.65;
            $heightNewBig = $picHeight * 0.65;
            $widthNewSmall = $picWidth * 0.4;
            $heightNewSmall = $picHeight * 0.4;
            if (COLLAGE_LAYOUT === '1+3-2') {
                $pictureOptions = [
                    [60, 60, $widthNewBig, $heightNewBig, 0],
                    [60, 730, $widthNewSmall, $heightNewSmall, 0],
                    [640, 730, $widthNewSmall, $heightNewSmall, 0],
                    [1220, 730, $widthNewSmall, $heightNewSmall, 0]
                ];
                $positions = [[60, 60], [60, 730], [640, 730], [1220, 730]];
            } else {
                // 3+1 Layout
                $pictureOptions = [
                    [60, 60, $widthNewSmall, $heightNewSmall, 0],
                    [640, 60, $widthNewSmall, $heightNewSmall, 0],
                    [1220, 60, $widthNewSmall, $heightNewSmall, 0],
                    [60, 505, $widthNewBig, $heightNewBig, 0]
                ];
                $positions = [[60, 60], [640, 60], [1220, 60], [60, 505]];
            }

            for ($i = 0; $i < 4; $i++) {
                addPicture($editImages[$i], $pictureOptions[$i], $my_collage);
            }
            break;
        case '1+2':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape == false) {
                $rotate_after_creation = true;
            }
            $heightNewBig = 626;
            $widthNewBig = $heightNewBig * 1.5;
            $heightNewSmall = 422;
            $widthNewSmall = $heightNewSmall * 1.5;
            $pictureOptions = [
                [40, 65, $widthNewBig, $heightNewBig, 11],
                [1125, 133, $widthNewSmall, $heightNewSmall, 0],
                [1125, 634, $widthNewSmall, $heightNewSmall, 0],
            ];

            for ($i = 0; $i < 3; $i++) {
                addPicture($editImages[$i], $pictureOptions[$i], $my_collage);
            }
            break;
        case '2x4':
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape) {
                $rotate_after_creation = true;
            }
            $degrees = 90;
            $images_rotated = [];
            for ($i = 0; $i < 4; $i++) {
                if (!file_exists($editImages[$i])) {
                    return false;
                }

                $tempSubImage = imagecreatefromjpeg($editImages[$i]);

                if (COLLAGE_TAKE_FRAME === 'always' && testFile(COLLAGE_FRAME)) {
                    $tempSubImage = applyFrame($tempSubImage, COLLAGE_FRAME);
                }

                $tempSubImage = imagerotate($tempSubImage, $degrees, $bg_color_hex);
                $images_rotated[] = resizeImage($tempSubImage, $height / 3.3, $width / 3.5);
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
            $dashedline_color = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            drawDashedLine($my_collage, 50, $height / 2, $width - 50, $height / 2, $dashedline_color);
            break;
        case '2x4-2':
            $width = 1800;
            $height = 1200;
            $my_collage = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
            imagefill($my_collage, 0, 0, $background);

            if ($landscape) {
                $rotate_after_creation = true;
            }
            $widthNew = 321;
            $heightNew = $widthNew * 1.5;
            $pictureOptions = [
                [63, 64, $widthNew, $heightNew, 90],
                [423, 64, $widthNew, $heightNew, 90],
                [785, 64, $widthNew, $heightNew, 90],
                [1146, 64, $widthNew, $heightNew, 90],
                [63, 652, $widthNew, $heightNew, 90],
                [423, 652, $widthNew, $heightNew, 90],
                [785, 652, $widthNew, $heightNew, 90],
                [1146, 652, $widthNew, $heightNew, 90],
            ];

            for ($i = 0; $i < 4; $i++) {
                addPicture($editImages[$i], $pictureOptions[$i], $my_collage);
                addPicture($editImages[$i + 4], $pictureOptions[$i + 4], $my_collage);
            }
            $dashedline_color = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            drawDashedLine($my_collage, 50, 600, 1750, 600, $dashedline_color);
            break;
        default:
            $my_collage = imagecreatetruecolor($width, $height);
            break;
    }

    if (COLLAGE_TAKE_FRAME === 'once' && testFile(COLLAGE_FRAME)) {
        $my_collage = applyFrame($my_collage, COLLAGE_FRAME);
    }

    if (TEXTONCOLLAGE_ENABLED === 'enabled' && testFile(TEXTONCOLLAGE_FONT)) {
        $my_collage = applyText(
            $my_collage,
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

    // Rotate image if needed
    if ($rotate_after_creation) {
        $my_collage = imagerotate($my_collage, -90, $bg_color_hex);
    }

    // Transfer image to destImagePath with returns the image to core
    imagejpeg($my_collage, $destImagePath, $quality);
    // Destroy the created collage in memory
    imagedestroy($my_collage);

    foreach ($editImages as $tmp) {
        unlink($tmp);
    }

    return true;
}

function drawDashedLine($my_collage, $x1, $y1, $x2, $y2, $dashedline_color)
{
    $dashed_style = array($dashedline_color, $dashedline_color, $dashedline_color, $dashedline_color,
        IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT);
    imagesetstyle($my_collage, $dashed_style);
    imageline($my_collage, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
}

function addPicture($filename, $pictureOptions, $my_collage)
{
    $dX = $pictureOptions[0];
    $dY = $pictureOptions[1];
    $height = $pictureOptions[2];
    $width = $pictureOptions[3];
    $degrees = $pictureOptions[4];

    $tempSubImage = imagecreatefromjpeg($filename);
    $tempSubImage = resizeCropImage($width, $height, $tempSubImage);

    if (COLLAGE_TAKE_FRAME === 'always' && testFile(COLLAGE_FRAME)) {
        $tempSubImage = applyFrame($tempSubImage, COLLAGE_FRAME);
    }

    if ($degrees != 0) {
        $tempSubImage = rotateResizeImage($tempSubImage, $degrees, COLLAGE_BACKGROUND_COLOR);
    }

    $widthNew = imagesx($tempSubImage);
    $heightNew = imagesy($tempSubImage);
    imagecopy($my_collage, $tempSubImage, $dX, $dY, 0, 0, $widthNew, $heightNew);
    imagedestroy($tempSubImage);
}
