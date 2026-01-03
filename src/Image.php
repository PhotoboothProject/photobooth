<?php

namespace Photobooth;

use GdImage;
use Photobooth\Service\ImageMetadataCacheService;
use Photobooth\Utility\FontUtility;
use Photobooth\Utility\ImageUtility;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\QrCodeUtility;

class Image
{
    /**
     * The new filename for the image.
     */
    public string $newFilename;

    /**
     * The debug level for error handling. Set to 0 avoid failing on error.
     */
    public int $debugLevel = 0;

    /**
     * Error counter.
     */
    public int $errorCount = 0;

    /**
     * Array to store error messages.
     */
    public array $errorLog = [];

    /**
     * Indicate source image was modified.
     */
    public bool $imageModified = false;

    /**
     * The quality of the saved jpeg image, from 0 (lowest) to 100 (highest). Default is 80.
     */
    public int $jpegQuality = 80;

    /**
     *
     * Text to Image Difinitions
     *
     */

    /**
     * Font size for the text
     */
    public int $fontSize = 80;

    /**
     * Rotation angle of the font
     */
    public int $fontRotation = 0;

    /**
     * X-coordinate of the starting position for the text
     */
    public int $fontLocationX = 80;

    /**
     * Y-coordinate of the starting position for the text
     */
    public int $fontLocationY = 80;

    /**
     * Color of the font in hexadecimal format (e.g., "#FF0000" for red)
     */
    public string $fontColor = '#ffffff';

    /**
     * File path to the TrueType font file to be used
     */
    public string $fontPath = '';

    /**
     * Text for the first line
     */
    public string $textLine1 = '';

    /**
     * Text for the second line
     */
    public string $textLine2 = '';

    /**
     * Text for the third line
     */
    public string $textLine3 = '';

    /**
     * Vertical spacing between lines of text
     */
    public int $textLineSpacing = 90;

    /**
     * Zone-based text alignment mode (when template uses text_alignment.mode = "zone")
     */
    public bool $textZoneMode = false;

    /**
     * X-coordinate of the text zone (pixels)
     */
    public float $textZoneX = 0;

    /**
     * Y-coordinate of the text zone (pixels)
     */
    public float $textZoneY = 0;

    /**
     * Width of the text zone (pixels)
     */
    public float $textZoneW = 0;

    /**
     * Height of the text zone (pixels)
     */
    public float $textZoneH = 0;

    /**
     * Padding inside the text zone (pixels)
     */
    public float $textZonePadding = 0;

    /**
     * Horizontal alignment within the zone: 'left', 'center', 'right'
     */
    public string $textZoneAlign = 'center';

    /**
     * Vertical alignment within the zone: 'top', 'middle', 'bottom'
     */
    public string $textZoneValign = 'middle';

    /**
     * Rotation angle for zone text (currently only 0 supported)
     */
    public int $textZoneRotation = 0;

    /**
     *
     * Apply Frame to Image Difinitions
     *
     */

    /**
     * File path to the frame image (PNG)
     */
    public string $framePath = '';

    /**
     * Whether to extend the frame to fit the source image
     */
    public bool $frameExtend = false;

    /**
     * The percentage of extension to the left side of the frame
     */
    public int $frameExtendLeft = 0;

    /**
     * The percentage of extension to the right side of the frame
     */
    public int $frameExtendRight = 0;

    /**
     * The percentage of extension to the bottom of the frame
     */
    public int $frameExtendBottom = 0;

    /**
     * The percentage of extension to the top of the frame
     */
    public int $frameExtendTop = 0;

    /**
     *
     * Add picture to image source Difinitions
     *
     */

    /**
     * The x-coordinate of the top-left corner of the picture to be added.
     */
    public int $addPictureX = 0;

    /**
     * The y-coordinate of the top-left corner of the picture to be added.
     */
    public int $addPictureY = 0;

    /**
     * The width of the picture to be added.
     */
    public int $addPictureWidth = 0;

    /**
     * The height of the picture to be added.
     */
    public int $addPictureHeight = 0;

    /**
     * The rotation angle of the picture to be added (in degrees).
     */
    public int $addPictureRotation = 0;

    /**
     * A flag indicating whether to apply a frame to the picture to be added.
     */
    public bool $addPictureApplyFrame = false;

    /**
     *
     * Add dashed line Definitions
     *
     */

    /**
     * Color of the dashed line.
     */
    public string $dashedLineColor = '';

    /**
     * X-coordinate of the starting point of the dashed line.
     */
    public int $dashedLineStartX = 0;

    /**
     * Y-coordinate of the starting point of the dashed line.
     */
    public int $dashedLineStartY = 0;

    /**
     * X-coordinate of the ending point of the dashed line.
     */
    public int $dashedLineEndX = 0;

    /**
     * Y-coordinate of the ending point of the dashed line.
     */
    public int $dashedLineEndY = 0;

    /**
     *
     * QR Difinitions
     *
     */

    /**
     * Whether or not to rotate the QR code.
     */
    public bool $qrRotate = false;

