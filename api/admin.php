<?php

/** @var array $config */
/** @var array $defaultConfig */

require_once __DIR__ . '/../admin/admin_boot.php';

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
use Photobooth\Utility\AdminKeypad;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

header('Content-Type: application/json');
$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

checkCsrfOrFail($_POST);

$configurationService = ConfigurationService::getInstance();
$defaultConfig = $configurationService->getDefaultConfiguration();

$data = ArrayUtility::replaceBooleanValues($_POST);
$action = $data['type'] ?? null;

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
    $newConfig['screensaver']['image_source']   = $normalizePath($newConfig['screensaver']['image_source'] ?? null);
    $newConfig['screensaver']['video_source']   = $normalizePath($newConfig['screensaver']['video_source'] ?? null);
    if (isset($newConfig['screensaver']['switch_seconds'])) {
        $newConfig['screensaver']['switch_seconds'] = (int)$newConfig['screensaver']['switch_seconds'];
    }
    if (isset($newConfig['screensaver']['timeout_minutes'])) {
        $newConfig['screensaver']['timeout_minutes'] = (int)$newConfig['screensaver']['timeout_minutes'];
    }
    if (isset($newConfig['screensaver']['text_backdrop_opacity'])) {
        $newConfig['screensaver']['text_backdrop_opacity'] = (float)$newConfig['screensaver']['text_backdrop_opacity'];
    }

    // Fonts selected via font picker
    $newConfig['textonpicture']['font'] = $normalizePath($newConfig['textonpicture']['font'] ?? null);
    $newConfig['textoncollage']['font'] = $normalizePath($newConfig['textoncollage']['font'] ?? null);
    $newConfig['textonprint']['font']   = $normalizePath($newConfig['textonprint']['font'] ?? null);
    $newConfig['print']['frame']        = $normalizePath($newConfig['print']['frame'] ?? null);

    $keepExistingSecret = static function (string $key, ?string $current, array $config): ?string {
        if (($current ?? '') === '' && isset($config['login'][$key])) {
            return $config['login'][$key];
        }
        return $current;
    };

    $newConfig['login']['password']   = $keepExistingSecret('password', $newConfig['login']['password'] ?? null, $config);
    $newConfig['login']['pin']        = $keepExistingSecret('pin', $newConfig['login']['pin'] ?? null, $config);
    $newConfig['login']['rental_pin'] = $keepExistingSecret('rental_pin', $newConfig['login']['rental_pin'] ?? null, $config);

    // Hash password early when a new value is provided
    if (!empty($newConfig['login']['password']) && $newConfig['login']['password'] !== ($config['login']['password'] ?? null)) {
        $newConfig['login']['password'] = password_hash($newConfig['login']['password'], PASSWORD_DEFAULT);
    }

    $loginEnabled      = !empty($newConfig['login']['enabled']);
    $loginKeypad       = !empty($newConfig['login']['keypad']);
    $rentalKeypad      = !empty($newConfig['login']['rental_keypad']);
    $loginPinIsHashed  = AdminKeypad::isHashedPin($newConfig['login']['pin'] ?? null);
    $rentalPinIsHashed = AdminKeypad::isHashedPin($newConfig['login']['rental_pin'] ?? null);

    if ($loginEnabled) {
        $hasPassword = !empty($newConfig['login']['password']);
        $hasKeypad   = $loginKeypad;

        if ($hasKeypad && !$loginPinIsHashed && strlen($newConfig['login']['pin']) !== 4) {
            $logger->debug('Keypad pin invalid; disabling keypad.', $newConfig['login']);
            $newConfig['login']['keypad'] = false;
            $hasKeypad = false;
        }

        if (!$hasPassword && !$hasKeypad) {
            $newConfig['login']['enabled'] = false;
            $logger->debug('Password and keypad missing. Login disabled.', $newConfig['login']);
        }
    } else {
        $newConfig['login']['keypad'] = false;
    }

    // Normalize screensaver boolean values (checkbox submits strings)
    if (isset($newConfig['screensaver']['enabled'])) {
        $newConfig['screensaver']['enabled'] = filter_var($newConfig['screensaver']['enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    if ($rentalKeypad) {
        $rentalPin = $newConfig['login']['rental_pin'] ?? '';
        if ((!$rentalPinIsHashed && strlen($rentalPin) !== 4) || $rentalPin === ($newConfig['login']['pin'] ?? null)) {
            $logger->debug('Rental keypad pin invalid; disabling rental keypad.', $newConfig['login']);
            $newConfig['login']['rental_keypad'] = false;
            $newConfig['login']['rental_pin'] = '';
        }
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
        $limitData = Collage::calculateLimit($newConfig['collage'], $logger);
        $newConfig['collage']['limit'] = $limitData['limit'];
        $newConfig['collage']['placeholder'] = $limitData['placeholderEnabled'];
        if ($newConfig['collage']['limit'] < 1) {
            $newConfig['collage']['enabled'] = false;
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

    // Hash password if a plain value slipped through (e.g., kept existing value while login disabled)
    if (!empty($newConfig['login']['password'])) {
        $passwordInfo = password_get_info($newConfig['login']['password']);
        if (($passwordInfo['algo'] ?? 0) === 0) {
            $newConfig['login']['password'] = password_hash($newConfig['login']['password'], PASSWORD_DEFAULT);
        }
    }

    // Hash PINs just before save to ensure storage never keeps plain values
    foreach (['pin', 'rental_pin'] as $pinField) {
        if (!empty($newConfig['login'][$pinField]) && !AdminKeypad::isHashedPin($newConfig['login'][$pinField])) {
            $newConfig['login'][$pinField] = password_hash($newConfig['login'][$pinField], PASSWORD_DEFAULT);
        }
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
        $logger->error('ERROR: Config can not be saved!', ['error' => $exception->getMessage()]);
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
