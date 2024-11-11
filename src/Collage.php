<?php

namespace Photobooth;

use Photobooth\Dto\CollageConfig;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Factory\CollageConfigFactory;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;

class Collage
{
    public static int $collageHeight = 0;
    public static int $collageWidth = 0;
    public static bool $drawDashedLine = false;
    public static string $pictureOrientation = '';
    public static bool $rotateAfterCreation = false;

    public static function reset(): void
    {
        self::$collageHeight = 0;
        self::$collageWidth = 0;
        self::$drawDashedLine = false;
        self::$pictureOrientation = '';
        self::$rotateAfterCreation = false;
    }

    public static function getPictureOptions(string $collageLayout): array
    {
        switch ($collageLayout) {
            case '2+2-1':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

                // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
                $pictureOptions = [
                    [0, 0, self::$collageWidth / 2, self::$collageHeight / 2, $pictureRotation],
                    [self::$collageWidth / 2, 0, self::$collageWidth / 2, self::$collageHeight / 2, $pictureRotation],
                    [0, self::$collageHeight / 2, self::$collageWidth / 2, self::$collageHeight / 2, $pictureRotation],
                    [self::$collageWidth / 2, self::$collageHeight / 2, self::$collageWidth / 2, self::$collageHeight / 2, $pictureRotation],
                ];

                break;
            case '2+2-2':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

                $heightRatio = 0.4; // 0.4 = image height ratio. Should be set below 0.5 (as we have 2 pictures). Please adapt the short/long ratio as well
                $shortRatio = 0.08; // shortRatio, distance until the top left corner of the first image
                $longRatio = 0.52; // longRatio = image height ratio + shortRatio + distance between the images. In this case: 0.4 + 0.08 + 0.04 = 0.52.
                // Distance between pictures = 2x (0.5 -heightRatio -shortRatio)
                // Please note: We get a correct picture, if this formula adds up to exactly 1:  2x heightRatio + 2x shortRatio + distance between pictures

                $heightp = self::$collageHeight * $heightRatio;
                $widthp = $heightp * 1.5;

                //If there is a need for Text/Frame, we could specify an additional horizontal offset. E.g. widthp * 0.08
                $horizontalOffset = $widthp * 0;

                // Set Picture Options (Start X, Start Y, Width, Height, Rotation Angle) for each picture
                $pictureOptions = [
                    [self::$collageWidth * $shortRatio + $horizontalOffset, self::$collageHeight * $shortRatio, $widthp, $heightp, $pictureRotation],
                    [self::$collageWidth * $longRatio + $horizontalOffset, self::$collageHeight * $shortRatio, $widthp, $heightp, $pictureRotation],
                    [self::$collageWidth * $shortRatio + $horizontalOffset, self::$collageHeight * $longRatio, $widthp, $heightp, $pictureRotation],
                    [self::$collageWidth * $longRatio + $horizontalOffset, self::$collageHeight * $longRatio, $widthp, $heightp, $pictureRotation],
                ];

                break;
            case '1+3-1':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

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

                $heightNewBig = self::$collageHeight * $heightRatioBig;
                $widthNewBig = $heightNewBig * 1.5;

                $heightNewSmall = self::$collageHeight * $heightRatioSmall;
                $widthNewSmall = $heightNewSmall * 1.5;

                $pictureOptions = [
                    [self::$collageWidth * $ratioBigPictureX, self::$collageHeight * $shortRatioY, $widthNewBig, $heightNewBig, $pictureRotation],
                    [self::$collageWidth * $shortRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                    [self::$collageWidth * $mediumRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                    [self::$collageWidth * $longRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                ];

                break;
            case '1+3-2':
            case '3+1':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

                //Specify Big/Small Height Ratios - values based on previos settings
                $heightRatioBig = 0.4978;
                $heightRatioSmall = 0.3052;

                if ($collageLayout === '1+3-2') {
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

                $heightNewBig = self::$collageHeight * $heightRatioBig;
                $widthNewBig = $heightNewBig * 1.5;

                $heightNewSmall = self::$collageHeight * $heightRatioSmall;
                $widthNewSmall = $heightNewSmall * 1.5;

                $pictureOptions = [
                    [self::$collageWidth * $ratioBigPictureX, self::$collageHeight * $shortRatioY, $widthNewBig, $heightNewBig, $pictureRotation],
                    [self::$collageWidth * $shortRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                    [self::$collageWidth * $mediumRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                    [self::$collageWidth * $longRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                ];

                break;
            case '1+2':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

                //Specify Big/Small Height Ratios - values based on previos settings
                $heightRatioBig = 0.55546; // based on previous value / height
                $heightRatioSmall = 0.40812;

                $shortRatioY = 0.055;
                $longRatioX = 0.555;
                $longRatioY = 0.5368;

                $heightNewBig = self::$collageHeight * $heightRatioBig;
                $widthNewBig = $heightNewBig * 1.5;

                $heightNewSmall = self::$collageHeight * $heightRatioSmall;
                $widthNewSmall = $heightNewSmall * 1.5;

                $pictureOptions = [
                    [self::$collageHeight * $shortRatioY, self::$collageHeight * $shortRatioY, $pictureRotation === 90 ? $heightNewBig : $widthNewBig, $pictureRotation === 90 ? $widthNewBig : $heightNewBig, 10 + $pictureRotation],
                    [self::$collageWidth * $longRatioX, self::$collageHeight * $shortRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                    [self::$collageWidth * $longRatioX, self::$collageHeight * $longRatioY, $widthNewSmall, $heightNewSmall, $pictureRotation],
                ];

                break;
            case '2+1':
                self::$rotateAfterCreation = self::$pictureOrientation === 'portrait';
                $pictureRotation = self::$pictureOrientation === 'portrait' ? 90 : 0;

                $heightRatio = 0.375;

                // Horizontal Ratio
                $shortRatioY = 0.1;
                $longRatioY = 0.525;

                // Vertical Ratio
                $shortRatioX = 0.1;
                $longRatioX = 0.525;

                $heightNew = self::$collageHeight * $heightRatio;
                $widthNew = $heightNew * 1.5;

                $pictureOptions = [
                    [self::$collageWidth * $shortRatioY, self::$collageHeight * $shortRatioX, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $longRatioY, self::$collageHeight * $shortRatioX, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $shortRatioY, self::$collageHeight * $longRatioX, $widthNew, $heightNew, $pictureRotation],
                ];

                break;
            case '2x4-1':
            case '2x4-2':
            case '2x4-3':
            case '2x4-4':
                self::$rotateAfterCreation = self::$pictureOrientation === 'landscape';
                self::$drawDashedLine = $collageLayout != '2x4-1';
                $pictureRotation = self::$pictureOrientation === 'landscape' ? 90 : 0;

                if ($collageLayout === '2x4-1') {
                    $widthNew = self::$collageHeight * 0.2857;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.035129;
                    $longRatioY = 0.532787;

                    $img1RatioX = 0.046875;
                    $img2RatioX = 0.284375;
                    $img3RatioX = 0.521875;
                    $img4RatioX = 0.764844;
                } elseif ($collageLayout === '2x4-2') {
                    $widthNew = self::$collageHeight * 0.2675;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.05333;
                    $longRatioY = 0.54333;

                    $img1RatioX = 0.03556;
                    $img2RatioX = 0.235;
                    $img3RatioX = 0.43611;
                    $img4RatioX = 0.63667;
                } elseif ($collageLayout === '2x4-3') {
                    $widthNew = self::$collageHeight * 0.32;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.01;
                    $longRatioY = 0.51;

                    $img1RatioX = 0.04194;
                    $img2RatioX = 0.27621;
                    $img3RatioX = 0.51048;
                    $img4RatioX = 0.74475;
                } else {
                    $widthNew = self::$collageHeight * 0.30;
                    $heightNew = $widthNew * 1.5;

                    $shortRatioY = 0.025;
                    $longRatioY = 0.525;

                    $img1RatioX = 0.02531;
                    $img2RatioX = 0.24080;
                    $img3RatioX = 0.45630;
                    $img4RatioX = 0.67178;
                }

                $pictureOptions = [
                    [self::$collageWidth * $img1RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img2RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img3RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img4RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img1RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img2RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img3RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img4RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                ];

                break;
            case '2x3-1':
            case '2x3-2':
                self::$rotateAfterCreation = self::$pictureOrientation === 'landscape';
                self::$drawDashedLine = $collageLayout === '2x3-1';
                $pictureRotation = self::$pictureOrientation === 'landscape' ? 90 : 0;

                $widthNew = intval(self::$collageHeight * 0.32);
                $heightNew = intval($widthNew * 1.5);

                $shortRatioY = 0.01;
                $longRatioY = 0.51;

                $img1RatioX = 0.04194;
                if ($collageLayout === '2x3-1') {
                    $img2RatioX = 0.27621;
                    $img3RatioX = 0.51048;
                } else {
                    $img2RatioX = 0.28597;
                    $img3RatioX = 0.53;
                }

                $pictureOptions = [
                    [self::$collageWidth * $img1RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img2RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img3RatioX, self::$collageHeight * $shortRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img1RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img2RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                    [self::$collageWidth * $img3RatioX, self::$collageHeight * $longRatioY, $widthNew, $heightNew, $pictureRotation],
                ];

                if ($collageLayout === '2x3-2') {
                    $centerX = self::$collageWidth * 0.5;
                    $centerY = self::$collageHeight * 0.5;
                    $scaleFactor = 0.99;

                    $pictureOptions = array_map(function ($image) use ($centerX, $centerY, $scaleFactor, $pictureRotation) {
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
                            $pictureRotation
                        ];
                    }, $pictureOptions);
                }

                break;
            default:
                $pictureOptions = [];

                break;
        }

        return $pictureOptions;
    }

    public static function createCollage(array $config, array $srcImagePaths, string $destImagePath, ?ImageFilterEnum $filter = null, CollageConfig $c = null): bool
    {
        if ($c === null) {
            $c = CollageConfigFactory::fromConfig($config);
        }
        self::reset();
        $imageHandler = new Image();
        $imageHandler->jpegQuality = 100;
        $editImages = [];
        $collageConfigFilePath = PathUtility::getAbsolutePath('private/' . $c->collageLayout);

        if (file_exists($collageConfigFilePath)) {
            $collageJson = json_decode((string)file_get_contents($collageConfigFilePath), true);

            if (is_array($collageJson)) {
                if (isset($collageJson['layout']) && !empty($collageJson['layout'])) {
                    $layoutConfigArray = $collageJson['layout'];

                    if (isset($collageJson['background_color']) && !empty($collageJson['background_color'])) {
                        $c->collageBackgroundColor = $collageJson['background_color'];
                    }

                    if (isset($collageJson['background']) && !empty($collageJson['background'])) {
                        $c->collageBackground = $collageJson['background'];
                    }

                    if (isset($collageJson['width']) && isset($collageJson['height'])) {
                        self::$collageWidth = $collageJson['width'];
                        self::$collageHeight = $collageJson['height'];
                    }

                    if (isset($collageJson['apply_frame']) && in_array($collageJson['apply_frame'], ['once', 'always'])) {
                        $c->collageTakeFrame = $collageJson['apply_frame'];
                    }

                    if (isset($collageJson['frame']) && !empty($collageJson['frame'])) {
                        $c->collageFrame = $collageJson['frame'];
                    }

                    if (isset($collageJson['placeholder']) && $collageJson['placeholder']) {
                        $c->collagePlaceholder = $collageJson['placeholder'];
                        $c->collagePlaceholderPosition = (int) $collageJson['placeholderposition'] - 1;
                        $c->collagePlaceholderPath = $collageJson['placeholderpath'];
                    }

                    $c->textOnCollageEnabled = isset($collageJson['text_custom_style']) ? ($collageJson['text_custom_style'] ? 'enabled' : 'disabled') : $c->textOnCollageEnabled;
                    if ($c->textOnCollageEnabled === 'enabled') {
                        $c->textOnCollageFontSize = isset($collageJson['text_font_size']) ? $collageJson['text_font_size'] : $c->textOnCollageFontSize;
                        $c->textOnCollageRotation = isset($collageJson['text_rotation']) ? $collageJson['text_rotation'] : $c->textOnCollageRotation;
                        $c->textOnCollageLocationX = isset($collageJson['text_locationx']) ? $collageJson['text_locationx'] : $c->textOnCollageLocationX;
                        $c->textOnCollageLocationY = isset($collageJson['text_locationy']) ? $collageJson['text_locationy'] : $c->textOnCollageLocationY;
                        $c->textOnCollageFontColor = isset($collageJson['text_font_color']) ? $collageJson['text_font_color'] : $c->textOnCollageFontColor;
                        $c->textOnCollageFont = isset($collageJson['text_font']) ? $collageJson['text_font'] : $c->textOnCollageFont;
                        $c->textOnCollageLine1 = array_key_exists('text_line1', $collageJson) ? $collageJson['text_line1'] : $c->textOnCollageLine1;
                        $c->textOnCollageLine2 = array_key_exists('text_line2', $collageJson) ? $collageJson['text_line2'] : $c->textOnCollageLine2;
                        $c->textOnCollageLine3 = array_key_exists('text_line3', $collageJson) ? $collageJson['text_line3'] : $c->textOnCollageLine3;
                        $c->textOnCollageLinespace = isset($collageJson['text_linespace']) ? $collageJson['text_linespace'] : $c->textOnCollageLinespace;
                    }
                } else {
                    $layoutConfigArray = $collageJson;
                }
            } else {
                return false;
            }
        }

        if ($c->collageBackgroundColor !== null) {
            // colors for background and while rotating jpeg images
            $colorComponents = $imageHandler->getColorComponents($c->collageBackgroundColor);
            list($bg_r, $bg_g, $bg_b) = $colorComponents;
        }

        $bg_color_hex = hexdec(substr($c->collageBackgroundColor, 1));
        if (!is_int($bg_color_hex)) {
            throw new \Exception('Cannot convert the hexadecimal collage background color to its decimal equivalent!');
        }

        // dashedline color on 2x3 and 2x4 collage layouts
        if ($c->collageDashedLineColor !== null) {
            $dashedColorComponents = $imageHandler->getColorComponents($c->collageDashedLineColor);
            list($dashed_r, $dashed_g, $dashed_b) = $dashedColorComponents;
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
                $editImages[] = PathUtility::getAbsolutePath($c->collagePlaceholderPath);
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

        $imageHandler->framePath = $c->collageFrame;
        $imageHandler->frameExtend = false;

        for ($i = 0; $i < $c->collageLimit; $i++) {
            $imageResource = $imageHandler->createFromImage($editImages[$i]);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Failed to create image resource.');
            }

            if ($c->pictureFlip !== 'off') {
                if ($c->pictureFlip === 'flip-horizontal') {
                    imageflip($imageResource, IMG_FLIP_HORIZONTAL);
                } elseif ($c->pictureFlip === 'flip-vertical') {
                    imageflip($imageResource, IMG_FLIP_VERTICAL);
                } elseif ($c->pictureFlip === 'flip-both') {
                    imageflip($imageResource, IMG_FLIP_BOTH);
                }
                $imageHandler->imageModified = true;
            }

            // apply filter
            if ($filter !== null && $filter !== ImageFilterEnum::PLAIN) {
                ImageUtility::applyFilter($filter, $imageResource);
                $imageHandler->imageModified = true;
            }

            if ($c->pictureRotation !== '0') {
                $imageResource = $imageHandler->rotateResizeImage(
                    image: $imageResource,
                    degrees: $c->pictureRotation,
                    bgColor: $c->collageBackgroundColor
                );
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

            self::$pictureOrientation = $width > $height ? 'landscape' : 'portrait';

            if ($imageHandler->imageModified) {
                $imageHandler->saveJpeg($imageResource, $editImages[$i]);
                $imageHandler->imageModified = false;
            }

            unset($imageResource);
        }

        if (strpos($c->collageLayout, '2x') === 0) {
            $editImages = array_merge($editImages, $editImages);
        }

        // If no dimensions given ftom json create Collage based on 300dpi 4x6in
        // Scale collages with the height
        if (self::$collageHeight === 0) {
            self::$collageHeight = intval(4 * $c->collageResolution);
        }

        if (self::$collageWidth === 0) {
            self::$collageWidth = intval(self::$collageHeight * 1.5);
        }

        $my_collage = imagecreatetruecolor(self::$collageWidth, self::$collageHeight);
        if (!$my_collage instanceof \GdImage) {
            throw new \Exception('Failed to create collage resource.');
        }

        if (!empty($c->collageBackground)) {
            $backgroundImage = $imageHandler->createFromImage($c->collageBackground);
            if (!$backgroundImage instanceof \GdImage) {
                throw new \Exception('Failed to create collage background image resource.');
            }
            $backgroundImage = $imageHandler->resizeImage($backgroundImage, self::$collageWidth, self::$collageHeight);
            if (!$backgroundImage instanceof \GdImage) {
                throw new \Exception('Failed to resize collage background image resource.');
            }
            imagecopy($my_collage, $backgroundImage, 0, 0, 0, 0, self::$collageWidth, self::$collageHeight);
        } else {
            $background = imagecolorallocate($my_collage, (int) $bg_r, (int) $bg_g, (int) $bg_b);
            imagefill($my_collage, 0, 0, (int) $background);
        }

        $imageHandler->addPictureApplyFrame = $c->collageTakeFrame === 'always' ? true : false;

        if (isset($layoutConfigArray)) {
            $pictureOptions = [];
            foreach ($layoutConfigArray as $layoutConfig) {
                if (!is_array($layoutConfig) || count($layoutConfig) < 5 || count($layoutConfig) > 6) {
                    return false;
                }

                $singlePictureOptions = [];
                for ($j = 0; $j < count($layoutConfig); $j++) {
                    $processed = $layoutConfig[$j];
                    if ($j !== 5) {
                        $value = str_replace(['x', 'y'], [self::$collageWidth, self::$collageHeight], $layoutConfig[$j]);
                        $processed = Helper::doMath($value);
                    }
                    $singlePictureOptions[] = $processed;
                }
                $pictureOptions[] = $singlePictureOptions;
            }
        } else {
            $pictureOptions = self::getPictureOptions($c->collageLayout);
        }

        if (empty($pictureOptions)) {
            throw new \Exception('Failed to get picture options.');
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
                (int)$singlePictureOptions[4],
                isset($singlePictureOptions[5]) ? (bool)$singlePictureOptions[5] : null
            );

            $imageHandler->addPicture($tmpImg, $my_collage);
            unset($tmpImg);
        }

        if (self::$drawDashedLine == true) {
            self::$collageWidth = (int) imagesx($my_collage);
            self::$collageHeight = (int) imagesy($my_collage);
            $imageHandler->dashedLineColor = (string)imagecolorallocate($my_collage, (int)$dashed_r, (int)$dashed_g, (int)$dashed_b);
            $imageHandler->dashedLineStartX = intval(self::$collageWidth * 0.03);
            $imageHandler->dashedLineStartY = intval(self::$collageHeight / 2);
            $imageHandler->dashedLineEndX = intval(self::$collageWidth * 0.97);
            $imageHandler->dashedLineEndY = intval(self::$collageHeight / 2);
            $imageHandler->drawDashedLine($my_collage);
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
        if (self::$rotateAfterCreation) {
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
