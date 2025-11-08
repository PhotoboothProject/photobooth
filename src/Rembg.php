<?php

namespace Photobooth;

use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\PathUtility;

class Rembg
{
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
            return [$imageHandler, $imageResource];
        }
        try {
            $logger->debug('Starting rembg background removal process via service.');

            // Save current image state before rembg processing
            $imageHandler->jpegQuality = 100;
            if (!$imageHandler->saveJpeg($imageResource, $vars['resultFile'])) {
                throw new \Exception('Failed to save image before rembg processing.');
            }

            // Use rembg service
            $serviceUrl = 'http://localhost:7000/api/remove';

            // Prepare the curl request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $serviceUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Prepare file upload
            $cfile = new \CURLFile($vars['resultFile'], 'image/jpeg', 'image.jpg');
            $postData = ['file' => $cfile];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                $logger->error('rembg service request failed', [
                    'httpCode' => $httpCode,
                    'error' => $error,
                    'response' => $response
                ]);
                $imageHandler->addErrorData('Warning: rembg service request failed.');
            } else {
                // Save the response to the result file
                if (file_put_contents($vars['resultFile'], $response) === false) {
                    throw new \Exception('Failed to save processed image from rembg service.');
                }

                $logger->debug('Background removal applied successfully via service');

                // Reload image resource after rembg processing
                $imageResource = $imageHandler->createFromImage($vars['resultFile']);
                if (!$imageResource) {
                    throw new \Exception('Error reloading image resource after rembg processing.');
                }
                assert($imageResource instanceof \GdImage);

                // Apply background if configured
                $backgroundPath = $rembgConfig['background'] ?? '';
                $backgroundPath = PathUtility::getAbsolutePath(ltrim($backgroundPath, '/'));
                if (!empty($backgroundPath) && file_exists($backgroundPath)) {
                    $backgroundImage = $imageHandler->createFromImage($backgroundPath);
                    if ($backgroundImage) {
                        // Resize background to match the processed image
                        $backgroundImage = imagescale($backgroundImage, imagesx($imageResource), imagesy($imageResource));
                        if ($backgroundImage) {
                            // Composite the background
                            imagecopy($imageResource, $backgroundImage, 0, 0, 0, 0, imagesx($imageResource), imagesy($imageResource));
                            imagedestroy($backgroundImage);
                            $logger->debug('Background image applied after rembg processing');
                        }
                    }
                }

                $imageHandler->imageModified = true;
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return [$imageHandler, $imageResource];
    }
}