    /**
     * The position to place the QR code on the image.
     */
    public string $qrPosition = 'bottom-right';

    /**
     * The offset in pixels from the specified QR code position.
     */
    public int $qrOffset = 0;

    /**
     * The size of the QR code, numeric value within the range of 2 to 10 and even.
     */
    public int $qrSize = 4;

    /**
     * @var int $qrMargin The margin size around the QR code, must be in range between 0 and 10.
     */
    public int $qrMargin = 4;

    /**
     * The color to apply to the QR code pixels.
     */
    public string $qrColor = '#ffffff';

    /**
     * The URL to generate a QR code for.
     */
    public string $qrUrl = '';

    /**
     *
     * Polaroid Effect Difinitions
     *
     */

    /**
     * The background color for the polaroid effect in hexadecimal format (e.g., '#c8c8c8').
     */
    public string $polaroidBgColor = '#c8c8c8';

    /**
     * The rotation angle for the polaroid effect in degrees.
     */
    public int $polaroidRotation = 0;

    /**
     * Creates a new filename for the image.
     */
    public static function createNewFilename(string $naming = 'random', string $ext = '.jpg'): string
    {
        if ($naming === 'dateformatted') {
            $name = date('Ymd_His') . $ext;
        } else {
            $name = md5(microtime()) . $ext;
        }
        return $name;
    }

    /**
     * Collect error data and increase errorCount
     */
    public function addErrorData(string|array $errorData): void
    {
        $this->errorCount++;
        $this->errorLog[] = $errorData;
    }

    /**
     * Reset the error count and error log.
     */
    public function errorReset(): void
    {
        $this->errorCount = 0;
        $this->errorLog = [];
    }

    /**
     * Sets the new filename for the image using the specified naming convention.
     */
    public function setNewFilename(string $naming): void
    {
        $this->newFilename = $this->createNewFilename($naming);
    }

    /**
     * Returns the new filename for the image.
     */
    public function getNewFilename(): string
    {
        return $this->newFilename;
    }

    /**
     * Sets the new filename for the image using the specified naming convention and returns the new filename.
     */
    public function setAndGetNewFilename(string $naming): string
    {
        $this->setNewFilename($naming);
        return $this->newFilename;
    }

    /**
     * Parses a hex color string and returns its RGBA components.
     *
     * @param string $hexColor The hex color string to parse.
     * @return array An array containing the red, green, blue, and alpha components.
     * @throws \Exception If the color string is invalid or parsing fails.
     */
    public static function getColorComponents(string $hexColor): array
    {
        try {
            if (strlen($hexColor) < 3) {
                throw new \Exception('Invalid color: too short.');
            }

            if (strlen($hexColor) > 9) {
                throw new \Exception('Invalid color: too long.');
            }

            if ($hexColor[0] !== '#') {
                throw new \Exception('Color HEX must start with "#".');
            }

            while (strlen($hexColor) < 9) {
                $hexColor .= '0';
            }

            $colorComponents = sscanf($hexColor, '#%02x%02x%02x%02x');

            if ($colorComponents !== null) {
                list($r, $g, $b, $a) = $colorComponents;
                return [$r, $g, $b, $a];
            } else {
                throw new \Exception('Color parsing failed: sscanf returned null.');
            }
        } catch (\Exception $e) {

            return [0, 0, 0, 0];
        }
    }

