<?php

namespace Photobooth;

use Photobooth\Dto\CollageConfig;
use Photobooth\Enum\ImageFilterEnum;
use Photobooth\Factory\CollageConfigFactory;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;
use Psr\Log\LoggerInterface;

class Collage
{
    public static int $collageHeight = 0;
    public static int $collageWidth = 0;
    public static bool $drawDashedLine = false;
    public static string $pictureOrientation = '';
    public static bool $rotateAfterCreation = false;
    public static string $layoutPath = '';

    public static function reset(): void
    {
        self::$collageHeight = 0;
        self::$collageWidth = 0;
        self::$drawDashedLine = false;
        self::$pictureOrientation = '';
        self::$rotateAfterCreation = false;
        self::$layoutPath = '';
    }

    /**
     * Calculate collage limit based on layout definition and placeholder settings.
     *
     * @return array{limit:int, placeholderEnabled:bool}
     */
    public static function calculateLimit(
        array $collageConfig,
        ?LoggerInterface $logger = null
    ): array {
        $layout = (string) ($collageConfig['layout'] ?? '');
        $orientation = (string) ($collageConfig['orientation'] ?? 'landscape');
        $placeholderEnabled = (bool) ($collageConfig['placeholder'] ?? false);
        $placeholderPosition = (int) ($collageConfig['placeholderposition'] ?? 0);
        $placeholderPath = $collageConfig['placeholderpath'] ?? null;

        $fallbackLimit = (int) ($collageConfig['limit'] ?? 1);
        if ($fallbackLimit < 1) {
            $fallbackLimit = 1;
        }
        $limit = $fallbackLimit;
        $logger = $logger ?? LoggerService::getInstance()->getLogger('main');

        $collageConfigFilePath = self::getCollageConfigPath($layout, $orientation);

        if ($collageConfigFilePath !== null) {
            $collageJson = json_decode((string) file_get_contents($collageConfigFilePath), true);
            if (is_array($collageJson)) {
                $layoutConfigArray = !empty($collageJson['layout'])
                    ? $collageJson['layout']
                    : $collageJson;

                if (str_starts_with($layout, '2x')) {
                    $limit = (int) ceil(count($layoutConfigArray) / 2);
                } else {
                    $limit = count($layoutConfigArray);
                }

                if ($placeholderEnabled) {
                    if ($placeholderPosition > 0 && $placeholderPosition <= $limit) {
                        $limit--;
                    } else {
                        $placeholderEnabled = false;
                        $logger->debug('Placeholder position not in range. Placeholder disabled.');
                    }

                    if ($placeholderPath === null || $placeholderPath === '') {
                        $placeholderEnabled = false;
                        $logger->debug('Collage Placeholder is empty. Collage Placeholder disabled.');
                    }
                }
            } else {
                $logger->debug('No valid collage json found. Collage disabled.');
            }
        }

        if ($limit < 1) {
            $limit = $fallbackLimit;
            $placeholderEnabled = false;
            $logger->debug('Invalid collage limit, must be 1 or greater. Collage disabled.');
        }

        return [
            'limit' => $limit,
            'placeholderEnabled' => $placeholderEnabled,
        ];
    }

    public static function getCollageConfigPath(string $collageLayout, string $pictureOrientation): ?string
    {
        self::$drawDashedLine =
            $collageLayout === '2x4-2' ||
            $collageLayout === '2x4-3' ||
            $collageLayout === '2x3-1';

        if (!str_ends_with($collageLayout, '.json')) {
            $collageLayout .= '.json';
        }

        $relativePaths = [
            'private/collage/' . $pictureOrientation . '/' . $collageLayout,
            'private/collage/' . $collageLayout,
            'private/' . $collageLayout,
            'template/collage/' . $pictureOrientation . '/' . $collageLayout,
            'template/collage/' . $collageLayout,
        ];

        foreach ($relativePaths as $relativePath) {
            $absolutePath = PathUtility::getAbsolutePath($relativePath);

            if (file_exists($absolutePath)) {
                self::$layoutPath = $absolutePath;
                return $absolutePath;
            }
        }

        return null;
    }

