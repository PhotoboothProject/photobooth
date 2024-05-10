<?php

namespace Photobooth;

use Photobooth\Dto\CollageConfig;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Factory\CollageConfigFactory;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

class Collage
{
    public static function createCollage(array $config, array $srcImagePaths, string $destImagePath, string $filter = 'plain', CollageConfig $c = null): bool
    {
        if ($c === null) {
            $c = CollageConfigFactory::fromConfig($config);
        }
        $editImages = [];
        $landscape = true;
        $rotate_after_creation = false;
        $image_filter = false;
        if (!empty($filter) && $filter !== 'plain') {
            $image_filter = $filter;
        }

        if ($c->collageBackgroundColor !== null) {
            // colors for background and while rotating jpeg images
            $colorComponents = sscanf($c->collageBackgroundColor, '#%02x%02x%02x');
            if ($colorComponents !== null) {
                list($bg_r, $bg_g, $bg_b) = $colorComponents;
            } else {
                throw new \Exception('Collage background color: sscanf returned null!');
            }
        }

        $bg_color_hex = hexdec(substr($c->collageBackgroundColor, 1));
        if (!is_int($bg_color_hex)) {
            throw new \Exception('Cannot convert the hexadecimal collage background color to its decimal equivalent!');
        }

        // dashedline color on 2x4 collage layouts
        if ($c->collageDashedLineColor !== null) {
            $dashedColorComponents = sscanf($c->collageDashedLineColor, '#%02x%02x%02x');
            if ($dashedColorComponents !== null) {
                list($dashed_r, $dashed_g, $dashed_b) = $dashedColorComponents;
            } else {
                throw new \Exception('Collage dashed line color: sscanf returned null!');
            }
        }

        if (!is_array($srcImagePaths)) {
            throw new \Exception('Source image paths are not an array.');
        }

        // validate that there is the correct amount of images
        if (($c->collagePlaceholder && count($srcImagePaths) !== $c->collageLimit - 1) || (!$c->collagePlaceholder && count($srcImagePaths) !== $c->collageLimit)) {
            throw new \Exception('Invalid number of images.');
        }

        //Use offset to reflect image file numbering
        $placeholderOffset = 0;
        for ($i = 0; $i < $c->collageLimit; $i++) {
            if ($c->collagePlaceholder && $c->collagePlaceholderPosition == $i) {
                $editImages[] = $c->collagePlaceholderPath;
                $placeholderOffset = 1;
            } else {
                if (!file_exists($srcImagePaths[$i - $placeholderOffset])) {
                    throw new \Exception('The file ' . $srcImagePaths[$i] . ' does not exist.');
                }
                $singleimage = substr($srcImagePaths[$i - $placeholderOffset], 0, -4);
                $editfilename = $singleimage . '-edit.jpg';
                if (!copy($srcImagePaths[$i - $placeholderOffset], $editfilename)) {
                    throw new \Exception('Failed to copy image for editing.');
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
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Failed to create image resource.');
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
                ImageUtility::applyFilter(ImageFilterEnum::tryFrom($image_filter), $imageResource);
                $imageHandler->imageModified = true;
            }

            if ($c->pictureRotation !== '0') {
                $imageHandler->resizeRotation = $c->pictureRotation;
                $imageHandler->resizeBgColor = $c->collageBackgroundColor;
                $imageResource = $imageHandler->rotateResizeImage($imageResource);
                if (!$imageResource instanceof \GdImage) {
                    throw new \Exception('Failed to rotate and resize image resource.');
                }
            }

            if ($c->picturePolaroidEffect === 'enabled') {
                $imageHandler->polaroidRotation = $c->picturePolaroidRotation;
                $imageResource = $imageHandler->effectPolaroid($imageResource);
            }

            $width = (int) imagesx($imageResource);
            $height = (int) imagesy($imageResource);

            if ($width > $height) {
                $landscape = true;
            } else {
                $landscape = false;
                $imageResource = imagerotate($imageResource, 90, $bg_color_hex);
                if (!$imageResource instanceof \GdImage) {
                    throw new \Exception('Failed to rotate image resource.');
                }
                $width = imagesx($imageResource);
                $height = imagesy($imageResource);
                $imageHandler->imageModified = true;
            }

            if ($imageHandler->imageModified) {
                $imageHandler->saveJpeg($imageResource, $editImages[$i]);
                $imageHandler->imageModified = false;
            }

            unset($imageResource);
        }
        //Create Collage based on 300dpi 4x6in - Scale collages with the height
        $collage_height = intval(4 * $c->collageResolution);
        $collage_width = intval($collage_height * 1.5);

        $my_collage = imagecreatetruecolor($collage_width, $collage_height);

        if (!$my_collage instanceof \GdImage) {
            throw new \Exception('Failed to create collage resource.');
        }

        if (is_array(@getimagesize($c->collageBackground))) {
            $backgroundImage = $imageHandler->createFromImage($c->collageBackground);
            $imageHandler->resizeMaxWidth = $collage_width;
            $imageHandler->resizeMaxHeight = $collage_height;
            if (!$backgroundImage instanceof \GdImage) {
                throw new \Exception('Failed to create collage background image resource.');
            }
            $backgroundImage = $imageHandler->resizeImage($backgroundImage);
            if (!$backgroundImage instanceof \GdImage) {
                throw new \Exception('Failed to resize collage background image resource.');
            }
            imagecopy($my_collage, $backgroundImage, 0, 0, 0, 0, $collage_width, $collage_height);
        } else {
            $background = imagecolorallocate($my_collage, (int) $bg_r, (int) $bg_g, (int) $bg_b);
            imagefill($my_collage, 0, 0, (int) $background);
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
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
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
                }

                break;
            case '1+3-2':
            case '3+1':
                //Specify Big/Small Height Ratios - values based on previos settings
                $heightRatioBig = 0.4978;
                $heightRatioSmall = 0.3052;

                if ($c->collageLayout === '1+3-2') {
                    // Vertical Positions for big and small images
                    // Vertical distance between pictures in this case  = 0.5 x shortRatioY.
                    $shortRatioY = 0.08; // shortRatioY, vertical distance until the top left corner of the image
                    $longRatioY = 0.6178; // longRatio = heightRatioBig + shortRatioY + distance between the images.
                } else {
                    // Switch vertical Positions for big and small images
                    // Vertical distance between pictures in this case  = 0.5 x shortRatioY.
                    $shortRatioY = 0.4252; // shortRatioY,  = heightRatioSmall + shortRatioY + distance between the images.
                    $longRatioY = 0.08; // longRatio = vertical distance until the top left corner of the image
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
                }
                break;
            case '2x4':
                // colage size defined by image size
                if (!isset($width) || !isset($height)) {
                    throw new \Exception('Width or height not defined!');
                }
                $my_collage = imagecreatetruecolor($width, $height);
                if (!$my_collage instanceof \GdImage) {
                    throw new \Exception('Failed to create collage resource.');
                }
                $background = imagecolorallocate($my_collage, (int)$bg_r, (int)$bg_g, (int)$bg_b);

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
                    if (!$tempSubImage instanceof \GdImage) {
                        throw new \Exception('Failed to create tempSubImage resource.');
                    }

                    if ($c->collageTakeFrame === 'always') {
                        $tempSubImage = $imageHandler->applyFrame($tempSubImage);
                    }

                    $tempSubImage = imagerotate($tempSubImage, $degrees, $bg_color_hex);
                    if (!$tempSubImage instanceof \GdImage) {
                        throw new \Exception('Failed to rotate tempSubImage resource.');
                    }
                    $imageHandler->resizeMaxWidth = intval($height / 3.3);
                    $imageHandler->resizeMaxHeight = intval($width / 3.5);
                    $images_rotated[] = $imageHandler->resizeImage($tempSubImage);
                }

                if (!$images_rotated[0] instanceof \GdImage) {
                    throw new \Exception('Failed to resize tempSubImage resource.');
                }
                $new_width = (int)imagesx($images_rotated[0]);
                $new_height = (int)imagesy($images_rotated[0]);

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
                    if (!$images_rotated[$i] instanceof \GdImage) {
                        throw new \Exception('Failed to add copy rotated images to collage resource.');
                    }
                    imagecopy($my_collage, $images_rotated[$i], $position_top[0], $position_top[1], 0, 0, $new_width, $new_height);
                    imagecopy($my_collage, $images_rotated[$i], $position_bottom[0], $position_bottom[1], 0, 0, $new_width, $new_height);
                }

