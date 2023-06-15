<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/collageConfig.php';
require_once __DIR__ . '/filter.php';
require_once __DIR__ . '/image.php';

function createCollage($srcImagePaths, $destImagePath, $filter = 'plain', CollageConfig $c = null) {
    if (is_null($c)) {
        $c = new CollageConfig();
    }
    $editImages = [];
    $rotate_after_creation = false;
    $image_filter = false;
    if (!empty($filter) && $filter !== 'plain') {
        $image_filter = $filter;
    }

    // colors for background and while rotating jpeg images
    list($bg_r, $bg_g, $bg_b) = sscanf($c->collageBackgroundColor, '#%02x%02x%02x');
    $bg_color_hex = hexdec(substr($c->collageBackgroundColor, 1));

    // dashedline color on 2x4 collage layouts
    list($dashed_r, $dashed_g, $dashed_b) = sscanf($c->collageDashedLineColor, '#%02x%02x%02x');

    if (!is_array($srcImagePaths)) {
        throw new Exception('Source image paths are not an array.');
    }

    // validate that there is the correct amount of images
    if (($c->collagePlaceholder && count($srcImagePaths) !== $c->collageLimit - 1) || (!$c->collagePlaceholder && count($srcImagePaths) !== $c->collageLimit)) {
        throw new Exception('Invalid number of images.');
    }

    //Use offset to reflect image file numbering
    $placeholderOffset = 0;
    for ($i = 0; $i < $c->collageLimit; $i++) {
        if ($c->collagePlaceholder && $c->collagePlaceholderPosition == $i) {
            $editImages[] = $c->collagePlaceholderPath;
            $placeholderOffset = 1;
        } else {
            if (!file_exists($srcImagePaths[$i - $placeholderOffset])) {
                throw new Exception('The file ' . $srcImagePaths[$i] . ' does not exist.');
            }
            $singleimage = substr($srcImagePaths[$i - $placeholderOffset], 0, -4);
            $editfilename = $singleimage . '-edit.jpg';
            if (!copy($srcImagePaths[$i - $placeholderOffset], $editfilename)) {
                throw new Exception('Failed to copy image for editing.');
            }
            $editImages[] = $editfilename;
        }
    }

    $imageHandler = new Image();
    $imageHandler->jpegQuality = 100;
    $imageHandler->framePath = $c->collageFrame;
    $imageHandler->frameExtend = false;

    for ($i = 0; $i < $c->collageLimit; $i++) {
        $imageResource = $imageHandler->createFromImage($editImages[$i]);
        // Only jpg/jpeg are supported
        if (!$imageResource) {
            throw new Exception('Failed to create image resource.');
        }

        if ($c->pictureFlip !== 'off') {
            if ($c->pictureFlip === 'horizontal') {
                imageflip($imageResource, IMG_FLIP_HORIZONTAL);
            } elseif ($c->pictureFlip === 'vertical') {
                imageflip($imageResource, IMG_FLIP_VERTICAL);
            } elseif ($c->pictureFlip === 'both') {
                imageflip($imageResource, IMG_FLIP_BOTH);
            }
            $imageHandler->imageModified = true;
        }

        // apply filter
        if ($image_filter) {
            applyFilter($image_filter, $imageResource);
            $imageHandler->imageModified = true;
        }

        if ($c->pictureRotation !== '0') {
            $imageHandler->resizeRotation = $c->pictureRotation;
            $imageHandler->resizeBgColor = $c->collageBackgroundColor;
            $imageResource = $imageHandler->rotateResizeImage($imageResource);
        }

        if ($c->picturePolaroidEffect === 'enabled') {
            $imageHandler->polaroidRotation = $c->picturePolaroidRotation;
            $imageResource = $imageHandler->effectPolaroid($imageResource);
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
            $imageHandler->imageModified = true;
        }

        if ($imageHandler->imageModified) {
            $imageHandler->saveJpeg($imageResource, $editImages[$i]);
            $imageHandler->imageModified = false;
        }

        imagedestroy($imageResource);
    }
    //Create Collage based on 300dpi 4x6in - Scale collages with the height
    $collage_height = 4 * $c->collageResolution;
    $collage_width = $collage_height * 1.5;

    $my_collage = imagecreatetruecolor($collage_width, $collage_height);
    if (!empty($c->collageBackground)) {
        $backgroundImage = $imageHandler->createFromImage($c->collageBackground);
        $imageHandler->resizeMaxWidth = $collage_width;
        $imageHandler->resizeMaxHeight = $collage_height;
        $backgroundImage = $imageHandler->resizeImage($backgroundImage);
        imagecopy($my_collage, $backgroundImage, 0, 0, 0, 0, $collage_width, $collage_height);
    } else {
        $background = imagecolorallocate($my_collage, $bg_r, $bg_g, $bg_b);
        imagefill($my_collage, 0, 0, $background);
    }

    if ($landscape == false) {
        $rotate_after_creation = true;
    }

    $imageHandler->addPictureApplyFrame = $c->collageTakeFrame === 'always' ? true : false;
    $imageHandler->addPictureBgImage = $c->collageBackground;
    $imageHandler->addPictureBgColor = $c->collageBackgroundColor;

    switch ($c->collageLayout) {
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

            for ($i = 0; $i < $c->collageLimit; $i++) {
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
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

            for ($i = 0; $i < $c->collageLimit; $i++) {
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
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

            for ($i = 0; $i < $c->collageLimit; $i++) {
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
            }

            break;
        case '1+3-2':
        case '3+1':
            //Specify Big/Small Height Ratios - values based on previos settings
            $heightRatioBig = 0.4978;
            $heightRatioSmall = 0.3052;

            if ($c->collageLayout === '1+3-2') {
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

            for ($i = 0; $i < $c->collageLimit; $i++) {
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
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
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
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
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
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

                $tempSubImage = $imageHandler->createFromImage($editImages[$i]);

                if ($c->collageTakeFrame === 'always') {
                    $tempSubImage = $imageHandler->applyFrame($tempSubImage);
                }

                $tempSubImage = imagerotate($tempSubImage, $degrees, $bg_color_hex);
                $imageHandler->resizeMaxWidth = $height / 3.3;
                $imageHandler->resizeMaxHeight = $width / 3.5;
                $images_rotated[] = $imageHandler->resizeImage($tempSubImage);
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
            $imageHandler->dashedLineColor = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            $imageHandler->dashedLineStartX = 50;
            $imageHandler->dashedLineStartY = $height / 2;
            $imageHandler->dashedLineEndX = $width - 50;
            $imageHandler->dashedLineEndY = $height / 2;
            $imageHandler->drawDashedLine($my_collage);

            break;
        case '2x4-2':
        case '2x4-3':
            if ($landscape) {
                $rotate_after_creation = true;
            }

            if ($c->collageLayout === '2x4-2') {
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
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);

                $imageHandler->setAddPictureOptions(
                    $pictureOptions[$i + 4][0],
                    $pictureOptions[$i + 4][1],
                    $pictureOptions[$i + 4][2],
                    $pictureOptions[$i + 4][3],
                    $pictureOptions[$i + 4][4]
                );
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
            }
            $imageHandler->dashedLineColor = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            $imageHandler->dashedLineStartX = $collage_width * 0.03;
            $imageHandler->dashedLineStartY = $collage_height / 2;
            $imageHandler->dashedLineEndX = $collage_width * 0.97;
            $imageHandler->dashedLineEndY = $collage_height / 2;
            $imageHandler->drawDashedLine($my_collage);

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
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);

                $imageHandler->setAddPictureOptions(
                    $pictureOptions[$i + 3][0],
                    $pictureOptions[$i + 3][1],
                    $pictureOptions[$i + 3][2],
                    $pictureOptions[$i + 3][3],
                    $pictureOptions[$i + 3][4]
                );
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
            }
            $imageHandler->dashedLineColor = imagecolorallocate($my_collage, $dashed_r, $dashed_g, $dashed_b);
            $imageHandler->dashedLineStartX = $collage_width * 0.03;
            $imageHandler->dashedLineStartY = $collage_height / 2;
            $imageHandler->dashedLineEndX = $collage_width * 0.97;
            $imageHandler->dashedLineEndY = $collage_height / 2;
            $imageHandler->drawDashedLine($my_collage);
            break;
        default:
            $collageConfigFilePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $c->collageLayout;
            if (!file_exists($collageConfigFilePath)) {
                $my_collage = imagecreatetruecolor($width, $height);
            }
            $layoutConfig = json_decode(file_get_contents($collageConfigFilePath), true);
            if (!is_array($layoutConfig) || count($layoutConfig) != $c->collageLimit) {
                return false;
            }
            // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
            $pictureOptions = [];
            for ($i = 0; $i < $c->collageLimit; $i++) {
                $imgConfig = $layoutConfig[$i];
                if (!is_array($imgConfig) || count($imgConfig) !== 5) {
                    return false;
                }
                $singlePictureOptions = [];
                for ($j = 0; $j < 5; $j++) {
                    $value = str_replace(['x', 'y'], [$collage_width, $collage_height], $imgConfig[$j]);
                    $singlePictureOptions[] = doMath($value);
                }
                $pictureOptions[] = $singlePictureOptions;
            }

            for ($i = 0; $i < $c->collageLimit; $i++) {
                $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                $imageHandler->setAddPictureOptions($pictureOptions[$i][0], $pictureOptions[$i][1], $pictureOptions[$i][2], $pictureOptions[$i][3], $pictureOptions[$i][4]);
                $imageHandler->addPicture($tmpImg, $my_collage);
                imagedestroy($tmpImg);
            }
            break;
    }

    if ($c->collageTakeFrame === 'once') {
        $my_collage = $imageHandler->applyFrame($my_collage, $c->collageFrame, true);
    }

    if ($c->textOnCollageEnabled === 'enabled') {
        $imageHandler->fontSize = $c->textOnCollageFontSize;
        $imageHandler->fontRotation = $c->textOnCollageRotation;
        $imageHandler->fontLocationX = $c->textOnCollageLocationX;
        $imageHandler->fontLocationY = $c->textOnCollageLocationY;
        $imageHandler->fontColor = $c->textOnCollageFontColor;
        $imageHandler->fontPath = $c->textOnCollageFont;
        $imageHandler->textLine1 = $c->textOnCollageLine1;
        $imageHandler->textLine2 = $c->textOnCollageLine2;
        $imageHandler->textLine3 = $c->textOnCollageLine3;
        $imageHandler->textLineSpacing = $c->textOnCollageLinespace;
        $my_collage = $imageHandler->applyText($my_collage);
    }

    // Rotate image if needed
    if ($rotate_after_creation) {
        $my_collage = imagerotate($my_collage, -90, $bg_color_hex);
    }

    // Transfer image to destImagePath with returns the image to core
    $imageHandler->saveJpeg($my_collage, $destImagePath);

    // Destroy the created collage in memory
    imagedestroy($my_collage);

    for ($i = 0; $i < $c->collageLimit; $i++) {
        if (!$c->collagePlaceholder || ($c->collagePlaceholder && $c->collagePlaceholderPosition != $i)) {
            unlink($editImages[$i]);
        }
    }

    return true;
}

function doMath($expression): int {
    $o = 0;
    // eval is evil. To mitigate any attacks the allowed characters are limited to numbers and math symbols
    eval('$o = ' . preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $expression) . ';');
    return intval($o);
}
