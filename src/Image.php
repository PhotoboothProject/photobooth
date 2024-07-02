<?php

namespace Photobooth;

use GdImage;
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
     * Resize Image Difinitions
     *
     */

    /**
     * The rotation angle for image resizing.
     */
    public int $resizeRotation = 0;

    /**
     * The background color in hexadecimal format (#RRGGBB) for image resizing.
     */
    public string $resizeBgColor = '#ffffff';

    /**
     * The maximum width for image resizing.
     */
    public int $resizeMaxWidth = 0;

    /**
     * The maximum height for image resizing.
     */
    public int $resizeMaxHeight = 0;

    /**
     * Determine to keep aspect ratio on resize.
     */
    public bool $keepAspectRatio = false;

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
     * The path to the background image to be used when rotating the picture to be added.
     */
    public string $addPictureBgImage = '';

    /**
     * The hexadecimal color code for the background color to be used when rotating the picture to be added.
     */
    public string $addPictureBgColor = '#0000007f';

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
     * Creates a GD image resource from an image file.
     */
    public function createFromImage(string $image): GdImage|false
    {
        try {
            if (str_contains($image, '/api/randomImg.php')) {
                parse_str(parse_url($image)['query'] ?? '', $query);
                $path = is_array($query['dir']) ? $query['dir']['0'] : $query['dir'];
                $image = ImageUtility::getRandomImageFromPath($path);
            } elseif (PathUtility::isAbsolutePath(PathUtility::getAbsolutePath($image))) {
                $image = PathUtility::getAbsolutePath($image);
            }

            $resource = imagecreatefromstring((string)file_get_contents($image));
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
    public function rotateResizeImage(GdImage $image): GdImage|false
    {
        try {
            $rotation = intval($this->resizeRotation);

            // simple rotate if possible and ignore changed dimensions (doesn't need to care about background color)
            $simple_rotate = [-180, -90, 0, 180, 90, 360];
            if (in_array($rotation, $simple_rotate)) {
                $new = imagerotate($image, $rotation, 0);
                if (!$new) {
                    throw new \Exception('Cannot rotate image.');
                }
            } else {
                $bg_color = $this->resizeBgColor;
                if (strlen($bg_color) === 7) {
                    $bg_color .= '00';
                }
                $colorComponents = sscanf($bg_color, '#%02x%02x%02x%02x');
                if ($colorComponents !== null) {
                    list($bg_r, $bg_g, $bg_b, $bg_a) = $colorComponents;
                    $bg_r = intval($bg_r);
                    $bg_g = intval($bg_g);
                    $bg_b = intval($bg_b);
                    $bg_a = intval($bg_a);

                } else {
                    throw new \Exception('Background color: sscanf returned null!');
                }

                // get old dimensions
                $old_width = intval(imagesx($image));
                $old_height = intval(imagesy($image));

                // create new image with old dimensions
                $new = imagecreatetruecolor($old_width, $old_height);
                if (!$new) {
                    throw new \Exception('Cannot create new image.');
                }

                // color background as defined
                $background = imagecolorallocatealpha($new, $bg_r, $bg_g, $bg_b, $bg_a);
                if (!imagefill($new, 0, 0, (int)$background)) {
                    throw new \Exception('Cannot fill image.');
                }

                // rotate the image
                $background = imagecolorallocatealpha($image, $bg_r, $bg_g, $bg_b, $bg_a);
                $image = imagerotate($image, $rotation, (int)$background);
                if (!$image) {
                    throw new \Exception('Cannot rotate image.');
                }

                // make sure width and/or height fits into old dimensions
                $this->resizeMaxWidth = intval($old_width);
                $this->resizeMaxHeight = intval($old_height);
                $image = self::resizeImage($image);
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
                if (imagecopy($new, $image, $x, $y, 0, 0, $new_width, $new_height)) {
                    throw new \Exception('Cannot copy rotated image to new image.');
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
     * Resize an image based on the maximum dimensions.
     */
    public function resizeImage(GdImage $image): GdImage|false
    {
        try {
            $old_width = imagesx($image);
            $old_height = imagesy($image);
            $max_width = $this->resizeMaxWidth;
            $max_height = $this->resizeMaxHeight;

            if ($max_width <= 0 || $max_height <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            $scale = min($max_width / $old_width, $max_height / $old_height);

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
    public function resizePngImage(GdImage $image): GdImage|false
    {
        try {
            $old_width = intval(imagesx($image));
            $old_height = intval(imagesy($image));
            $new_width = $this->resizeMaxWidth;
            $new_height = $this->resizeMaxHeight;

            if ($new_width <= 0 || $new_height <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            if ($this->keepAspectRatio) {
                $scale = min($new_width / $old_width, $new_height / $old_height);
                $new_width = intval(ceil($scale * $old_width));
                $new_height = intval(ceil($scale * $old_height));
            }
            $new = imagecreatetruecolor((int)$new_width, (int)$new_height);
            if (!$new) {
                throw new \Exception('Cannot create new image.');
            }

            imagealphablending($new, false);
            imagesavealpha($new, true);

            if ($this->keepAspectRatio) {
                if (!imagecopyresized($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
                    throw new \Exception('Cannot resize image.');
                }
            } else {
                if (!imagecopyresampled($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
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
    public function resizeCropImage(GdImage $source_file): GdImage
    {
        try {
            $old_width = intval(imagesx($source_file));
            $old_height = intval(imagesy($source_file));
            $max_width = $this->resizeMaxWidth;
            $max_height = $this->resizeMaxHeight;

            if ($max_width <= 0 || $max_height <= 0) {
                throw new \Exception('Invalid image maximum dimensions.');
            }

            $new_width = intval(($old_height * $max_width) / $max_height);
            $new_height = intval(($old_width * $max_height) / $max_width);

            settype($max_width, 'integer');
            settype($max_height, 'integer');

            $new = imagecreatetruecolor(intval($max_width), intval($max_height));
            if (!$new) {
                throw new \Exception('Cannot create new image.');
            }

            // If the new width is greater than the actual width of the image, then the height is too large and the rest is cut off, or vice versa
            if ($new_width > $old_width) {
                // Cut point by height
                $h_point = intval(($old_height - $new_height) / 2);
                // Copy image
                if (!imagecopyresampled($new, $source_file, 0, 0, 0, $h_point, $max_width, $max_height, $old_width, $new_height)) {
                    throw new \Exception('Cannot resize and crop image by height.');
                }
            } else {
                // Cut point by width
                $w_point = intval(($old_width - $new_width) / 2);
                if (!imagecopyresampled($new, $source_file, 0, 0, $w_point, 0, $max_width, $max_height, $new_width, $old_height)) {
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

            $this->resizeMaxWidth = $pic_width;
            $this->resizeMaxHeight = $pic_height;
            $frame = self::resizePngImage($frame);
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
            $fontSize = $this->fontSize;
            $fontRotation = $this->fontRotation;
            $fontLocationX = $this->fontLocationX;
            $fontLocationY = $this->fontLocationY;
            $fontPath = PathUtility::getAbsolutePath($this->fontPath);
            $textLineSpacing = $this->textLineSpacing;
            // Convert hex color string to RGB values
            $colorComponents = sscanf($this->fontColor, '#%02x%02x%02x');
            if ($colorComponents !== null) {
                list($r, $g, $b) = $colorComponents;
            } else {
                throw new \Exception('Font color: sscanf returned null!');
            }
            $r = intval($r);
            $g = intval($g);
            $b = intval($b);

            // Allocate color and set font
            $color = intval(imagecolorallocate($sourceResource, $r, $g, $b));

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
     * Set the picture options for adding a picture to image resource.
     */
    public function setAddPictureOptions(int $x, int $y, int $width, int $height, int $rotation): void
    {
        $this->addPictureX = $x;
        $this->addPictureY = $y;
        $this->addPictureWidth = $width;
        $this->addPictureHeight = $height;
        $this->addPictureRotation = $rotation;
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
                $this->resizeMaxWidth = $height;
                $this->resizeMaxHeight = $width;
                $imageResource = self::resizeCropImage($imageResource);
            } else {
                $this->resizeMaxWidth = $width;
                $this->resizeMaxHeight = $height;
                $imageResource = self::resizeCropImage($imageResource);
            }

            if ($this->addPictureApplyFrame) {
                $imageResource = self::applyFrame($imageResource);
            }

            if ($degrees != 0) {
                $backgroundColor = $this->addPictureBgColor;
                if (is_file($this->addPictureBgImage)) {
                    $backgroundColor = '#0000007f';
                }
                $this->resizeBgColor = $backgroundColor;
                $this->resizeRotation = $degrees;
                $imageResource = self::rotateResizeImage($imageResource);
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
                $colorComponents = sscanf($this->qrColor, '#%02x%02x%02x');
                if ($colorComponents !== null) {
                    list($r, $g, $b) = $colorComponents;
                } else {
                    throw new \Exception('QR color: sscanf returned null!');
                }
                $r = intval($r);
                $g = intval($g);
                $b = intval($b);

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
            // We create a new image
            $img = imagecreatetruecolor(imagesx($resource) + 25, imagesy($resource) + 80);
            if (!$img) {
                throw new \Exception('Cannot create new image.');
            }
            $white = intval(imagecolorallocate($img, 255, 255, 255));

            // We fill in the new white image
            if (!imagefill($img, 0, 0, $white)) {
                throw new \Exception('Cannot fill image.');
            }

            // We copy the image to which we want to apply the polariod effect in our new image.
            if (!imagecopy($img, $resource, 11, 11, 0, 0, imagesx($resource), imagesy($resource))) {
                unset($img);
                throw new \Exception('Cannot copy image.');
            }

            // Border color
            $color = intval(imagecolorallocate($img, 192, 192, 192));
            // We put a gray border to our image.
            if (!imagerectangle($img, 0, 0, imagesx($img) - 4, imagesy($img) - 4, $color)) {
                unset($img);
                throw new \Exception('Cannot add border.');
            }

            // Shade Colors
            $gris1 = intval(imagecolorallocate($img, 208, 208, 208));
            $gris2 = intval(imagecolorallocate($img, 224, 224, 224));
            $gris3 = intval(imagecolorallocate($img, 240, 240, 240));

            // We add a small shadow
            if (
                !imageline($img, 2, imagesy($img) - 3, imagesx($img) - 1, imagesy($img) - 3, $gris1) ||
                !imageline($img, 4, imagesy($img) - 2, imagesx($img) - 1, imagesy($img) - 2, $gris2) ||
                !imageline($img, 6, imagesy($img) - 1, imagesx($img) - 1, imagesy($img) - 1, $gris3) ||
                !imageline($img, imagesx($img) - 3, 2, imagesx($img) - 3, imagesy($img) - 4, $gris1) ||
                !imageline($img, imagesx($img) - 2, 4, imagesx($img) - 2, imagesy($img) - 4, $gris2) ||
                !imageline($img, imagesx($img) - 1, 6, imagesx($img) - 1, imagesy($img) - 4, $gris3)
            ) {
                unset($img);
                throw new \Exception('Cannot add shadow.');
            }

            // Convert hex color string to RGB values
            $colorComponents = sscanf($this->polaroidBgColor, '#%02x%02x%02x');
            if ($colorComponents !== null) {
                list($rbcc, $gbcc, $bbcc) = $colorComponents;
            } else {
                throw new \Exception('Polaroid color: sscanf returned null!');
            }
            $rbcc = intval($rbcc);
            $gbcc = intval($gbcc);
            $bbcc = intval($bbcc);

            // We rotate the image
            $background = intval(imagecolorallocate($img, $rbcc, $gbcc, $bbcc));
            $rotatedImg = imagerotate($img, $this->polaroidRotation, $background);

            if (!$rotatedImg) {
                throw new \Exception('Cannot rotate image.');
            }
        } catch (\Exception $e) {
            $this->addErrorData($e->getMessage());

            // Try to clear cache
            if (isset($img) && $img instanceof GdImage) {
                unset($img);
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
        unset($img);

        // We return the rotated image
        return $rotatedImg;
    }
}
