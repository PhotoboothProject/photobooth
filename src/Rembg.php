<?php

namespace Photobooth;

use Photobooth\Service\LoggerService;
use Photobooth\Utility\PathUtility;

class Rembg
{
    public static function process(
        Image $imageHandler,
        array $vars,
        array $rembgConfig,
        \GdImage $imageResource
    ): array {
        $logger = LoggerService::getInstance()->getLogger('rembg');
        // Only process if rembg is enabled and not in collage/chroma mode
        if (
            empty($rembgConfig['enabled']) ||
            !empty($vars['isCollage']) ||
            !empty($vars['isChroma'])
        ) {
            $logger->debug('Skipped (disabled or collage/chroma mode)');
            return [$imageHandler, $imageResource];
        }

        $logger->debug('Starting background removal process via service');

        // Prepare temporary files
        $tempInput = tempnam(sys_get_temp_dir(), 'rembg_input_') . '.png';
        $tempOutput = tempnam(sys_get_temp_dir(), 'rembg_output_') . '.png';

        try {
            // Save image for upload
            if (!imagepng($imageResource, $tempInput)) {
                throw new \Exception('Failed to save input image');
            }

            // Prepare API URL and parameters
            $apiUrl = 'http://localhost:7000/api/remove';
            $queryParams = [];
            if (!empty($rembgConfig['model'])) {
                $queryParams['model'] = $rembgConfig['model'];
            }
            if (!empty($rembgConfig['alpha_matting'])) {
                $queryParams['a'] = 'true';
                if (!empty($rembgConfig['alpha_matting_background_threshold'])) {
                    $queryParams['ab'] = $rembgConfig['alpha_matting_background_threshold'];
                }
                if (!empty($rembgConfig['alpha_matting_erode_size'])) {
                    $queryParams['ae'] = $rembgConfig['alpha_matting_erode_size'];
                }
                if (!empty($rembgConfig['alpha_matting_foreground_threshold'])) {
                    $queryParams['af'] = $rembgConfig['alpha_matting_foreground_threshold'];
                }
            }
            if (!empty($rembgConfig['post_processing'])) {
                $queryParams['ppm'] = 'true';
            }

            // Build query string
            if (!empty($queryParams)) {
                $apiUrl .= '?' . http_build_query($queryParams);
            }

            // Log: Image sent to API + parameters
            $paramString = json_encode($queryParams);
            $logger->debug("Image sent to API: $apiUrl with parameters: $paramString");

            // cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new \CURLFile($tempInput, 'image/png', 'input.png')
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Log: Image returned + processing success/failure
            if ($error) {
                $logger->error("Image processing failed: cURL error - $error");
                throw new \Exception('cURL error: ' . $error);
            }
            if ($httpCode !== 200) {
                $responsePreview = is_string($response) ? substr($response, 0, 200) : 'no response';
                $logger->error("Image processing failed: HTTP $httpCode - Response: " . $responsePreview);
                throw new \Exception('API error: HTTP ' . $httpCode . ' - ' . $responsePreview);
            }

            // Check MIME type
            if (!is_string($response)) {
                throw new \Exception('Invalid API response: expected string');
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                throw new \Exception('Failed to initialize fileinfo');
            }
            $mimeType = finfo_buffer($finfo, $response);
            finfo_close($finfo);
            if ($mimeType === false || !str_starts_with($mimeType, 'image/')) {
                $mimeDisplay = $mimeType !== false ? $mimeType : 'unknown';
                $logger->error("Invalid response: Expected image, got $mimeDisplay - Response: " . substr($response, 0, 200));
                throw new \Exception('Invalid API response: not an image');
            }

            $logger->debug("Image successfully processed and returned from API (HTTP 200, MIME: $mimeType)");

            // Save response as image
            if (file_put_contents($tempOutput, $response) === false) {
                throw new \Exception('Failed to save output image');
            }

            // Load processed image
            $processedImage = imagecreatefrompng($tempOutput);
            if ($processedImage === false) {
                throw new \Exception('Failed to load processed image');
            }

            // Apply background if configured
            if (!empty($rembgConfig['background'])) {
                $backgroundPath = PathUtility::getAbsolutePath($rembgConfig['background']);
                if (file_exists($backgroundPath)) {
                    $backgroundContent = file_get_contents($backgroundPath);
                    if ($backgroundContent === false) {
                        $logger->error('Failed to read background image file');
                    } else {
                        $backgroundImage = imagecreatefromstring($backgroundContent);
                        $backgroundImage = imagecreatefromstring($backgroundContent);
                        if ($backgroundImage !== false) {
                            $backgroundMode = $rembgConfig['backgroundMode'] ?? 'scale-fill';
                            $newImage = self::applyBackgroundWithMode(
                                $processedImage,
                                $backgroundImage,
                                $backgroundMode,
                                $logger
                            );
                            imagedestroy($processedImage);
                            imagedestroy($backgroundImage);
                            $processedImage = $newImage;
                            $logger->debug('Background image applied after rembg processing');
                        }
                    }
                }
            }

            $logger->debug('Background removal applied successfully via service');

            // Cleanup
            unlink($tempInput);
            unlink($tempOutput);

            return [$imageHandler, $processedImage];

        } catch (\Exception $e) {
            $logger->error('Processing failed: ' . $e->getMessage());
            if (file_exists($tempInput)) {
                unlink($tempInput);
            }
            if (file_exists($tempOutput)) {
                unlink($tempOutput);
            }
            return [$imageHandler, $imageResource]; // Fallback to original image
        }
    }

