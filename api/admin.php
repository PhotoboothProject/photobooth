<?php

require_once '../lib/boot.php';

use Photobooth\Helper;
use Photobooth\Environment;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$data = $_POST;

if (isset($data['type'])) {
    $newConfig = [];
    $logger->debug('Saving Photobooth configuration...');

    foreach ($config as $k => $conf) {
        if (is_array($conf)) {
            foreach ($conf as $sk => $sc) {
                if (isset($data[$k][$sk])) {
                    if ($data[$k][$sk] == 'true') {
                        $newConfig[$k][$sk] = true;
                    } else {
                        $newConfig[$k][$sk] = $data[$k][$sk];
                    }
                } elseif (isset($defaultConfig[$k][$sk])) {
                    $newConfig[$k][$sk] = false;
                }
            }
        } else {
            if (isset($data[$k]) && !empty($data[$k])) {
                if ($data[$k] == 'true') {
                    $newConfig[$k] = true;
                } else {
                    $newConfig[$k] = $data[$k];
                }
            } else {
                $newConfig[$k] = false;
            }
        }
    }

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

    if (isset($newConfig['database']['file']) && empty($newConfig['database']['file'])) {
        $newConfig['database']['file'] = 'db';
    }

    if (isset($newConfig['mail']['file']) && empty($newConfig['mail']['file'])) {
        $newConfig['mail']['file'] = 'mail-adresses';
    }

    if (isset($newConfig['remotebuzzer']['port']) && empty($newConfig['remotebuzzer']['port'])) {
        $newConfig['remotebuzzer']['port'] = 14711;
    }

    if ($newConfig['get_request']['countdown'] || $newConfig['get_request']['processed']) {
        if (isset($newConfig['get_request']['server']) && empty($newConfig['get_request']['server'])) {
            $newConfig['get_request']['countdown'] = false;
            $newConfig['get_request']['processed'] = false;
            $logger->debug('No GET request server entered. Disabled GET request options.');
        }
    }

    $collageLayout = $newConfig['collage']['layout'];
    $collageConfigFilePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private/collage.json';
    if ($collageLayout === '1+2' || $collageLayout == '2+1' || $collageLayout == '2x3') {
        $newConfig['collage']['limit'] = 3;
    } elseif ($collageLayout == 'collage.json' && file_exists($collageConfigFilePath)) {
        $collageConfig = json_decode(file_get_contents($collageConfigFilePath), true);
        if (is_array($collageConfig)) {
            if (array_key_exists('layout', $collageConfig)) {
                $newConfig['collage']['limit'] = count($collageConfig['layout']);
            } else {
                $newConfig['collage']['limit'] = count($collageConfig);
            }
        } else {
            $newConfig['collage']['limit'] = 4;
        }
    } else {
        $newConfig['collage']['limit'] = 4;
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

        if (
            empty($newConfig['collage']['placeholderpath']) ||
            !is_array(
                @getimagesize(
                    str_starts_with($newConfig['collage']['placeholderpath'], 'http')
                        ? $newConfig['collage']['placeholderpath']
                        : $_SERVER['DOCUMENT_ROOT'] . $newConfig['collage']['placeholderpath']
                )
            )
        ) {
            $newConfig['collage']['placeholder'] = false;
            $logger->debug('Collage Placeholder does not exist or is empty. Collage Placeholder disabled.', $newConfig['collage']);
        }
    }

    if ($newConfig['picture']['take_frame'] && $newConfig['picture']['frame'] === '') {
        $newConfig['picture']['take_frame'] = false;
        $logger->debug('set picture.frame empty', [$newConfig['picture']['frame']]);
    }

    if ($newConfig['collage']['take_frame'] && $newConfig['collage']['frame'] === '') {
        $newConfig['collage']['take_frame'] = false;
        $logger->debug('collage.frame empty', [$newConfig['collage']['frame']]);
    }

    if ($newConfig['print']['print_frame'] && $newConfig['print']['frame'] === '') {
        $newConfig['print']['print_frame'] = false;
        $logger->debug('print.frame empty', [$newConfig['print']['frame']]);
    }

    if ($newConfig['textonpicture']['enabled'] && ($newConfig['textonpicture']['font'] === '' || !file_exists(PathUtility::getAbsolutePath($newConfig['textonpicture']['font'])))) {
        $newConfig['textonpicture']['enabled'] = false;
        $logger->debug('Picture font does not exist or is empty. Disabled text on picture. Note: Must be an absoloute path.', [$newConfig['textonpicture']['font']]);
    }

    if ($newConfig['textoncollage']['enabled'] && ($newConfig['textoncollage']['font'] === '' || !file_exists(PathUtility::getAbsolutePath($newConfig['textoncollage']['font'])))) {
        $newConfig['textoncollage']['enabled'] = false;
        $logger->debug('Collage font does not exist or is empty. Disabled text on picture. Note: Must be an absoloute path.', [$newConfig['textoncollage']['font']]);
    }

    if ($newConfig['textonprint']['enabled'] && ($newConfig['textonprint']['font'] === '' || !file_exists(PathUtility::getAbsolutePath($newConfig['textonprint']['font'])))) {
        $newConfig['textonprint']['enabled'] = false;
        $logger->debug('Print font does not exist or is empty. Disabled text on print. Note: Must be an absoloute path.', [$newConfig['textonprint']['font']]);
    }

    if ($newConfig['logo']['enabled']) {
        $logoPath = $newConfig['logo']['path'];
        if (empty($logoPath) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)) {
            $newConfig['logo']['enabled'] = false;
            $logger->debug('Logo file path does not exist or is empty. Logo disabled.', $newConfig['logo']);
        } else {
            $newConfig['logo']['path'] = PathUtility::fixFilePath($logoPath);
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            if ($ext === 'svg') {
                $logger->debug('Logo file is SVG, path saved.', $newConfig['logo']);
            } else {
                $imageInfo = @getimagesize($_SERVER['DOCUMENT_ROOT'] . $logoPath);
                if ($imageInfo === false) {
                    $newConfig['logo']['enabled'] = false;
                    $logger->debug('Logo file is not a supported image type [' . $ext . ']. Logo disabled.', $newConfig['logo']);
                } else {
                    $logger->debug('Logo file is a supported image type [' . $ext . '], path saved.', $newConfig['logo']);
                }
            }
        }
    }

    $content = "<?php\n\$config = " . var_export(Helper::arrayRecursiveDiff($newConfig, $defaultConfig), true) . ';';

    if (file_put_contents(PathUtility::getAbsolutePath('config/my.config.inc.php'), $content)) {
        Helper::clearCache(PathUtility::getAbsolutePath('config/my.config.inc.php'));
        $logger->debug('New config saved.');
        if ($data['type'] == 'reset') {
            $logger->debug('Resetting Photobooth.');
            if ($newConfig['reset']['remove_images']) {
                $logger->debug('Removing images.');
                // empty folders
                foreach ($config['foldersAbs'] as $folder) {
                    if ($folder != $config['foldersAbs']['archives'] && $folder != $config['foldersAbs']['private']) {
                        if (is_dir($folder)) {
                            $files = glob($folder . '/*.jpg');
                            foreach ($files as $file) {
                                // iterate files
                                if (is_file($file)) {
                                    // delete file
                                    unlink($file);
                                    $logger->debug($file . ' deleted.');
                                }
                            }
                        }
                    } else {
                        $logger->debug($folder . ' skipped.');
                    }
                }
            }

            if ($newConfig['reset']['remove_print_db']) {
                $printManager = PrintManagerService::getInstance();
                // delete print database
                if ($printManager->removePrintDb()) {
                    $logger->debug('printed.csv deleted.');
                }
                if ($printManager->unlockPrint()) {
                    $logger->debug('print.lock deleted.');
                }
                if ($printManager->removePrintCounter()) {
                    $logger->debug('print.count deleted.');
                }
            }

            if ($newConfig['reset']['remove_mailtxt']) {
                $mailService = MailService::getInstance();
                $mailService->resetDatabase();
                $logger->debug('Mail database resetted.');
            }

            if ($newConfig['reset']['remove_config']) {
                // delete personal config
                if (is_file(PathUtility::getAbsolutePath('config/my.config.inc.php'))) {
                    unlink(PathUtility::getAbsolutePath('config/my.config.inc.php'));
                    $logger->debug('my.config.inc.php deleted.');
                }
            }

            $logFiles = glob(PathUtility::getAbsolutePath('var/log') . '/*.log');
            foreach ($logFiles as $logFile) {
                // iterate files
                if (is_file($logFile)) {
                    // delete file
                    unlink($logFile);
                    $logger->debug($logFile . ' deleted.');
                }
            }

            // delete db.txt
            $database = DatabaseManagerService::getInstance();
            if (is_file($database->databaseFile)) {
                // delete file
                unlink($database->databaseFile);
                $logger->debug($database->databaseFile . ' deleted.');
            }
        }
        echo json_encode('success');
    } else {
        $logger->error('ERROR: Config can not be saved!');
        echo json_encode('error');
    }
} else {
    $logger->error('ERROR: Unknown action.');
    die(json_encode('error'));
}

// Kill service daemons after config has changed
ProcessService::getInstance()->shutdown();
exit();
