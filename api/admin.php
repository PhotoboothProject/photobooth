<?php

/** @var array $config */
/** @var array $defaultConfig */

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Enum\FolderEnum;
use Photobooth\Environment;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\ImageMetadataCacheService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\ArrayUtility;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

header('Content-Type: application/json');

$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$configurationService = ConfigurationService::getInstance();
$defaultConfig = $configurationService->getDefaultConfiguration();

$data = ArrayUtility::replaceBooleanValues($_POST);
$action = isset($data['type']) ? $data['type'] : null;

// Reset
if ($action === 'reset') {
    // First step in resetting the photobooth is always resetting the logs
    // This ensures we are able to write logmessages afterwards.
    $loggerService->addLogger('main');
    $loggerService->addLogger('synctodrive');
    $loggerService->addLogger('remotebuzzer');
    $loggerService->reset();

    $logger->debug(basename($_SERVER['PHP_SELF']));
    $resetOptions = [
        'remove_media' => (bool) ($data['reset']['remove_media'] ?? false),
        'remove_print_db' => (bool) ($data['reset']['remove_print_db'] ?? false),
        'remove_mail_db' => (bool) ($data['reset']['remove_mail_db'] ?? false),
        'remove_config' => (bool) ($data['reset']['remove_config'] ?? false),
    ];
    $logger->info('Resetting Photobooth.', $resetOptions);

    // Remove images, videos and database
    if ($resetOptions['remove_media']) {
        $logger->info('Remove media.');
        $imageFolders = [
            FolderEnum::IMAGES->absolute(),
            FolderEnum::KEYING->absolute(),
            FolderEnum::PRINT->absolute(),
            FolderEnum::QR->absolute(),
            FolderEnum::TEST->absolute(),
            FolderEnum::THUMBS->absolute(),
            FolderEnum::TEMP->absolute(),
        ];
        $filesystem = (new Filesystem());
        $finder = (new Finder())
            ->files()
            ->in($imageFolders)
            ->name(['*.jpg', '*.mp4', '*.gif']);
        foreach ($finder as $file) {
            $logger->info($file->getRealPath() . ' deleted.');
            $filesystem->remove($file->getRealPath());
        }

        // delete db.txt
        $database = DatabaseManagerService::getInstance();
        if (is_file($database->databaseFile)) {
            // delete file
            unlink($database->databaseFile);
            $logger->debug($database->databaseFile . ' deleted.');
        }

        // Clear gallery image metadata cache as media has been removed
        ImageMetadataCacheService::getInstance()->clear();
        $logger->debug('Image metadata cache cleared.');
    }

    // Remove print database
    if ($resetOptions['remove_print_db']) {
        $logger->info('Remove print database.');
        $printManager = PrintManagerService::getInstance();
        if ($printManager->removePrintDb()) {
            $logger->info('printed.csv deleted.');
        }
        if ($printManager->unlockPrint()) {
            $logger->info('print.lock deleted.');
        }
        if ($printManager->removePrintCounter()) {
            $logger->info('print.count deleted.');
        }
    }

    // Remove mail database
    if ($resetOptions['remove_mail_db']) {
        $logger->info('Remove mail database.');
        $mailService = MailService::getInstance();
        $mailService->resetDatabase();
    }

    // Remove personal config
    if ($resetOptions['remove_config']) {
        $logger->info('Remove "config/my.config.inc.php".');
        if (is_file(PathUtility::getAbsolutePath('config/my.config.inc.php'))) {
            unlink(PathUtility::getAbsolutePath('config/my.config.inc.php'));
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Photobooth resetted.'
    ]);
} elseif ($action === 'config') {
    $logger->debug('Saving Photobooth configuration...');
    $newConfig = ArrayUtility::mergeRecursive($defaultConfig, $data);

    $rootPath = PathUtility::getRootPath();

    $normalizePath = static function (?string $path) use ($rootPath): ?string {
        if ($path === null || $path === '') {
            return $path;
        }

        // Strip installation root from absolute filesystem paths written by the file chooser
        if ($rootPath !== '' && str_starts_with($path, $rootPath)) {
            $path = substr($path, strlen($rootPath));
        }

        // Trim leading slashes so we store project-relative paths like
        // "private/..." or "resources/..." instead of "/private/...".
        return ltrim($path, '/');
    };

    // Logo and UI images
    $newConfig['logo']['path']             = $normalizePath($newConfig['logo']['path'] ?? null);
    $newConfig['ui']['shutter_cheese_img'] = $normalizePath($newConfig['ui']['shutter_cheese_img'] ?? null);

    // Frames and backgrounds which may be selected via image picker
    $newConfig['picture']['frame']       = $normalizePath($newConfig['picture']['frame'] ?? null);
    $newConfig['collage']['frame']       = $normalizePath($newConfig['collage']['frame'] ?? null);
    $newConfig['background']['defaults'] = $normalizePath($newConfig['background']['defaults'] ?? null);
    $newConfig['background']['admin']    = $normalizePath($newConfig['background']['admin'] ?? null);
    $newConfig['background']['chroma']   = $normalizePath($newConfig['background']['chroma'] ?? null);
    $newConfig['collage']['placeholderpath'] = $normalizePath($newConfig['collage']['placeholderpath'] ?? null);

    // Fonts selected via font picker
    $newConfig['textonpicture']['font'] = $normalizePath($newConfig['textonpicture']['font'] ?? null);
    $newConfig['textoncollage']['font'] = $normalizePath($newConfig['textoncollage']['font'] ?? null);
    $newConfig['textonprint']['font']   = $normalizePath($newConfig['textonprint']['font'] ?? null);
    $newConfig['print']['frame']        = $normalizePath($newConfig['print']['frame'] ?? null);

    if (isset($newConfig['login']['enabled']) && $newConfig['login']['enabled'] == true) {
        if ((isset($newConfig['login']['password']) && !empty($newConfig['login']['password'])) || $newConfig['login']['keypad']) {
            if ($newConfig['login']['keypad'] && strlen($newConfig['login']['pin']) != 4) {
                $logger->debug('Keypad pin reset.');
                $logger->debug('Length: ' . strlen($newConfig['login']['pin']) . ' Expected length: 4', $newConfig['login']);
                $newConfig['login']['enabled'] = false;
                $newConfig['login']['keypad'] = false;
                $newConfig['login']['pin'] = '';
            }
            if (isset($newConfig['login']['password']) && !empty($newConfig['login']['password'])) {
                // allow login via password, but we might have disabled because the PIN length did not match our requirements
                $newConfig['login']['enabled'] = true;
                if ($newConfig['login']['password'] != $config['login']['password']) {
                    $hashing = password_hash($newConfig['login']['password'], PASSWORD_DEFAULT);
                    $newConfig['login']['password'] = $hashing;
                }
            }
        } else {
            $newConfig['login']['enabled'] = false;
            $newConfig['login']['keypad'] = false;
            $newConfig['login']['pin'] = '';
            $logger->debug('Password not set. Login disabled.', $newConfig['login']);
        }
    } else {
        $newConfig['login']['password'] = null;
        $newConfig['login']['keypad'] = false;
        $newConfig['login']['pin'] = '';
    }

    if (isset($newConfig['login']['rental_keypad']) && $newConfig['login']['rental_keypad'] == true) {
        if (strlen($newConfig['login']['rental_pin']) != 4 || $newConfig['login']['rental_pin'] === $newConfig['login']['pin']) {
            $logger->debug('Rental keypad pin reset.', $newConfig['login']);
            $logger->debug('Length: ' . strlen($newConfig['login']['rental_pin']) . ' Expected length: 4', $newConfig['login']);
            if ($newConfig['login']['rental_pin'] === $newConfig['login']['pin']) {
                $logger->debug('Rental keypad pin must be different from login pin.', $newConfig['login']);
            }
            $newConfig['login']['rental_keypad'] = false;
            $newConfig['login']['rental_pin'] = '';
        }
    } else {
        $newConfig['login']['rental_pin'] = '';
    }

    if (isset($newConfig['filters']['enabled']) && $newConfig['filters']['enabled'] == true) {
        if (isset($newConfig['picture']['keep_original']) && !$newConfig['picture']['keep_original']) {
            $newConfig['filters']['enabled'] = false;
            $logger->debug('Filters disabled, you must keep original images in tmp folder to use this function.', [$newConfig['filters'], $newConfig['picture']]);
        }
    }

    if (isset($newConfig['filters']['disabled']) && $newConfig['filters']['disabled'] == false) {
        $newConfig['filters']['disabled'] = [];
    }

    if (isset($newConfig['commands']['preview']) && !empty($newConfig['commands']['preview'])) {
        if (strpos($newConfig['commands']['preview'], 'cameracontrol') !== false) {
            $newConfig['preview']['bsm'] = strpos($newConfig['commands']['preview'], '--bsm') !== false;
        }
    }

    if ($newConfig['preview']['camTakesPic'] && $newConfig['preview']['mode'] != 'device_cam' && $newConfig['preview']['mode'] != 'gphoto') {
        $newConfig['preview']['camTakesPic'] = false;
        $logger->debug('Device cam takes picture disabled. Can take images from preview only from gphoto2 and device cam preview.');
    }

    if (Environment::isWindows()) {
        if (!empty($newConfig['remotebuzzer']['enabled'])) {
            $newConfig['remotebuzzer']['enabled'] = false;
            $logger->debug('Remotebuzzer server unsupported on Windows.');
        }
        if (!empty($newConfig['synctodrive']['enabled'])) {
            $newConfig['synctodrive']['enabled'] = false;
            $logger->debug('Sync pictures to USB stick unsupported on Windows.');
        }
    }

    if (isset($newConfig['remotebuzzer']['port']) && empty($newConfig['remotebuzzer']['port'])) {
        $newConfig['remotebuzzer']['port'] = 14711;
    }

    if (isset($newConfig['database']['file']) && empty($newConfig['database']['file'])) {
        $newConfig['database']['file'] = 'db';
    }

    if (isset($newConfig['mail']['file']) && empty($newConfig['mail']['file'])) {
        $newConfig['mail']['file'] = 'mail-adresses';
    }

    if ($newConfig['get_request']['countdown'] || $newConfig['get_request']['processed']) {
        if (isset($newConfig['get_request']['server']) && empty($newConfig['get_request']['server'])) {
            $newConfig['get_request']['countdown'] = false;
            $newConfig['get_request']['processed'] = false;
            $logger->debug('No GET request server entered. Disabled GET request options.');
        }
    }

    // Collage json config
    $newConfig['collage']['limit'] = $newConfig['collage']['limit'] ?? $defaultConfig['collage']['limit'];
    if ($newConfig['collage']['enabled']) {
        $collageConfigFilePath = Collage::getCollageConfigPath($newConfig['collage']['layout'], $newConfig['collage']['orientation']);

        if ($collageConfigFilePath !== null) {
            $collageJson = json_decode((string)file_get_contents($collageConfigFilePath), true);

            if (is_array($collageJson)) {
                if (isset($collageJson['layout']) && !empty($collageJson['layout'])) {
                    $layoutConfigArray = $collageJson['layout'];

                    if (array_key_exists('placeholder', $collageJson)) {
                        $newConfig['collage']['placeholder'] = $collageJson['placeholder'];
                    }
                    if (array_key_exists('placeholderposition', $collageJson)) {
                        $newConfig['collage']['placeholderposition'] = $collageJson['placeholderposition'];
                    }
                    if (array_key_exists('placeholderpath', $collageJson)) {
                        $newConfig['collage']['placeholderpath'] = $collageJson['placeholderpath'];
                    }
                } else {
                    $layoutConfigArray = $collageJson;
                }

                // Calculate collage limit
                if (str_starts_with($newConfig['collage']['layout'], '2x')) {
                    $newConfig['collage']['limit'] = (int) ceil(count($layoutConfigArray) / 2);
                } else {
                    $newConfig['collage']['limit'] = count($layoutConfigArray);
                }

                // If there is a collage placeholder whithin the correct range (0 < placeholderposition <= collage limit), we need to decrease the collage limit by 1
                if ($newConfig['collage']['placeholder']) {
                    $collagePlaceholderPosition = (int) $newConfig['collage']['placeholderposition'];
                    if ($collagePlaceholderPosition > 0 && $collagePlaceholderPosition <= $newConfig['collage']['limit']) {
                        $newConfig['collage']['limit'] = $newConfig['collage']['limit'] - 1;
                    } else {
                        $newConfig['collage']['placeholder'] = false;
                        $logger->debug('Placeholder position not in range. Placeholder disabled.');
                    }

                    if ($newConfig['collage']['placeholderpath'] === '') {
                        $newConfig['collage']['placeholder'] = false;
                        $logger->debug('Collage Placeholder is empty. Collage Placeholder disabled.');
                    }
                }
            } else {
                $newConfig['collage']['enabled'] = false;
                $logger->debug('No valid collage json found. Collage disabled.');
            }
        }
        if ($newConfig['collage']['limit'] < 1) {
            $newConfig['collage']['enabled'] = false;
            $newConfig['collage']['limit'] = $defaultConfig['collage']['limit'];
            $logger->debug('Invalid collage limit, must be 1 or greater. Collage disabled.');
        }
    }

    if ($newConfig['picture']['take_frame'] && $newConfig['picture']['frame'] === '') {
        $newConfig['picture']['take_frame'] = false;
        $logger->debug('Picture frame empty. Disabled picture frame.');
    }

    if ($newConfig['collage']['take_frame'] && $newConfig['collage']['frame'] === '') {
        $newConfig['collage']['take_frame'] = false;
        $logger->debug('Collage frame empty. Disabled collage frame.');
    }

    if ($newConfig['print']['print_frame'] && $newConfig['print']['frame'] === '') {
        $newConfig['print']['print_frame'] = false;
        $logger->debug('Print frame empty. Disabled frame on print.');
    }

    if ($newConfig['textonpicture']['enabled'] && $newConfig['textonpicture']['font'] === '') {
        $newConfig['textonpicture']['enabled'] = false;
        $logger->debug('Picture font is empty. Disabled text on picture.');
    }

    if ($newConfig['textoncollage']['enabled'] && $newConfig['textoncollage']['font'] === '') {
        $newConfig['textoncollage']['enabled'] = false;
        $logger->debug('Collage font is empty. Disabled text on picture.');
    }

    if ($newConfig['textonprint']['enabled'] && $newConfig['textonprint']['font'] === '') {
        $newConfig['textonprint']['enabled'] = false;
        $logger->debug('Print font is empty. Disabled text on print.');
    }

    if ($newConfig['logo']['enabled']) {
        $logoPath = $newConfig['logo']['path'];

        if (empty($logoPath)) {
            $newConfig['logo']['enabled'] = false;
            $logger->debug('Logo path empty. Logo disabled.', $newConfig['logo']);
        } else {
            try {
                $absoluteLogoPath = PathUtility::resolveFilePath($logoPath);
            } catch (\Exception $e) {
                $newConfig['logo']['enabled'] = false;
                $logger->debug('Logo file path does not exist or is not readable. Logo disabled.', [
                    'logo'  => $newConfig['logo'],
                    'error' => $e->getMessage(),
                ]);
                $absoluteLogoPath = null;
            }

            if ($absoluteLogoPath !== null) {
                $newConfig['logo']['path'] = PathUtility::fixFilePath($logoPath);
                $ext                       = pathinfo($absoluteLogoPath, PATHINFO_EXTENSION);

                if ($ext === 'svg') {
                    $logger->debug('Logo file is SVG, path saved.', $newConfig['logo']);
                } else {
                    $imageInfo = @getimagesize($absoluteLogoPath);
                    if ($imageInfo === false) {
                        $newConfig['logo']['enabled'] = false;
                        $logger->debug(
                            'Logo file is not a supported image type [' . $ext . ']. Logo disabled.',
                            $newConfig['logo'],
                        );
                    } else {
                        $logger->debug('Logo file is a supported image type [' . $ext . '], path saved.', $newConfig['logo']);
                    }
                }
            }
        }
    }

    try {
        $configurationService->update($newConfig);
        $logger->debug('New config saved.');
        echo json_encode([
            'status' => 'success',
            'message' => 'New config saved.',
        ]);
    } catch (\Exception $exception) {
        $logger->error('ERROR: Config can not be saved!');
        echo json_encode([
            'status' => 'error',
            'message' => $exception->getMessage(),
        ]);
    }
} else {
    $logger->error('ERROR: Unknown action.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unknown action.',
    ]);
    die();
}

// Kill service daemons after config has changed
ProcessService::getInstance()->shutdown();
exit();