                imagescale($my_collage, $width, $height);
                $imageHandler->dashedLineColor = (string)imagecolorallocate($my_collage, (int)$dashed_r, (int)$dashed_g, (int)$dashed_b);
                $imageHandler->dashedLineStartX = 50;
                $imageHandler->dashedLineStartY = $height / 2;
                $imageHandler->dashedLineEndX = $width - 50;
                $imageHandler->dashedLineEndY = $height / 2;
                $imageHandler->drawDashedLine($my_collage);

                break;
            case '2x4-2':
            case '2x4-3':
            case '2x4-4':
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
                } elseif ($c->collageLayout === '2x4-3') {
                    $widthNew = $collage_height * 0.32;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.01;
                    $longRatioY = 0.51;

                    $img1RatioX = 0.04194;
                    $img2RatioX = 0.27621;
                    $img3RatioX = 0.51048;
                    $img4RatioX = 0.74475;
                } else {
                    $widthNew = $collage_height * 0.30;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.025;
                    $longRatioY = 0.525;

                    $img1RatioX = 0.02531;
                    $img2RatioX = 0.24080;
                    $img3RatioX = 0.45630;
                    $img4RatioX = 0.67178;
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
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int) $pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int)$pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);

                    $imageHandler->setAddPictureOptions(
                        (int)$pictureOptions[$i + 4][0],
                        (int)$pictureOptions[$i + 4][1],
                        (int)$pictureOptions[$i + 4][2],
                        (int)$pictureOptions[$i + 4][3],
                        (int)$pictureOptions[$i + 4][4]
                    );
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
                }
                $imageHandler->dashedLineColor = (string)imagecolorallocate($my_collage, (int)$dashed_r, (int)$dashed_g, (int)$dashed_b);
                $imageHandler->dashedLineStartX = intval($collage_width * 0.03);
                $imageHandler->dashedLineStartY = intval($collage_height / 2);
                $imageHandler->dashedLineEndX = intval($collage_width * 0.97);
                $imageHandler->dashedLineEndY = intval($collage_height / 2);
                $imageHandler->drawDashedLine($my_collage);

                break;
            case '2x3':
            case '2x3-2':
                if ($landscape) {
                    $rotate_after_creation = true;
                }

                $widthNew = intval($collage_height * 0.32);
                $heightNew = intval($widthNew * 1.5);

                $shortRatioY = 0.01;
                $longRatioY = 0.51;

                $img1RatioX = 0.04194;
                if ($c->collageLayout === '2x3') {
                    $img2RatioX = 0.27621;
                    $img3RatioX = 0.51048;
                } else {
                    $img2RatioX = 0.28597;
                    $img3RatioX = 0.53;
                }

                $pictureOptions = [
                    [$collage_width * $img1RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                    [$collage_width * $img2RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                    [$collage_width * $img3RatioX, $collage_height * $shortRatioY, $widthNew, $heightNew, 90],
                    [$collage_width * $img1RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                    [$collage_width * $img2RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                    [$collage_width * $img3RatioX, $collage_height * $longRatioY, $widthNew, $heightNew, 90],
                ];

                if ($c->collageLayout === '2x3-2') {
                    $centerX = $collage_width * 0.5;
                    $centerY = $collage_height * 0.5;
                    $scaleFactor = 0.99;

                    $pictureOptions = array_map(function ($image) use ($centerX, $centerY, $scaleFactor) {
                        $x_top_left = $image[0];
                        $y_top_left = $image[1];
                        $image_width = $image[2];
                        $image_height = $image[3];

                        // Calculate the center of the current image
                        $imageCenterX = $x_top_left + $image_width / 2;
                        $imageCenterY = $y_top_left + $image_height / 2;

                        // Calculate the vector from the group center to the image center
                        $vectorX = $imageCenterX - $centerX;
                        $vectorY = $imageCenterY - $centerY;

                        // Scale the vector by the scale factor
                        $vectorX *= $scaleFactor;
                        $vectorY *= $scaleFactor;

                        // Calculate the new center of the image
                        $newImageCenterX = $centerX + $vectorX;
                        $newImageCenterY = $centerY + $vectorY;

                        // Calculate the new top left position of the image
                        $new_x_top_left = $newImageCenterX - $image_width * $scaleFactor / 2;
                        $new_y_top_left = $newImageCenterY - $image_height * $scaleFactor / 2;

                        // Return the new position and size of the image
                        return [
                            $new_x_top_left,
                            $new_y_top_left,
                            $image_width * $scaleFactor,
                            $image_height * $scaleFactor,
                            90
                        ];
                    }, $pictureOptions);
                }

                for ($i = 0; $i < 3; $i++) {
                    $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions((int)$pictureOptions[$i][0], (int)$pictureOptions[$i][1], (int)$pictureOptions[$i][2], (int)$pictureOptions[$i][3], (int) $pictureOptions[$i][4]);
                    $imageHandler->addPicture($tmpImg, $my_collage);

                    $imageHandler->setAddPictureOptions(
                        (int)$pictureOptions[$i + 3][0],
                        (int)$pictureOptions[$i + 3][1],
                        (int) $pictureOptions[$i + 3][2],
                        (int)$pictureOptions[$i + 3][3],
                        (int) $pictureOptions[$i + 3][4]
                    );
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
                }
                if ($c->collageLayout === '2x3') {
                    $imageHandler->dashedLineColor = (string)imagecolorallocate($my_collage, (int)$dashed_r, (int)$dashed_g, (int)$dashed_b);
                    $imageHandler->dashedLineStartX = intval($collage_width * 0.03);
                    $imageHandler->dashedLineStartY = intval($collage_height / 2);
                    $imageHandler->dashedLineEndX = intval($collage_width * 0.97);
                    $imageHandler->dashedLineEndY = intval($collage_height / 2);
                    $imageHandler->drawDashedLine($my_collage);
                }
                break;
            default:
                $collageConfigFilePath = PathUtility::getAbsolutePath('private' . DIRECTORY_SEPARATOR . $c->collageLayout);
                $collageJson = json_decode((string)file_get_contents($collageConfigFilePath), true);

                if (is_array($collageJson)) {
                    if (array_key_exists('layout', $collageJson)) {
                        $layoutConfigArray = $collageJson['layout'];

                        if (array_key_exists('width', $collageJson) && array_key_exists('height', $collageJson)) {
                            $collage_width = $collageJson['width'];
                            $collage_height = $collageJson['height'];
                            $my_collage = imagecreatetruecolor($collage_width, $collage_height);
                            if (!$my_collage instanceof \GdImage) {
                                throw new \Exception('Failed to create collage resource.');
                            }
                            $background = imagecolorallocate($my_collage, (int)$bg_r, (int)$bg_g, (int)$bg_b);
                            imagefill($my_collage, 0, 0, (int)$background);
                        }

                        if (array_key_exists('background', $collageJson)) {
                            if ($collageJson['background']) {
                                $imageHandler->resizeMaxWidth = (int)$collage_width;
                                $imageHandler->resizeMaxHeight = (int)$collage_height;
                                $backgroundImage = $imageHandler->createFromImage($collageJson['background']);
                                if (!$backgroundImage instanceof \GdImage) {
                                    throw new \Exception('Failed to create collage background resource.');
                                }
                                $backgroundImage = $imageHandler->resizeImage($backgroundImage);
                                if (!$backgroundImage instanceof \GdImage) {
                                    throw new \Exception('Failed to resize collage background resource.');
                                }
                                imagecopy($my_collage, $backgroundImage, 0, 0, 0, 0, $collage_width, $collage_height);
                                $imageHandler->addPictureBgImage = $collageJson['background'];
                            }
                        }

                        if (array_key_exists('portrait', $collageJson) && $collage_width > $collage_height) {
                            if ($collageJson['portrait']) {
                                $tmp = (int) $collage_width;
                                $collage_width = (int) $collage_height;
                                $collage_height = $tmp;
                                $my_collage = imagerotate($my_collage, -90, $bg_color_hex);
                                if (!$my_collage instanceof \GdImage) {
                                    throw new \Exception('Failed to rotate collage resource.');
                                }
                            }
                        }

                        if (array_key_exists('rotate_after_creation', $collageJson)) {
                            $rotate_after_creation = $collageJson['rotate_after_creation'];
                        }

                        if (array_key_exists('apply_frame', $collageJson) && array_key_exists('frame', $collageJson)) {
                            if ($collageJson['apply_frame'] === 'once' || $collageJson['apply_frame'] === 'always') {
                                $c->collageTakeFrame = $collageJson['apply_frame'];
                            }
                            $c->collageFrame = $collageJson['frame'];
                            $imageHandler->framePath = $c->collageFrame;
                            $imageHandler->addPictureApplyFrame = $c->collageTakeFrame === 'always' ? true : false;
                        }

                        $c->textOnCollageEnabled = isset($collageJson['text_custom_style']) ? ($collageJson['text_custom_style'] ? 'enabled' : 'disabled') : $c->textOnCollageEnabled;
                        if ($c->textOnCollageEnabled) {
                            $c->textOnCollageFontSize = isset($collageJson['text_font_size']) ? $collageJson['text_font_size'] : $c->textOnCollageFontSize;
                            $c->textOnCollageRotation = isset($collageJson['text_rotation']) ? $collageJson['text_rotation'] : $c->textOnCollageRotation;
                            $c->textOnCollageLocationX = isset($collageJson['text_locationx']) ? $collageJson['text_locationx'] : $c->textOnCollageLocationX;
                            $c->textOnCollageLocationY = isset($collageJson['text_locationy']) ? $collageJson['text_locationy'] : $c->textOnCollageLocationY;
                            $c->textOnCollageFontColor = isset($collageJson['text_font_color']) ? $collageJson['text_font_color'] : $c->textOnCollageFontColor;
                            $c->textOnCollageFont = isset($collageJson['text_font']) ? $collageJson['text_font'] : $c->textOnCollageFont;
                            $c->textOnCollageLine1 = isset($collageJson['text_line1']) ? $collageJson['text_line1'] : $c->textOnCollageLine1;
                            $c->textOnCollageLine2 = isset($collageJson['text_line2']) ? $collageJson['text_line2'] : $c->textOnCollageLine2;
                            $c->textOnCollageLine3 = isset($collageJson['text_line3']) ? $collageJson['text_line3'] : $c->textOnCollageLine3;
                            $c->textOnCollageLinespace = isset($collageJson['text_linespace']) ? $collageJson['text_linespace'] : $c->textOnCollageLinespace;
                        }
                    } else {
                        $layoutConfigArray = $collageJson;
                    }
                } else {
                    return false;
                }

                $pictureOptions = [];
                foreach ($layoutConfigArray as $layoutConfig) {
                    if (!is_array($layoutConfig) || count($layoutConfig) !== 5) {
                        return false;
                    }

                    $singlePictureOptions = [];
                    for ($j = 0; $j < 5; $j++) {
                        $value = str_replace(['x', 'y'], [$collage_width, $collage_height], $layoutConfig[$j]);
                        $singlePictureOptions[] = self::doMath($value);
                    }
                    $pictureOptions[] = $singlePictureOptions;
                }

                foreach ($pictureOptions as $i => $singlePictureOptions) {
                    $tmpImg = $imageHandler->createFromImage($editImages[$i]);
                    if (!$tmpImg instanceof \GdImage) {
                        throw new \Exception('Failed to create tmp image resource.');
                    }
                    $imageHandler->setAddPictureOptions(
                        (int)$singlePictureOptions[0],
                        (int)$singlePictureOptions[1],
                        (int)$singlePictureOptions[2],
                        (int)$singlePictureOptions[3],
                        (int)$singlePictureOptions[4]
                    );
                    $imageHandler->addPicture($tmpImg, $my_collage);
                    unset($tmpImg);
                }
                break;
        }

        if ($c->collageTakeFrame === 'once') {
            $my_collage = $imageHandler->applyFrame($my_collage);
            if (!$my_collage instanceof \GdImage) {
                throw new \Exception('Failed to apply frame on collage resource.');
            }
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
            if (!$my_collage instanceof \GdImage) {
                throw new \Exception('Failed to apply text to collage resource.');
            }
        }

        // Rotate image if needed
        if ($rotate_after_creation) {
            $my_collage = imagerotate($my_collage, -90, $bg_color_hex);
            if (!$my_collage instanceof \GdImage) {
                throw new \Exception('Failed to rotate collage resource after creation.');
            }
        }

        // Transfer image to destImagePath with returns the image to core
        $imageHandler->saveJpeg($my_collage, $destImagePath);

        // Destroy the created collage in memory
        unset($my_collage);

        for ($i = 0; $i < $c->collageLimit; $i++) {
            if (($c->collagePlaceholder && $c->collagePlaceholderPosition != $i) || !$c->collagePlaceholder) {
                unlink($editImages[$i]);
            }
        }

        return true;
    }

    public static function doMath(string $expression): int
    {
        $o = 0;
        // eval is evil. To mitigate any attacks the allowed characters are limited to numbers and math symbols
        eval('$o = ' . preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $expression) . ';');
        return intval($o);
    }

    public static function getCollageFiles(array $collage, string $filename_tmp, string $file, array $srcImages): array
    {
        $collageBasename = substr($filename_tmp, 0, -4);
        $singleImageBase = substr($file, 0, -4);

        $collageSrcImagePaths = [];

        for ($i = 0; $i < $collage['limit']; $i++) {
            $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
            if ($collage['keep_single_images']) {
                $srcImages[] = $singleImageBase . '-' . $i . '.jpg';
            }
        }
        return [$collageSrcImagePaths, $srcImages];
    }
}
