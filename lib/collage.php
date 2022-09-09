<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/resize.php';
require_once __DIR__ . '/applyFrame.php';
require_once __DIR__ . '/applyText.php';
require_once __DIR__ . '/filter.php';
require_once __DIR__ . '/polaroid.php';

define('COLLAGE_LAYOUT', $config['collage']['layout']);
define('COLLAGE_RESOLUTION', (int) substr($config['collage']['resolution'], 0, -3));
define('COLLAGE_BACKGROUND_COLOR', $config['collage']['background_color']);
define('COLLAGE_FRAME', $config['collage']['frame']);
define('COLLAGE_TAKE_FRAME', $config['collage']['take_frame']);
define('COLLAGE_PLACEHOLDER', $config['collage']['placeholder']);
// If a placeholder is set, decrease the value by 1 in order to reflect array counting at 0
define('COLLAGE_PLACEHOLDER_POSITION', (int) $config['collage']['placeholderposition'] - 1);
define('COLLAGE_PLACEHOLDER_PATH', $config['collage']['placeholderpath']);
define('COLLAGE_DASHEDLINE_COLOR', $config['collage']['dashedline_color']);
// If a placholder image should be used, we need to increase the limit here in order to count the images correct
define('COLLAGE_LIMIT', $config['collage']['placeholder'] ? $config['collage']['limit'] + 1 : $config['collage']['limit']);
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

    if (!is_array($srcImagePaths)) {
        return false;
    }

    // validate that there is the correct amount of images
    if ((COLLAGE_PLACEHOLDER && count($srcImagePaths) !== COLLAGE_LIMIT - 1) || (!COLLAGE_PLACEHOLDER && count($srcImagePaths) !== COLLAGE_LIMIT)) {
        return false;
    }

    // If there is a placeholder defined, we need to make sure that the image at the placeholder path exists.
    if (COLLAGE_PLACEHOLDER && !testFile(COLLAGE_PLACEHOLDER_PATH)) {
        return false;
    }

    //Use offset to reflect image file numbering
    $placeholderOffset = 0;
    for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
        if (COLLAGE_PLACEHOLDER && COLLAGE_PLACEHOLDER_POSITION == $i) {
            $editImages[] = COLLAGE_PLACEHOLDER_PATH;
            $placeholderOffset = 1;
        } else {
            if (!file_exists($srcImagePaths[$i - $placeholderOffset])) {
                $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $srcImagePaths[$i] . ' does not exist';
                logErrorAndDie($errormsg);
            }
            $singleimage = substr($srcImagePaths[$i - $placeholderOffset], 0, -4);
            $editfilename = $singleimage . '-edit.jpg';
            copy($srcImagePaths[$i - $placeholderOffset], $editfilename);
            $editImages[] = $editfilename;
        }
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
    //Create Collage based on 300dpi 4x6in - Scale collages with the height
    $collage_height = 4 * COLLAGE_RESOLUTION;
    $collage_width = $collage_height * 1.5;

    $my_collage = imagecreatetruecolor($collage_width, $collage_height);
    $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
    imagefill($my_collage, 0, 0, $background);
    if ($landscape == false) {
        $rotate_after_creation = true;
    }

    switch (COLLAGE_LAYOUT) {
        // old 2x2 are now named 2+2 as 2x means images are duplicated
        case '2x2':
        case '2+2':
            // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
            $pictureOptions = [
                [0, 0, $collage_width / 2, $collage_height / 2, 0],
                [$collage_width / 2, 0, $collage_width / 2, $collage_height / 2, 0],
                [0, $collage_height / 2, $collage_width / 2, $collage_height / 2, 0],
                [$collage_width / 2, $collage_height / 2, $collage_width / 2, $collage_height / 2, 0],
            ];

            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }

            break;
        case '2x2-2':
        case '2+2-2':
            $heightRatio = 0.4; // 0.4 = image height ratio. Should be set below 0.5 (as we have 2 pictures). Please adapt the short/long ratio as well
            $shortRatio = 0.08; // shortRatio, distance until the top left corner of the first image
            $longRatio = 0.52; // longRatio = image height ratio + shortRatio + distance between the images. In this case: 0.4 + 0.08 + 0.04 = 0.52.
            // Distance between pictures = 2x (0.5 -heightRatio -shortRatio)
            // Please note: We get a correct picture, if this formula adds up to exactly 1:  2x heightRatio + 2x shortRatio + distance between pictures

            $heightp = $collage_height * $heightRatio;
            $widthp = $heightp * 1.5;

            //If there is a need for Text/Frame, we could specify an additional horizontal offset. E.g. widthp * 0.08
            $horizontalOffset = $widthp * 0;

            // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
            $pictureOptions = [
                [$collage_width * $shortRatio + $horizontalOffset, $collage_height * $shortRatio, $widthp, $heightp, 0],
                [$collage_width * $longRatio + $horizontalOffset, $collage_height * $shortRatio, $widthp, $heightp, 0],
                [$collage_width * $shortRatio + $horizontalOffset, $collage_height * $longRatio, $widthp, $heightp, 0],
                [$collage_width * $longRatio + $horizontalOffset, $collage_height * $longRatio, $widthp, $heightp, 0],
            ];

            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }

            break;
        case '1+3':
            //Specify Big/Small Height Ratios - values based on previos settings
            $heightRatioBig = 0.4978;
            $heightRatioSmall = 0.3052;

            // Vertical Positions for big and small images
            $shortRatioY = 0.08; // shortRatioY, vertical distance until the top left corner of the image
            $longRatioY = 0.6178; // longRatio = heightRatioBig + shortRatioY + distance between the images.
            // Vertical distance between pictures in this case  = 0.5 x shortRatioY.

            // Horizontal Positions for small images
            $shortRatioX = 0.0281; // shortRatioX, horizontal width ratio distance to the left picture
            $mediumRatioX = 0.34736; // mediumRatioX, horizontal width ratio distance to the middle image. shortRatioX + heightRatioSmall + distance between pictures
            $longRatioX = 0.66662; //longRatioX, horizontal width ratio distance to the right image. shortRatioX + 2x heightRatioSmall + 2x distance between pictures
            // Horzontal distance between pictures = 0.5 x shortRatioX

            // Horizontal position of big image
            $ratioBigPictureX = 0.4741; // 1 - shortRatioX - heightRatioBig

            $heightNewBig = $collage_height * $heightRatioBig;
            $widthNewBig = $heightNewBig * 1.5;

            $heightNewSmall = $collage_height * $heightRatioSmall;
            $widthNewSmall = $heightNewSmall * 1.5;

            $pictureOptions = [
                [$collage_width * $ratioBigPictureX, $collage_height * $shortRatioY, $widthNewBig, $heightNewBig, 0],
                [$collage_width * $shortRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
                [$collage_width * $mediumRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
                [$collage_width * $longRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
            ];

            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }

            break;
        case '1+3-2':
        case '3+1':
            //Specify Big/Small Height Ratios - values based on previos settings
            $heightRatioBig = 0.4978;
            $heightRatioSmall = 0.3052;

            if (COLLAGE_LAYOUT === '1+3-2') {
                // Vertical Positions for big and small images
                $shortRatioY = 0.08; // shortRatioY, vertical distance until the top left corner of the image
                $longRatioY = 0.6178; // longRatio = heightRatioBig + shortRatioY + distance between the images.
                // Vertical distance between pictures in this case  = 0.5 x shortRatioY.
            } else {
                // Switch vertical Positions for big and small images
                $shortRatioY = 0.4252; // shortRatioY,  = heightRatioSmall + shortRatioY + distance between the images.
                $longRatioY = 0.08; // longRatio = vertical distance until the top left corner of the image
                // Vertical distance between pictures in this case  = 0.5 x shortRatioY.
            }

            // Horizontal Positions for small images
            $shortRatioX = 0.0281; // shortRatioX, horizontal width ratio distance to the left picture
            $mediumRatioX = 0.34736; // mediumRatioX, horizontal width ratio distance to the middle image. shortRatioX + heightRatioSmall + distance between pictures
            $longRatioX = 0.66662; //longRatioX, horizontal width ratio distance to the right image. shortRatioX + 2x heightRatioSmall + 2x distance between pictures
            // Horzontal distance between pictures = 0.5 x shortRatioX

            // Horizontal position of big image
            $ratioBigPictureX = 0.0281; // shortRatioX

            $heightNewBig = $collage_height * $heightRatioBig;
            $widthNewBig = $heightNewBig * 1.5;

            $heightNewSmall = $collage_height * $heightRatioSmall;
            $widthNewSmall = $heightNewSmall * 1.5;

            $pictureOptions = [
                [$collage_width * $ratioBigPictureX, $collage_height * $shortRatioY, $widthNewBig, $heightNewBig, 0],
                [$collage_width * $shortRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
                [$collage_width * $mediumRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
                [$collage_width * $longRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
            ];

            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }

            break;
        case '1+2':
            //Specify Big/Small Height Ratios - values based on previos settings
            $heightRatioBig = 0.55546; // based on previous value / height
            $heightRatioSmall = 0.40812;

            $shortRatioY = 0.055;
            $longRatioX = 0.555;
            $longRatioY = 0.5368;

            $heightNewBig = $collage_height * $heightRatioBig;
            $widthNewBig = $heightNewBig * 1.5;

            $heightNewSmall = $collage_height * $heightRatioSmall;
            $widthNewSmall = $heightNewSmall * 1.5;

            $pictureOptions = [
                [0, $collage_height * $shortRatioY, $widthNewBig, $heightNewBig, 10],
                [$collage_width * $longRatioX, $collage_height * $shortRatioY, $widthNewSmall, $heightNewSmall, 0],
                [$collage_width * $longRatioX, $collage_height * $longRatioY, $widthNewSmall, $heightNewSmall, 0],
            ];

            for ($i = 0; $i < 3; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }

            break;
        case '2+1':
            $heightRatio = 0.375;

            // Horizontal Ratio
            $shortRatioY = 0.1;
            $longRatioY = 0.525;

            // Vertical Ratio
            $shortRatioX = 0.1;
            $longRatioX = 0.525;

            $heightNew = $collage_height * $heightRatio;
            $widthNew = $heightNew * 1.5;

            $pictureOptions = [
                [$collage_width * $shortRatioY, $collage_height * $shortRatioX, $widthNew, $heightNew, 0],
                [$collage_width * $longRatioY, $collage_height * $shortRatioX, $widthNew, $heightNew, 0],
                [$collage_width * $shortRatioY, $collage_height * $longRatioX, $widthNew, $heightNew, 0],
            ];

            for ($i = 0; $i < 3; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }
            break;
        case '2x4':
            // colage size defined by image size
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

            $height_offset = intval(($height / 2 - $new_height) / 2);
            $width_offset = intval(($width - $new_width * 4) / 5);

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
        case '2x4-3':
            if ($landscape) {
                $rotate_after_creation = true;
            }

            if (COLLAGE_LAYOUT === '2x4-2') {
                $widthNew = $collage_height * 0.2675;
                $heightNew = $widthNew * 1.5;

                $shortRatioY = 0.05333;
                $longRatioY = 0.54333;

                $img1RatioX = 0.03556;
                $img2RatioX = 0.235;
                $img3RatioX = 0.43611;
                $img4RatioX = 0.63667;
            } else {
                $widthNew = $collage_height * 0.32;
                $heightNew = $widthNew * 1.5;

                $shortRatioY = 0.01;
                $longRatioY = 0.51;

                $img1RatioX = 0.04194;
                $img2RatioX = 0.27621;
                $img3RatioX = 0.51048;
                $img4RatioX = 0.74475;
            }

            $pictureOptions = [
                [$collage_width * $img1RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img2RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img3RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img4RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img1RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img2RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img3RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img4RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
            ];

            for ($i = 0; $i < 4; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i + 4]);
            }
            $dashedline_color = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            drawDashedLine($my_collage, $collage_width * 0.03, $collage_height / 2, $collage_width * 0.97, $collage_height / 2, $dashedline_color);
            break;
        case '2x3':
            if ($landscape) {
                $rotate_after_creation = true;
            }

            $widthNew = $collage_height * 0.32;
            $heightNew = $widthNew * 1.5;

            $shortRatioY = 0.01;
            $longRatioY = 0.51;

            $img1RatioX = 0.04194;
            $img2RatioX = 0.27621;
            $img3RatioX = 0.51048;

            $pictureOptions = [
                [$collage_width * $img1RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img2RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img3RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img1RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img2RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                [$collage_width * $img3RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
            ];

            for ($i = 0; $i < 3; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i + 3]);
            }
            $dashedline_color = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            drawDashedLine($my_collage, $collage_width * 0.03, $collage_height / 2, $collage_width * 0.97, $collage_height / 2, $dashedline_color);
            break;
        case 'collage.json':
            $collageConfigFilePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'collage.json';
            if (!file_exists($collageConfigFilePath)) {
                return false;
            }
            $collageConfig = json_decode(file_get_contents($collageConfigFilePath), true);
            if (!is_array($collageConfig) || count($collageConfig) != COLLAGE_LIMIT) {
                return false;
            }
            // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
            $pictureOptions = [];
            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                $imgConfig = $collageConfig[$i];
                if (!is_array($imgConfig) || count($imgConfig) !== 5) {
                    return false;
                }
                $singlePictureOptions = [];
                for ($j = 0; $j < 5; $j++) {
                    $value = str_replace(array('x', 'y'), array($collage_width, $collage_height), $imgConfig[$j]);
                    $singlePictureOptions[] = doMath($value);
                }
                $pictureOptions[] = $singlePictureOptions;
            }

            for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
                addPicture($my_collage, $editImages[$i], $pictureOptions[$i]);
            }
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

    for ($i = 0; $i < COLLAGE_LIMIT; $i++) {
        if (!COLLAGE_PLACEHOLDER || (COLLAGE_PLACEHOLDER && COLLAGE_PLACEHOLDER_POSITION != $i)) {
            unlink($editImages[$i]);
        }
    }

    return true;
}