    /**
     * Apply background image with different scaling/cropping modes
     *
     * @param \GdImage $foreground The transparent foreground image
     * @param \GdImage $background The background image
     * @param string $mode The mode: 'none', 'scale-fit', 'scale-fill', 'crop-center', 'stretch'
     * @param \Photobooth\Logger\NamedLogger $logger Logger instance
     * @return \GdImage The composited image
     */
    private static function applyBackgroundWithMode(
        \GdImage $foreground,
        \GdImage $background,
        string $mode,
        \Photobooth\Logger\NamedLogger $logger
    ): \GdImage {
        $canvasWidth = imagesx($foreground);
        $canvasHeight = imagesy($foreground);
        $bgWidth = imagesx($background);
        $bgHeight = imagesy($background);

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

        $logger->debug("Applying background with mode: {$mode} (Canvas: {$canvasWidth}x{$canvasHeight}, BG: {$bgWidth}x{$bgHeight})");

        switch ($mode) {
            case 'none':
                // Original behavior: direct copy, no scaling
                imagecopy($canvas, $background, 0, 0, 0, 0, $bgWidth, $bgHeight);
                break;

            case 'scale-fit':
                // Scale background to fit inside canvas (preserve aspect ratio, may have black bars)
                $scale = min($canvasWidth / $bgWidth, $canvasHeight / $bgHeight);
                $scaledWidth = (int)($bgWidth * $scale);
                $scaledHeight = (int)($bgHeight * $scale);
                $offsetX = (int)(($canvasWidth - $scaledWidth) / 2);
                $offsetY = (int)(($canvasHeight - $scaledHeight) / 2);

                $black = imagecolorallocate($canvas, 0, 0, 0);
                if ($black !== false) {
                    imagefill($canvas, 0, 0, $black);
                }
                imagecopyresampled(
                    $canvas,
                    $background,
                    $offsetX,
                    $offsetY,
                    0,
                    0,
                    $scaledWidth,
                    $scaledHeight,
                    $bgWidth,
                    $bgHeight
                );
                break;

            case 'scale-fill':
                // Scale background to cover entire canvas (preserve aspect ratio, may crop)
                $scale = max($canvasWidth / $bgWidth, $canvasHeight / $bgHeight);
                $scaledWidth = (int)($bgWidth * $scale);
                $scaledHeight = (int)($bgHeight * $scale);
                $offsetX = (int)(($canvasWidth - $scaledWidth) / 2);
                $offsetY = (int)(($canvasHeight - $scaledHeight) / 2);

                imagecopyresampled(
                    $canvas,
                    $background,
                    $offsetX,
                    $offsetY,
                    0,
                    0,
                    $scaledWidth,
                    $scaledHeight,
                    $bgWidth,
                    $bgHeight
                );
                break;

            case 'crop-center':
                // Crop background from center to match canvas size
                if ($bgWidth < $canvasWidth || $bgHeight < $canvasHeight) {
                    // Background too small, scale up first
                    $scale = max($canvasWidth / $bgWidth, $canvasHeight / $bgHeight);
                    $scaledBg = imagecreatetruecolor((int)($bgWidth * $scale), (int)($bgHeight * $scale));
                    imagecopyresampled($scaledBg, $background, 0, 0, 0, 0, imagesx($scaledBg), imagesy($scaledBg), $bgWidth, $bgHeight);
                    $cropX = (int)((imagesx($scaledBg) - $canvasWidth) / 2);
                    $cropY = (int)((imagesy($scaledBg) - $canvasHeight) / 2);
                    imagecopy($canvas, $scaledBg, 0, 0, $cropX, $cropY, $canvasWidth, $canvasHeight);
                    imagedestroy($scaledBg);
                } else {
                    $cropX = (int)(($bgWidth - $canvasWidth) / 2);
                    $cropY = (int)(($bgHeight - $canvasHeight) / 2);
                    imagecopy($canvas, $background, 0, 0, $cropX, $cropY, $canvasWidth, $canvasHeight);
                }
                break;

            case 'stretch':
            default:
                // Stretch background to exact canvas size (may distort)
                imagecopyresampled($canvas, $background, 0, 0, 0, 0, $canvasWidth, $canvasHeight, $bgWidth, $bgHeight);
                break;
        }

        // Merge transparent foreground onto background
        imagecopy($canvas, $foreground, 0, 0, 0, 0, $canvasWidth, $canvasHeight);

        return $canvas;
    }
}