    /**
     * Creates a GD image resource from an image file.
     */
    public function createFromImage(string $image): GdImage|false
    {
        try {
            if (str_contains($image, '/api/randomImg.php')) {
                $query = [];
                parse_str(parse_url($image)['query'] ?? '', $query);
                $dirParam = $query['dir'] ?? null;
                if ($dirParam === null) {
                    throw new \Exception('Missing "dir" parameter for random image path.');
                }
                $path = is_array($dirParam) ? $dirParam[0] ?? '' : $dirParam;
                if ($path === '') {
                    throw new \Exception('Invalid "dir" parameter for random image path.');
                }
                $image = ImageUtility::getRandomImageFromPath($path);
            }

            if (!file_exists($image)) {
                $image = PathUtility::resolveFilePath($image);
            }

            $extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            $resource = false;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $resource = @imagecreatefromjpeg($image);
                    break;
                case 'png':
                    $resource = @imagecreatefrompng($image);
                    break;
                case 'gif':
                    $resource = @imagecreatefromgif($image);
                    break;
                case 'webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $resource = @imagecreatefromwebp($image);
                    }
                    break;
                default:
                    $contents = file_get_contents($image);
                    if ($contents === false) {
                        throw new \Exception('Can\'t read image file: ' . $image);
                    }
                    $resource = @imagecreatefromstring((string)$contents);
                    break;
            }
            if (!$resource) {
                throw new \Exception('Can\'t create GD resource.');
            }
            return $resource;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            return false;
        }
    }

    /**
     * Saves a GD image resource to disk.
     */
    public function saveJpeg(GdImage $sourceResource, string $destination): bool
    {
        try {
            // Save the image to disk
            if (!imagejpeg($sourceResource, $destination, $this->jpegQuality)) {
                throw new \Exception('Error saving image.');
            }

            // Cache image dimensions for gallery/PhotoSwipe performance
            $width = imagesx($sourceResource);
            $height = imagesy($sourceResource);
            ImageMetadataCacheService::getInstance()->set($destination, $width, $height);

            return true;
        } catch (\Exception $e) {
            // If there is an exception, return false
            $this->addErrorData($e->getMessage());

            return false;
        }
    }

    /**
     * Rotate and resize an image.
     */
    public function rotateResizeImage(GdImage $image, int $degrees, string $bgColor = '#ffffff', bool $useTransparentBackground = false): GdImage|false
    {
        if ($degrees % 360 === 0) {
            // No rotation needed for 0, 360, -360, etc.
            return $image;
        }

        $new = $image;
        try {
            // simple rotate if possible and ignore changed dimensions (doesn't need to care about background color)
            $simple_rotate = [-180, -90, 0, 180, 90, 360];
            if (in_array($degrees, $simple_rotate)) {
                $new = imagerotate($image, $degrees, 0);
                if (!$new) {
                    throw new \Exception('Cannot rotate image.');
                }
                // without, 0 degree rotation would loose alpha blending, results in black image
                imagealphablending($new, true);
            } else {
                $old_width = imagesx($image);
                $old_height = imagesy($image);

                // Create a new true color image
                $new = imagecreatetruecolor($old_width, $old_height);
                if (!$new) {
                    throw new \Exception('Failed to create new image canvas.');
                }
                if ($useTransparentBackground) {
                    // Enable transparency
                    imagesavealpha($new, true);
                    imagealphablending($new, false);

                    // Allocate a fully transparent background
                    $background = imagecolorallocatealpha($new, 0, 0, 0, 127);
                } else {
                    $colorComponents = self::getColorComponents($bgColor);
                    [$bg_r, $bg_g, $bg_b, $bg_a] = $colorComponents;
                    // color background as defined
                    $background = imagecolorallocatealpha($new, $bg_r, $bg_g, $bg_b, $bg_a);
                }
                if (!imagefill($new, 0, 0, (int)$background)) {
                    throw new \Exception('Cannot fill image.');
                }

                // rotate the image
                $image = imagerotate($image, $degrees, (int)$background);
                if (!$image) {
                    throw new \Exception('Cannot rotate image.');
                }

                // make sure width and/or height fits into old dimensions
                $image = self::resizeImage($image, intval($old_width), intval($old_height));
                if (!$image instanceof \GdImage) {
                    throw new \Exception('Failed to resize image.');
                }
                // get new dimensions after rotate and resize
                $new_width = intval(imagesx($image));
                $new_height = intval(imagesy($image));

                // center rotated image
                $x = intval(($old_width - $new_width) / 2);
                $y = intval(($old_height - $new_height) / 2);

                // copy rotated image to new image with old dimensions
                if (!imagecopy($new, $image, $x, $y, 0, 0, $new_width, $new_height)) {
                    throw new \Exception('Cannot copy rotated image to new image.');
                }
            }
            $this->imageModified = true;
            return $new;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if ($new instanceof \GdImage) {
                unset($new);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $image;
        }
    }

    /**
     * Resize an image based on the maximum dimensions.
     */
    public function resizeImage(GdImage $image, int $maxWidth, ?int $maxHeight = null): GdImage|false
    {
        $maxHeight = $maxHeight ?? $maxWidth;
        try {
            $old_width = imagesx($image);
            $old_height = imagesy($image);

            if ($maxWidth <= 0 || $maxHeight <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            $scale = min($maxWidth / $old_width, $maxHeight / $old_height);

            $new_width = intval(ceil($scale * $old_width));
            $new_height = intval(ceil($scale * $old_height));

            $new_image = imagescale($image, $new_width, $new_height, IMG_TRIANGLE);
            if (!$new_image) {
                throw new \Exception('Cannot resize image.');
            }
            $this->imageModified = true;
            return $new_image;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $image;
        }
    }

    /**
     * Resize a PNG image based on the maximum dimensions.
     */
    public function resizePngImage(GdImage $image, int $newWidth, ?int $newHeight = null, bool $keepAspectRatio = false): GdImage|false
    {
        $newHeight = $newHeight ?? $newWidth;
        try {
            $old_width = intval(imagesx($image));
            $old_height = intval(imagesy($image));

            if ($newWidth <= 0 || $newHeight <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            if ($keepAspectRatio) {
                $scale = min($newWidth / $old_width, $newHeight / $old_height);
                $newWidth = intval(ceil($scale * $old_width));
                $newHeight = intval(ceil($scale * $old_height));
            }
            $new = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
            if (!$new) {
                throw new \Exception('Cannot create new image.');
            }

            imagealphablending($new, false);
            imagesavealpha($new, true);

            if ($keepAspectRatio) {
                if (!imagecopyresized($new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $old_width, $old_height)) {
                    throw new \Exception('Cannot resize image.');
                }
            } else {
                if (!imagecopyresampled($new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $old_width, $old_height)) {
                    throw new \Exception('Cannot resize image.');
                }
            }

            $this->imageModified = true;
            return $new;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if (isset($new) && $new instanceof \GdImage) {
                unset($new);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $image;
        }
    }

    /**
     * Resize and crop an image by center.
     */
    public function resizeCropImage(GdImage $source_file, int $maxWidth, ?int $maxHeight = null): GdImage
    {
        $maxHeight = $maxHeight ?? $maxWidth;
        try {
            $old_width = intval(imagesx($source_file));
            $old_height = intval(imagesy($source_file));

            if ($maxWidth <= 0 || $maxHeight <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            $new_width = intval(($old_height * $maxWidth) / $maxHeight);
            $new_height = intval(($old_width * $maxHeight) / $maxWidth);

            settype($maxWidth, 'integer');
            settype($maxHeight, 'integer');

            $new = imagecreatetruecolor(intval($maxWidth), intval($maxHeight));
            if (!$new) {
                throw new \Exception('Cannot create new image.');
            }

            // If the new width is greater than the actual width of the image, then the height is too large and the rest is cut off, or vice versa
            if ($new_width > $old_width) {
                // Cut point by height
                $h_point = intval(($old_height - $new_height) / 2);
                // Copy image
                if (!imagecopyresampled($new, $source_file, 0, 0, 0, $h_point, $maxWidth, $maxHeight, $old_width, $new_height)) {
                    throw new \Exception('Cannot resize and crop image by height.');
                }
            } else {
                // Cut point by width
                $w_point = intval(($old_width - $new_width) / 2);
                if (!imagecopyresampled($new, $source_file, 0, 0, $w_point, 0, $maxWidth, $maxHeight, $new_width, $old_height)) {
                    throw new \Exception('Cannot resize and crop image by width.');
                }
            }

            $this->imageModified = true;
            return $new;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if (isset($new) && $new instanceof GdImage) {
                unset($new);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $source_file;
        }
    }

    /**
     * Apply the frame to the source image resource
     */
    public function applyFrame(GdImage $sourceResource): GdImage
    {
        try {
            if ($this->frameExtend) {
                $new_width = intval(imagesx($sourceResource) / (1 - 0.01 * ($this->frameExtendLeft + $this->frameExtendRight)));
                $new_height = intval(imagesy($sourceResource) / (1 - 0.01 * ($this->frameExtendTop + $this->frameExtendBottom)));

                $img = imagecreatetruecolor($new_width, $new_height);
                if (!$img instanceof \GdImage) {
                    throw new \Exception('Cannot create new image.');
                }
                $white = intval(imagecolorallocate($img, 255, 255, 255));

                // We fill in the new white image
                if (!imagefill($img, 0, 0, $white)) {
                    throw new \Exception('Cannot fill image.');
                }

                $image_pos_x = intval(imagesx($img) * 0.01 * $this->frameExtendLeft);
                $image_pos_y = intval(imagesy($img) * 0.01 * $this->frameExtendTop);

                // We copy the image to which we want to apply the frame in our new image.
                if (!imagecopy($img, $sourceResource, $image_pos_x, $image_pos_y, 0, 0, imagesx($sourceResource), imagesy($sourceResource))) {
                    throw new \Exception('Error copying image to new frame.');
                }
            } else {
                $img = $sourceResource;
            }

            $pic_width = imagesx($img);
            $pic_height = imagesy($img);

            $frame = self::createFromImage($this->framePath);
            if (!$frame instanceof \GdImage) {
                throw new \Exception('Failed to create frame from image.');
            }

            $frame = self::resizePngImage($frame, $pic_width, $pic_height);
            if (!$frame) {
                throw new \Exception('Cannot resize Frame.');
            }
            $frame_width = imagesx($frame);
            $frame_height = imagesy($frame);

            $dst_x = 0;
            $dst_y = 0;

            if ($pic_height == $frame_height) {
                $dst_x = intval(($pic_width - $frame_width) / 2);
            } else {
                $dst_y = intval(($pic_height - $frame_height) / 2);
            }

            if (!imagecopy($img, $frame, $dst_x, $dst_y, 0, 0, $frame_width, $frame_height)) {
                throw new \Exception('Error applying frame to image.');
            }

            $this->imageModified = true;
            // Return resource with text applied
            return $img;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Clear cache
            if ($img instanceof GdImage) {
                unset($img);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $sourceResource;
        }
    }

    /**
     * Apply text to the source image resource
     */
    public function applyText(GdImage $sourceResource): GdImage
    {
        try {
            $fontPath = PathUtility::getAbsolutePath($this->fontPath);
            $tempFontPath = $_SERVER['DOCUMENT_ROOT'] . '/tempfont.ttf';
            $isTempFont = false;

            // Convert hex color string to RGB values
            $colorComponents = self::getColorComponents($this->fontColor);
            list($r, $g, $b) = $colorComponents;

            // Allocate color and set font
            $color = intval(imagecolorallocate($sourceResource, $r, $g, $b));

            if (PathUtility::isUrl($this->fontPath)) {
                $font = @file_get_contents($this->fontPath);

                if ($font === false) {
                    throw new \Exception('Failed to download font from: ' . $this->fontPath);
                }
                file_put_contents($tempFontPath, $this->fontPath);
                $fontPath = $tempFontPath;
                $isTempFont = true;
            } else {
                $fontPath = FontUtility::getFontPath($this->fontPath);
            }

            // Check if zone mode is enabled
            if ($this->textZoneMode) {
                // Zone-based text rendering
                $this->applyTextInZone($sourceResource, $fontPath, $color);
            } else {
                // Legacy text rendering (original behavior)
                $this->applyTextLegacy($sourceResource, $fontPath, $color);
            }

            if ($isTempFont && file_exists($tempFontPath)) {
                if (!unlink($tempFontPath)) {
                    $this->addErrorData('Failed to delete tmp font: ' . $tempFontPath);
                }
            }
            $this->imageModified = true;
            // Return resource with text applied
            return $sourceResource;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $sourceResource;
        }
    }

    /**
     * Legacy text rendering (original behavior when not in zone mode)
     */
    private function applyTextLegacy(GdImage $sourceResource, string $fontPath, int $color): void
    {
        $fontSize = $this->fontSize;
        $fontRotation = $this->fontRotation;
        $fontLocationX = $this->fontLocationX;
        $fontLocationY = $this->fontLocationY;
        $textLineSpacing = $this->textLineSpacing;

        // Add first line of text
        if (!empty($this->textLine1)) {
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $fontLocationX, $fontLocationY, $color, $fontPath, $this->textLine1)) {
                throw new \Exception('Could not add first line of text to resource.');
            }
        }

        // Add second line of text
        if (!empty($this->textLine2)) {
            $line2Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $textLineSpacing : $fontLocationY;
            $line2X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $textLineSpacing;
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $line2X, $line2Y, $color, $fontPath, $this->textLine2)) {
                throw new \Exception('Could not add second line of text to resource.');
            }
        }

        // Add third line of text
        if (!empty($this->textLine3)) {
            $line3Y = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationY + $textLineSpacing * 2 : $fontLocationY;
            $line3X = $fontRotation < 45 && $fontRotation > -45 ? $fontLocationX : $fontLocationX + $textLineSpacing * 2;
            if (!imagettftext($sourceResource, $fontSize, $fontRotation, $line3X, $line3Y, $color, $fontPath, $this->textLine3)) {
                throw new \Exception('Could not add third line of text to resource.');
            }
        }
    }

    /**
     * Zone-based text rendering with auto-fit, proper alignment and baseline correction
     */
    private function applyTextInZone(GdImage $sourceResource, string $fontPath, int $color): void
    {
        // Collect non-empty text lines
        $lines = [];
        if (!empty(trim($this->textLine1))) {
            $lines[] = trim($this->textLine1);
        }
        if (!empty(trim($this->textLine2))) {
            $lines[] = trim($this->textLine2);
        }
        if (!empty(trim($this->textLine3))) {
            $lines[] = trim($this->textLine3);
        }

        // Nothing to draw if no lines
        if (count($lines) === 0) {
            return;
        }

        // Calculate zone with padding
        $padding = $this->textZonePadding;
        $zoneX = $this->textZoneX + $padding;
        $zoneY = $this->textZoneY + $padding;
        $zoneW = $this->textZoneW - (2 * $padding);
        $zoneH = $this->textZoneH - (2 * $padding);

        // Ensure zone has positive dimensions
        if ($zoneW <= 0 || $zoneH <= 0) {
            return;
        }

        // Calculate line height factor from admin settings
        $lineHeightFactor = $this->textLineSpacing > 0 && $this->fontSize > 0
            ? $this->textLineSpacing / $this->fontSize
            : 1.2;

        // Auto-fit: find the largest font size that fits all text in the zone
        $fontSize = $this->fontSize > 0 ? $this->fontSize : 50;
        $minFontSize = 10; // Absolute minimum
        $preferredMinFontSize = 18; // Preferred minimum for readability

        $fitsInZone = false;
        while ($fontSize >= $minFontSize && !$fitsInZone) {
            $lineHeight = (int)($fontSize * $lineHeightFactor);
            $maxLineWidth = 0;

            // Measure all lines at current font size
            foreach ($lines as $line) {
                $bbox = @imagettfbbox($fontSize, 0, $fontPath, $line);
                if ($bbox !== false) {
                    $lineWidth = abs($bbox[2] - $bbox[0]);
                    if ($lineWidth > $maxLineWidth) {
                        $maxLineWidth = $lineWidth;
                    }
                }
            }

            // Calculate total block height
            // Height = (n-1) * lineHeight + fontSize (last line doesn't need spacing below)
            $blockHeight = (count($lines) - 1) * $lineHeight + $fontSize;

            // Check if it fits
            if ($maxLineWidth <= $zoneW && $blockHeight <= $zoneH) {
                $fitsInZone = true;
            } else {
                $fontSize--;
            }
        }

        // If even minimum font size doesn't fit, use minimum anyway
        if (!$fitsInZone) {
            $fontSize = $minFontSize;
        }

        // Recalculate with final font size
        $lineHeight = (int)($fontSize * $lineHeightFactor);
        $blockHeight = (count($lines) - 1) * $lineHeight + $fontSize;

        // Get ascent for baseline correction
        // The ascent is the distance from baseline to top of tallest character
        $ascentBbox = @imagettfbbox($fontSize, 0, $fontPath, 'HgjpqyÄÖÜ');
        $ascent = $ascentBbox !== false ? abs($ascentBbox[7]) : $fontSize;

        // Calculate vertical start position based on valign
        switch ($this->textZoneValign) {
            case 'bottom':
                $startTopY = $zoneY + $zoneH - $blockHeight;
                break;
            case 'middle':
                $startTopY = $zoneY + ($zoneH - $blockHeight) / 2;
                break;
            case 'top':
            default:
                $startTopY = $zoneY;
                break;
        }

        // Draw each line with individual horizontal alignment
        foreach ($lines as $index => $line) {
            // Measure this specific line
            $bbox = @imagettfbbox($fontSize, 0, $fontPath, $line);
            $lineWidth = $bbox !== false ? abs($bbox[2] - $bbox[0]) : 0;

            // Calculate X position based on align
            switch ($this->textZoneAlign) {
                case 'right':
                    $drawX = (int)($zoneX + $zoneW - $lineWidth);
                    break;
                case 'center':
                    $drawX = (int)($zoneX + ($zoneW - $lineWidth) / 2);
                    break;
                case 'left':
                default:
                    $drawX = (int)$zoneX;
                    break;
            }

            // Calculate Y position (baseline position)
            // First line: startTopY + ascent (to position top of text at startTopY)
            // Subsequent lines: add lineHeight for each
            $drawY = (int)($startTopY + $ascent + ($index * $lineHeight));

            // Draw the text (rotation is 0 for zone mode)
            if (!imagettftext($sourceResource, $fontSize, 0, $drawX, $drawY, $color, $fontPath, $line)) {
                throw new \Exception('Could not add line ' . ($index + 1) . ' of text to resource.');
            }
        }
    }

    /**
     * Set the picture options for adding a picture to image resource.
     */
    public function setAddPictureOptions(int $x, int $y, int $width, int $height, int $rotation, ?bool $applyFrame = null): void
    {
        $this->addPictureX = $x;
        $this->addPictureY = $y;
        $this->addPictureWidth = $width;
        $this->addPictureHeight = $height;
        $this->addPictureRotation = $rotation;
        if ($applyFrame !== null) {
            $this->addPictureApplyFrame = $applyFrame;
        }
    }

    /**
     * Add a picture to the destination image resource.
     */
    public function addPicture(GdImage $imageResource, GdImage $destinationResource): void
    {
        try {
            $dX = intval($this->addPictureX);
            $dY = intval($this->addPictureY);
            $width = intval($this->addPictureWidth);
            $height = intval($this->addPictureHeight);
            $degrees = intval($this->addPictureRotation);

            if ($width <= 0 || $height <= 0) {
                throw new \Exception('Invalid image dimensions or maximum dimensions.');
            }

            if (abs($degrees) == 90) {
                $imageResource = $this->resizeCropImage($imageResource, $height, $width);
            } else {
                $imageResource = $this->resizeCropImage($imageResource, $width, $height);
            }

            if ($this->addPictureApplyFrame) {
                $imageResource = $this->applyFrame($imageResource);
            }

            if ($degrees != 0) {
                $imageResource = $this->rotateResizeImage(
                    image: $imageResource,
                    degrees: $degrees,
                    useTransparentBackground: true
                );
                if (!$imageResource instanceof \GdImage) {
                    throw new \Exception('Failed to rotate and resize image.');
                }
                if (abs($degrees) != 90) {
                    $width = intval(imagesx($imageResource));
                    $height = intval(imagesy($imageResource));
                }
            }

            if (!imagecopy($destinationResource, $imageResource, $dX, $dY, 0, 0, $width, $height)) {
                throw new \Exception('Can\'t add image to resource.');
            }
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());
            throw $e;
        }
        $this->imageModified = true;
    }

    /**
     * Draw a dashed line on the specified image resource.
     */
    public function drawDashedLine(GdImage $imageResource): void
    {
        try {
            $dashedLine = [
                $this->dashedLineColor,
                $this->dashedLineColor,
                $this->dashedLineColor,
                $this->dashedLineColor,
                IMG_COLOR_TRANSPARENT,
                IMG_COLOR_TRANSPARENT,
                IMG_COLOR_TRANSPARENT,
                IMG_COLOR_TRANSPARENT,
            ];
            if (!imagesetstyle($imageResource, $dashedLine)) {
                throw new \Exception('Can\'t set the style for line drawing.');
            }
            if (!imageline($imageResource, $this->dashedLineStartX, $this->dashedLineStartY, $this->dashedLineEndX, $this->dashedLineEndY, IMG_COLOR_STYLED)) {
                throw new \Exception('Can\'t draw image line.');
            }
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            return;
        }
        $this->imageModified = true;
    }

    /**
     * Generates a QR code image using the provided URL and configuration settings.
     */
    public function createQr(): GdImage
    {
        try {
            if (empty($this->qrUrl)) {
                throw new \Exception('No URL for QR-Code generation defined.');
            }

            if (!is_numeric($this->qrSize)) {
                throw new \Exception('QR-Size is not numeric.');
            }
            if ($this->qrSize % 2 != 0) {
                throw new \Exception('QR-Size is not even.');
            }
            if ($this->qrSize < 2 || $this->qrSize > 10) {
                throw new \Exception('QR-Size must be 2, 4, 6, 8 or 10.');
            }

            if (!is_numeric($this->qrMargin)) {
                throw new \Exception('QR-Margin is not numeric.');
            }
            if ($this->qrMargin < 0 || $this->qrMargin > 10) {
                throw new \Exception('QR-Size must be in range between 0 and 10.');
            }

            $size = $this->qrSize * 40;
            $margin = (int)($size / 100 * $this->qrMargin);
            $result = QrCodeUtility::create($this->qrUrl, '', $size, $margin);
            $qrCodeImage = imagecreatefromstring($result->getString());

            if (!$qrCodeImage) {
                throw new \Exception('Failed to create image from QR code.');
            }

            if ($this->qrRotate) {
                if (!imagerotate($qrCodeImage, 90, 0)) {
                    throw new \Exception('Unable to rotate QR-Code-Image.');
                }
            }
            if ($this->qrColor != '#ffffff') {
                $qrwidth = imagesx($qrCodeImage);
                $qrheight = imagesy($qrCodeImage);
                $colorComponents = self::getColorComponents($this->qrColor);
                list($r, $g, $b) = $colorComponents;

                $selected = intval(imagecolorallocate($qrCodeImage, $r, $g, $b));

                for ($xpos = 0; $xpos < $qrwidth; $xpos++) {
                    for ($ypos = 0; $ypos < $qrheight; $ypos++) {
                        $currentcolor = intval(imagecolorat($qrCodeImage, $xpos, $ypos));
                        $parts = imagecolorsforindex($qrCodeImage, $currentcolor);

                        if ($parts['red'] == 255 && $parts['green'] == 255 && $parts['blue'] == 255) {
                            imagesetpixel($qrCodeImage, $xpos, $ypos, $selected);
                        }
                    }
                }
            }
            return $qrCodeImage;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generates a QR code and displays it as a PNG image.
     */
    public function showQR(): void
    {
        try {
            // Generate the QR code
            $qrCode = $this->createQr();

            // Display the QR code as a PNG image
            imagepng($qrCode);
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // If an exception is caught, display the error message
            echo $e->getMessage();
        }
    }

    /**
     * Generates a QR code and saves it to a specified destination path.
     */
    public function saveQr(string $destination): bool
    {
        try {
            if (empty($destination)) {
                throw new \Exception('No destination path given.');
            }

            // Generate the QR code
            $qrCode = $this->createQr();

            // Save the QR code as a PNG image to the specified destination path
            if (!imagepng($qrCode, $destination)) {
                throw new \Exception('Unable to save QR code to ' . $destination);
            }

            // Return true if the QR code was successfully saved
            return true;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // If an exception is caught, return false
            return false;
        }
    }

    /**
     * Applies a generated QR code image to an existing image resource.
     */
    public function applyQr(GdImage $qrCode, GdImage $imageResource): GdImage
    {
        try {
            if (!is_numeric($this->qrOffset)) {
                throw new \Exception('QR-Offset is not numeric.');
            }
            $offset = $this->qrOffset;

            $width = imagesx($imageResource);
            $height = imagesy($imageResource);
            $qrWidth = imagesx($qrCode);
            $qrHeight = imagesy($qrCode);

            switch ($this->qrPosition) {
                case 'topLeft':
                    $x = $offset;
                    $y = $offset;
                    break;
                case 'top':
                    $x = ($width - $qrWidth) / 2;
                    $y = $offset;
                    break;
                case 'topRight':
                    $x = $width - ($qrWidth + $offset);
                    $y = $offset;
                    break;
                case 'right':
                    $x = $width - $qrWidth - $offset;
                    $y = ($height - $qrHeight) / 2;
                    break;
                case 'bottomRight':
                    $x = $width - ($qrWidth + $offset);
                    $y = $height - ($qrHeight + $offset);
                    break;
                case 'bottom':
                    $x = ($width - $qrWidth) / 2;
                    $y = $height - $qrHeight - $offset;
                    break;
                case 'bottomLeft':
                    $x = $offset;
                    $y = $height - ($qrHeight + $offset);
                    break;
                case 'left':
                    $x = $offset;
                    $y = ($height - $qrHeight) / 2;
                    break;
                default:
                    $x = $width - ($qrWidth + $offset);
                    $y = $height - ($qrHeight + $offset);
                    break;
            }

            if (!imagecopy($imageResource, $qrCode, $x, $y, 0, 0, $qrWidth, $qrHeight)) {
                throw new \Exception('Can not apply QR Code onto image.');
            }
            // Try to clear cache
            if ($qrCode instanceof GdImage) {
                unset($qrCode);
            }
            $this->imageModified = true;
            return $imageResource;
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if ($qrCode instanceof GdImage) {
                unset($qrCode);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // return unmodified resource
            return $imageResource;
        }
    }

    /**
     * Applies a polaroid effect to the given image resource.
     */
    public function effectPolaroid(GdImage $resource): GdImage
    {
        try {
            // Get resolution of the input image
            $resourceWidth = imagesx($resource);
            $resourceHeight = imagesy($resource);

            // Determine the shorter side of the original image, used as a reference for border thickness
            $resourceShortSide = min($resourceWidth, $resourceHeight);

            // Define base border thickness parameters. These could eventually be configurable in the Admin Panel.
            $borderThinPercentage = 2; // Percentage of the original short side for thin borders (left, right, top)
            $factorBottomBorder = 6;   // Multiplier for the bottom border thickness relative to the thin borders

            // Calculate actual pixel thickness
            $borderThinPx = (int) round($borderThinPercentage * $resourceShortSide / 100); // for the thin borders
            $borderBottomPx = $factorBottomBorder * $borderThinPx; // for the thick bottom border

            // Calculate dimensions for the final Polaroid image.
            $finalPolaroidWidth = $resourceWidth + (2 * $borderThinPx);
            // The final Polaroid height is calculated to maintain the original image's aspect ratio (width/height).
            $finalPolaroidHeight = (int) round($finalPolaroidWidth / ($resourceWidth / $resourceHeight));

            // Calculate the target height for the image *content* within the Polaroid frame.
            $targetImageContentHeight = $finalPolaroidHeight - $borderThinPx - $borderBottomPx;

            // Error handling: Ensure there is enough vertical space for the image content after borders.
            if ($targetImageContentHeight <= 0) {
                throw new \Exception('Polaroid borders are too large, no space left for image content. Please adjust border settings.');
            }

            // Calculate the amount of pixels to be cropped from the original image's height.
            // This 'cropAmount' is distributed evenly on the top and bottom of the original image.
            $cropAmountTotal = $resourceHeight - $targetImageContentHeight;
            // Calculate the offset from the top (and bottom) of the original image for the crop.
            $cropYOffset = (int) round($cropAmountTotal / 2);

            // Create a new GD image resource for the final Polaroid output.
            // This canvas will have the calculated final dimensions and serve as the base for the Polaroid.
            $polaroidCanvas = imagecreatetruecolor($finalPolaroidWidth, $finalPolaroidHeight);
            if (!$polaroidCanvas) {
                throw new \Exception('Failed to create new image canvas for Polaroid effect.');
            }
            $white = intval(imagecolorallocate($polaroidCanvas, 255, 255, 255));

            // Fill the entire canvas with white. This forms the base for all white borders.
            if (!imagefill($polaroidCanvas, 0, 0, $white)) {
                throw new \Exception('Failed to fill Polaroid canvas with white color.');
            }

            // Copy the original image onto the Polaroid canvas.
            // The image is copied without scaling. Vertical cropping is achieved by specifying
            // the source's Y-offset ($cropYOffset) and the source's effective height ($targetImageContentHeight).
            // The destination X/Y positions ($borderThinPx, $borderThinPx) define the top-left
            // corner of where the (cropped) image content starts within the Polaroid canvas.
            if (!imagecopy(
                $polaroidCanvas,             // Destination image resource
                $resource,                   // Source image resource
                $borderThinPx,               // Destination X-coordinate (left border)
                $borderThinPx,               // Destination Y-coordinate (top border)
                0,                           // Source X-coordinate (start from left of original image)
                $cropYOffset,                // Source Y-coordinate (start from Y-offset within original image for cropping)
                $resourceWidth,              // Width of the source rectangle to copy (full original width)
                $targetImageContentHeight    // Height of the source rectangle to copy (cropped height from original)
            )) {
                unset($polaroidCanvas);
                throw new \Exception('Failed to copy image onto Polaroid canvas.');
            }

            // Convert hex color string to RGB values
            $colorComponents = self::getColorComponents($this->polaroidBgColor);
            list($rbcc, $gbcc, $bbcc) = $colorComponents;

            // We rotate the image
            $rotationBackgroundColor = intval(imagecolorallocate($polaroidCanvas, $rbcc, $gbcc, $bbcc));
            $rotatedImg = imagerotate($polaroidCanvas, $this->polaroidRotation, $rotationBackgroundColor);

            if (!$rotatedImg) {
                throw new \Exception('Cannot rotate image.');
            }
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if (isset($polaroidCanvas) && $polaroidCanvas instanceof GdImage) {
                unset($polaroidCanvas);
            }

            // Re-throw exception on loglevel > 1
            if ($this->debugLevel > 1) {
                throw $e;
            }

            // Return unmodified resource
            return $resource;
        }
        $this->imageModified = true;
        // We destroy the image we have been working with
        unset($polaroidCanvas);

        // We return the rotated image
        return $rotatedImg;
    }
}