function doMath($expression): int {
    $o = 0;
    // eval is evil. To mitigate any attacks the allowed characters are limited to numbers and math symbols
    eval('$o = ' . preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $expression) . ';');
    return $o;
}

function drawDashedLine($my_collage, $x1, $y1, $x2, $y2, $dashedline_color) {
    settype($x1, 'integer');
    settype($x2, 'integer');
    settype($y1, 'integer');
    settype($y2, 'integer');

    $dashed_style = [
        $dashedline_color,
        $dashedline_color,
        $dashedline_color,
        $dashedline_color,
        IMG_COLOR_TRANSPARENT,
        IMG_COLOR_TRANSPARENT,
        IMG_COLOR_TRANSPARENT,
        IMG_COLOR_TRANSPARENT,
    ];
    imagesetstyle($my_collage, $dashed_style);
    imageline($my_collage, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
}

function addPicture($my_collage, $filename, $pictureOptions) {
    $dX = intval($pictureOptions[0]);
    $dY = intval($pictureOptions[1]);
    $width = intval($pictureOptions[2]);
    $height = intval($pictureOptions[3]);
    $degrees = intval($pictureOptions[4]);

    $tempSubImage = imagecreatefromjpeg($filename);
    if (abs($degrees) == 90) {
        $tempSubImage = resizeCropImage($height, $width, $tempSubImage);
    } else {
        $tempSubImage = resizeCropImage($width, $height, $tempSubImage);
    }

    if (COLLAGE_TAKE_FRAME === 'always' && testFile(COLLAGE_FRAME)) {
        $tempSubImage = applyFrame($tempSubImage, COLLAGE_FRAME);
    }

    if ($degrees != 0) {
        $tempSubImage = rotateResizeImage($tempSubImage, $degrees, COLLAGE_BACKGROUND_COLOR);
        if (abs($degrees) != 90) {
            $width = intval(imagesx($tempSubImage));
            $height = intval(imagesy($tempSubImage));
        }
    }

    imagecopy($my_collage, $tempSubImage, $dX, $dY, 0, 0, $width, $height);
    imagedestroy($tempSubImage);
}
