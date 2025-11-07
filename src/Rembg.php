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
            $logger->debug('Starting rembg background removal process.');

            // Save current image state before rembg processing
            $imageHandler->jpegQuality = 100;
            if (!$imageHandler->saveJpeg($imageResource, $vars['resultFile'])) {
                throw new \Exception('Failed to save image before rembg processing.');
            }

            // Resolve background path
            $backgroundPath = $rembgConfig['background'] ?? '';
            $backgroundPath = PathUtility::getAbsolutePath(ltrim($backgroundPath, '/'));

            if (!empty($backgroundPath) && file_exists($backgroundPath)) {
                $venvPath = PathUtility::getAbsolutePath('scripts/rembg_venv/bin/python3');
                $scriptPath = PathUtility::getAbsolutePath('scripts/rembg_processor.py');

                // Select Python executable
                if (file_exists($venvPath)) {
                    $pythonCmd = $venvPath;
                    $logger->debug('Using rembg venv python3', ['path' => $venvPath]);
                } else {
                    $pythonCmd = 'python3';
                    $logger->info('rembg venv not found, using system python3', ['venv_path' => $venvPath]);
                }

                // Build the command
                $cmd = sprintf(
                    '%s %s %s %s %s %s %s %s %s %s %s %s',
                    escapeshellcmd($pythonCmd),
                    escapeshellarg($scriptPath),
                    escapeshellarg($vars['resultFile']),
                    escapeshellarg($backgroundPath),
                    escapeshellarg($rembgConfig['model'] ?? 'u2net'),
                    ($rembgConfig['alpha_matting'] ?? false) ? '1' : '0',
                    escapeshellarg($rembgConfig['max_size'] ?? '1024'),
                    escapeshellarg($vars['resultFile']),
                    escapeshellarg($rembgConfig['alpha_matting_foreground_threshold'] ?? '240'),
                    escapeshellarg($rembgConfig['alpha_matting_background_threshold'] ?? '10'),
                    escapeshellarg($rembgConfig['alpha_matting_erode_size'] ?? '10'),
                    ($rembgConfig['post_processing'] ?? false) ? '1' : '0'
                );

                exec($cmd, $output, $returnValue);

                if ($returnValue !== 0) {
                    $logger->error('rembg processing failed', [
                        'cmd' => $cmd,
                        'output' => $output,
                        'returnValue' => $returnValue
                    ]);
                    $imageHandler->addErrorData('Warning: rembg processing failed.');
                } else {
                    $logger->debug('Background removal applied successfully', ['output' => $output]);

                    // Reload image resource after rembg processing
                    $imageResource = $imageHandler->createFromImage($vars['resultFile']);
                    if (!$imageResource) {
                        throw new \Exception('Error reloading image resource after rembg processing.');
                    }
                    $imageHandler->imageModified = true;
                }
            } else {
                $logger->debug('rembg enabled but no background image set or missing file.', [
                    'backgroundPath' => $backgroundPath
                ]);
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return [$imageHandler, $imageResource];
    }
}