    public static function createCollage(array $config, array $srcImagePaths, string $destImagePath, ?ImageFilterEnum $filter = null, ?CollageConfig $c = null): bool
    {
        if ($c === null) {
            $c = CollageConfigFactory::fromConfig($config);
        }
        self::reset();
        $imageHandler = new Image();
        $imageHandler->jpegQuality = 100;
        $editImages = [];
        $firstImagePath = $srcImagePaths[0] ?? null;

        self::$pictureOrientation = $c->collageOrientation;

        $collageConfigFilePath = self::getCollageConfigPath($c->collageLayout, self::$pictureOrientation);

        // Save the original admin setting for text on collage
        $adminTextOnCollageEnabled = $c->textOnCollageEnabled;

        if ($collageConfigFilePath !== null) {
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

                    // JSON can only override if admin setting allows text
                    if ($adminTextOnCollageEnabled === 'enabled' && isset($collageJson['text_custom_style'])) {
                        $c->textOnCollageEnabled = $collageJson['text_custom_style'] ? 'enabled' : 'disabled';
                    }

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

                    if ($c->collageAllowSelection) {
                        // JSON layout can only disable or customize text if admin has enabled it
                        if ($adminTextOnCollageEnabled === 'enabled') {
                            if (isset($collageJson['text_disabled']) && $collageJson['text_disabled'] === true) {
                                $c->textOnCollageEnabled = 'disabled';
                            } elseif (isset($collageJson['text_alignment']) && is_array($collageJson['text_alignment'])) {
                                $ta = $collageJson['text_alignment'];
                                $c->textOnCollageEnabled = 'enabled';

                                $replace = ['x' => self::$collageWidth, 'y' => self::$collageHeight];

                                // Check if zone mode
                                if (isset($ta['mode']) && $ta['mode'] === 'zone') {
                                    // Zone mode: store zone parameters for Image::applyTextInZone()
                                    $c->textZoneMode = true;
                                    $c->textZoneX = (float) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['x'] ?? '0'));
                                    $c->textZoneY = (float) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['y'] ?? '0'));
                                    $c->textZoneW = isset($ta['w']) ? (float) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['w'])) : 0;
                                    $c->textZoneH = isset($ta['h']) ? (float) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['h'])) : 0;
                                    $c->textZonePadding = isset($ta['padding']) ? (float) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['padding'])) : 0;
                                    $c->textZoneAlign = $ta['align'] ?? 'center';
                                    $c->textZoneValign = $ta['valign'] ?? 'middle';
                                    $c->textZoneRotation = isset($ta['rotation']) ? (int) $ta['rotation'] : 0;

                                    // In zone mode: ignore admin X/Y/Rotation values
                                    // Keep admin font, color, text lines, fontSize (as start), lineHeight (as factor)
                                } else {
                                    // Legacy mode: calculate X/Y position based on alignment
                                    $zoneX = Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['x'] ?? '0'));
                                    $zoneY = Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['y'] ?? '0'));
                                    $zoneW = isset($ta['w']) ? Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['w'])) : 0;
                                    $zoneH = isset($ta['h']) ? Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['h'])) : 0;

                                    if (isset($ta['fontSize'])) {
                                        $c->textOnCollageFontSize = (int) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['fontSize']));
                                    }

                                    if (isset($ta['rotation'])) {
                                        $c->textOnCollageRotation = (int) $ta['rotation'];
                                    }

                                    if (isset($ta['lineHeight'])) {
                                        $c->textOnCollageLinespace = (int) Helper::doMath(str_replace(array_keys($replace), array_values($replace), $ta['lineHeight']));
                                    }

                                    $align = $ta['align'] ?? 'start';
                                    $valign = $ta['valign'] ?? 'top';

                                    if ($align === 'center' || $valign === 'middle') {
                                        $textLines = [];
                                        if (!empty($c->textOnCollageLine1)) {
                                            $textLines[] = $c->textOnCollageLine1;
                                        }
                                        if (!empty($c->textOnCollageLine2)) {
                                            $textLines[] = $c->textOnCollageLine2;
                                        }
                                        if (!empty($c->textOnCollageLine3)) {
                                            $textLines[] = $c->textOnCollageLine3;
                                        }

                                        if (count($textLines) > 0 && file_exists($c->textOnCollageFont)) {
                                            $maxWidth = 0;
                                            foreach ($textLines as $line) {
                                                $bbox = imagettfbbox($c->textOnCollageFontSize, $c->textOnCollageRotation, $c->textOnCollageFont, $line);
                                                if ($bbox) {
                                                    $width = abs($bbox[2] - $bbox[0]);
                                                    if ($width > $maxWidth) {
                                                        $maxWidth = $width;
                                                    }
                                                }
                                            }
                                            $totalHeight = $c->textOnCollageFontSize + (count($textLines) - 1) * $c->textOnCollageLinespace;

                                            if ($align === 'center') {
                                                $c->textOnCollageLocationX = (int) ($zoneX + ($zoneW - $maxWidth) / 2);
                                            } else {
                                                $c->textOnCollageLocationX = (int) $zoneX;
                                            }

                                            if ($valign === 'middle') {
                                                $c->textOnCollageLocationY = (int) ($zoneY + ($zoneH - $totalHeight) / 2 + $c->textOnCollageFontSize);
                                            } else {
                                                $c->textOnCollageLocationY = (int) ($zoneY + $c->textOnCollageFontSize);
                                            }
                                        } else {
                                            $c->textOnCollageLocationX = (int) $zoneX;
                                            $c->textOnCollageLocationY = (int) $zoneY;
                                        }
                                    } else {
                                        $c->textOnCollageLocationX = (int) $zoneX;
                                        $c->textOnCollageLocationY = (int) $zoneY;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $layoutConfigArray = $collageJson;
                }
            } else {
                return false;
            }
        }

        $bg_r = 255;
        $bg_g = 255;
        $bg_b = 255;
        $bg_color_hex = 0xFFFFFF;

        if ($c->collageBackgroundColor !== null) {
            // colors for background and while rotating jpeg images
            $colorComponents = $imageHandler->getColorComponents($c->collageBackgroundColor);
            list($bg_r, $bg_g, $bg_b) = $colorComponents;
            $bg_color_hex = hexdec(substr($c->collageBackgroundColor, 1));
            if (!is_int($bg_color_hex)) {
                throw new \Exception('Cannot convert the hexadecimal collage background color to its decimal equivalent!');
            }
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
                $srcIndex = $i - $placeholderOffset;
                if (!isset($srcImagePaths[$srcIndex]) || !file_exists($srcImagePaths[$srcIndex])) {
                    throw new \Exception('The file ' . ($srcImagePaths[$srcIndex] ?? 'undefined') . ' does not exist.');
                }
                $singleimage = substr($srcImagePaths[$srcIndex], 0, -4);
                $editfilename = $singleimage . '-edit.jpg';
                if (!copy($srcImagePaths[$i - $placeholderOffset], $editfilename)) {
                    throw new \Exception('Failed to copy image for editing.');
                }
                $editImages[] = $editfilename;
            }
        }

        $imageHandler->framePath = Helper::getPrefixedFile($c->collageFrame, $c->collageLayout);
        $imageHandler->frameExtend = false;

        for ($i = 0; $i < $c->collageLimit; $i++) {
            $imageResource = $imageHandler->createFromImage($editImages[$i]);
            if (!$imageResource instanceof \GdImage) {
                throw new \Exception('Failed to create image resource.');
            }

            if (
                $c->pictureFlip !== 'off'
                && (!$c->collagePlaceholder || $c->collagePlaceholderPosition !== $i)
            ) {
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

            if ($c->collagePolaroidEffect === 'enabled') {
                $imageHandler->polaroidRotation = $c->collagePolaroidRotation;
                $imageResource = $imageHandler->effectPolaroid($imageResource);
            }

            $width = (int) imagesx($imageResource);
            $height = (int) imagesy($imageResource);

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
            self::$collageHeight = 1200;
        }

        if (self::$collageWidth === 0) {
            self::$collageWidth = 1800;
        }

        $my_collage = imagecreatetruecolor(self::$collageWidth, self::$collageHeight);
        if (!$my_collage instanceof \GdImage) {
            throw new \Exception('Failed to create collage resource.');
        }

        $c->collageBackground = Helper::getPrefixedFile($c->collageBackground, $c->collageLayout);
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

        $pictureOptions = [];
        if (isset($layoutConfigArray)) {
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
            if (self::$pictureOrientation === 'portrait') {
                $imageHandler->dashedLineStartX = intval(self::$collageWidth * 0.03);
                $imageHandler->dashedLineStartY = intval(self::$collageHeight / 2);
                $imageHandler->dashedLineEndX = intval(self::$collageWidth * 0.97);
                $imageHandler->dashedLineEndY = intval(self::$collageHeight / 2);
            } else {
                $imageHandler->dashedLineStartX = intval(self::$collageWidth / 2);
                $imageHandler->dashedLineStartY = 0;
                $imageHandler->dashedLineEndX = intval(self::$collageWidth / 2);
                $imageHandler->dashedLineEndY = intval(self::$collageHeight);
            }
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

            // Set zone mode properties if enabled
            $imageHandler->textZoneMode = $c->textZoneMode;
            if ($c->textZoneMode) {
                $imageHandler->textZoneX = $c->textZoneX;
                $imageHandler->textZoneY = $c->textZoneY;
                $imageHandler->textZoneW = $c->textZoneW;
                $imageHandler->textZoneH = $c->textZoneH;
                $imageHandler->textZonePadding = $c->textZonePadding;
                $imageHandler->textZoneAlign = $c->textZoneAlign;
                $imageHandler->textZoneValign = $c->textZoneValign;
                $imageHandler->textZoneRotation = $c->textZoneRotation;
            }

            $my_collage = $imageHandler->applyText($my_collage);
            if (!$my_collage instanceof \GdImage) {
                throw new \Exception('Failed to apply text to collage resource.');
            }

            // If this is a 2x* layout (images duplicated) also draw the text
            // on the second half so both sides show the same caption.
            if (strpos($c->collageLayout, '2x') === 0) {
                if (self::$pictureOrientation === 'landscape') {
                    // Landscape: duplicate horizontally (shift X to right half)
                    $origX = $imageHandler->fontLocationX;
                    $shift = (int) (self::$collageWidth / 2);
                    $imageHandler->fontLocationX = $origX + $shift;

                    // Apply text again with zone mode support
                    if ($imageHandler->textZoneMode) {
                        $origZoneX = $imageHandler->textZoneX;
                        $imageHandler->textZoneX = $origZoneX + $shift;
                        $my_collage = $imageHandler->applyText($my_collage);
                        $imageHandler->textZoneX = $origZoneX;
                    } else {
                        $my_collage = $imageHandler->applyText($my_collage);
                    }

                    if (!$my_collage instanceof \GdImage) {
                        throw new \Exception('Failed to apply duplicated text to collage resource.');
                    }
                    $imageHandler->fontLocationX = $origX;
                } else {
                    // Portrait: duplicate vertically (shift Y to bottom half)
                    $origY = $imageHandler->fontLocationY;
                    $shift = (int) (self::$collageHeight / 2);
                    $imageHandler->fontLocationY = $origY + $shift;

                    // Apply text again with zone mode support
                    if ($imageHandler->textZoneMode) {
                        $origZoneY = $imageHandler->textZoneY;
                        $imageHandler->textZoneY = $origZoneY + $shift;
                        $my_collage = $imageHandler->applyText($my_collage);
                        $imageHandler->textZoneY = $origZoneY;
                    } else {
                        $my_collage = $imageHandler->applyText($my_collage);
                    }

                    if (!$my_collage instanceof \GdImage) {
                        throw new \Exception('Failed to apply duplicated text to collage resource.');
                    }
                    $imageHandler->fontLocationY = $origY;
                }
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
