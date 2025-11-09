<?php

namespace Photobooth;

use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\PathUtility;

class Rembg
{
    private static function log(string $message, string $level = 'INFO'): void
    {
        $logFile = PathUtility::getAbsolutePath('var/log/rembg.log');
        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        $logEntry = "[$timestamp][rembg][$level] $pid: $message\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    public static function process(
        Image $imageHandler,
        array $vars,
        array $rembgConfig,
        \GdImage $imageResource,
        NamedLogger $logger
    ): array {
        // Only process if rembg is enabled and not in collage/chroma mode
        if (
            empty($rembgConfig['enabled']) ||
            !empty($vars['isCollage']) ||
            !empty($vars['isChroma'])
        ) {
            self::log('Skipped (disabled or collage/chroma mode)');
            return [$imageHandler, $imageResource];
        }

        self::log('Starting background removal process via service');
        $logger->debug('Starting rembg background removal process via service.');

        // Prepare temporary files
        $tempInput = tempnam(sys_get_temp_dir(), 'rembg_input_') . '.png';
        $tempOutput = tempnam(sys_get_temp_dir(), 'rembg_output_') . '.png';

        try {
            // Save image for upload
            if (!imagepng($imageResource, $tempInput)) {
                throw new \Exception('Failed to save input image');
            }
            $logger->debug('Rembg: Input image saved to ' . $tempInput);

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
            self::log("Image sent to API: $apiUrl with parameters: $paramString");
            $logger->debug('Rembg: Sending image to API', ['url' => $apiUrl, 'params' => $queryParams]);

            // cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new \CURLFile($tempInput, 'image/png', 'input.png')
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Log: Image returned + processing success/failure
            if ($error) {
                self::log("Image processing failed: cURL error - $error", 'ERROR');
                throw new \Exception('cURL error: ' . $error);
            }
            if ($httpCode !== 200) {
                self::log("Image processing failed: HTTP $httpCode - Response: " . substr($response, 0, 200), 'ERROR');
                throw new \Exception('API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $response);
            finfo_close($finfo);
            if (!str_starts_with($mimeType, 'image/')) {
                self::log("Invalid response: Expected image, got $mimeType - Response: " . substr($response, 0, 200), 'ERROR');
                throw new \Exception('Invalid API response: not an image');
            }

            self::log("Image successfully processed and returned from API (HTTP 200, MIME: $mimeType)");
            $logger->debug('Rembg: API response received', ['httpCode' => $httpCode, 'mimeType' => $mimeType]);

            // Save response as image
            if (file_put_contents($tempOutput, $response) === false) {
                throw new \Exception('Failed to save output image');
            }
            $logger->debug('Rembg: Output image saved to ' . $tempOutput);

            // Load processed image
            $processedImage = imagecreatefrompng($tempOutput);
            if (!$processedImage) {
                throw new \Exception('Failed to load processed image');
            }

            // Apply background if configured
            if (!empty($rembgConfig['background'])) {
                $backgroundPath = PathUtility::getAbsolutePath($rembgConfig['background']);
                if (file_exists($backgroundPath)) {
                    $backgroundImage = imagecreatefromstring(file_get_contents($backgroundPath));
                    if ($backgroundImage) {
                        $newImage = imagecreatetruecolor(imagesx($processedImage), imagesy($processedImage));
                        imagecopy($newImage, $backgroundImage, 0, 0, 0, 0, imagesx($processedImage), imagesy($processedImage));
                        imagecopy($newImage, $processedImage, 0, 0, 0, 0, imagesx($processedImage), imagesy($processedImage));
                        imagedestroy($processedImage);
                        $processedImage = $newImage;
                        self::log('Background image applied after rembg processing');
                        $logger->debug('Background image applied after rembg processing');
                    }
                }
            }

            self::log('Background removal applied successfully via service');
            $logger->debug('Background removal applied successfully via service');

            // Cleanup
            unlink($tempInput);
            unlink($tempOutput);

            return [$imageHandler, $processedImage];

        } catch (\Exception $e) {
            self::log('Processing failed: ' . $e->getMessage(), 'ERROR');
            $logger->error('Rembg processing failed', ['error' => $e->getMessage()]);
            if (file_exists($tempInput)) {
                unlink($tempInput);
            }
            if (file_exists($tempOutput)) {
                unlink($tempOutput);
            }
            return [$imageHandler, $imageResource]; // Fallback to original image
        }
    }
}
