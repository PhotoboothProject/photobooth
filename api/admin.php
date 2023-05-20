<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/printdb.php';

$Logger = new DataLogger(PHOTOBOOTH_LOG);

$Logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);

$data = $_POST;

if (isset($data['type'])) {
    $newConfig = [];
    $Logger->addLogData(['config' => 'Saving Photobooth configuration']);

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
        if (isset($newConfig['login']['password']) && !empty($newConfig['login']['password'])) {
            if (!($newConfig['login']['password'] === $config['login']['password'])) {
                $hashing = password_hash($newConfig['login']['password'], PASSWORD_DEFAULT);
                $newConfig['login']['password'] = $hashing;
            }
        } else {
            $newConfig['login']['enabled'] = false;
            $Logger->addLogData(['login' => 'Password not set. Login disabled.']);
        }
    } else {
        $newConfig['login']['password'] = null;
    }

    if ($newConfig['preview']['camTakesPic'] && $newConfig['preview']['mode'] != 'device_cam' && $newConfig['preview']['mode'] != 'gphoto') {
        $newConfig['preview']['camTakesPic'] = false;
        $Logger->addLogData(['preview' => 'Device cam takes picture disabled. Can take images from preview only from gphoto2 and device cam preview.']);
    }

    if ($newConfig['ui']['style'] === 'custom') {
        if (
            !is_readable('../template/custom.template.php') &&
            !is_readable('../resources/css/custom_style.css') &&
            !is_readable('../resources/css/custom_admin.css') &&
            !is_readable('../resources/css/custom_chromakeying.css') &&
            !is_readable('../resources/css/custom_live_chromakeying.css')
        ) {
            $newConfig['ui']['style'] = 'modern_squared';
            $Logger->addLogData(['ui' => 'No custom style resources found. Falling back to modern squared style.']);
        } else {
            if (!file_exists('../template/custom.template.php')) {
                copy('../template/modern.template.php', '../template/custom.template.php');
            }
            if (!file_exists('../resources/css/custom_style.css')) {
                copy('../resources/css/modern_style.css', '../resources/css/custom_style.css');
            }
            if (!file_exists('../resources/css/custom_admin.css')) {
                copy('../resources/css/modern_admin.css', '../resources/css/custom_admin.css');
            }
            if (!file_exists('../resources/css/custom_chromakeying.css')) {
                copy('../resources/css/modern_chromakeying.css', '../resources/css/custom_chromakeying.css');
            }
            if (!file_exists('../resources/css/custom_live_chromakeying.css')) {
                copy('../resources/css/modern_live_chromakeying.css', '../resources/css/custom_live_chromakeying.css');
            }
        }
    }

    if (SERVER_OS === 'windows') {
        if (!empty($newConfig['remotebuzzer']['enabled'])) {
            $newConfig['remotebuzzer']['enabled'] = false;
            $Logger->addLogData(['remotebuzzer' => 'Remotebuzzer server unsupported on Windows.']);
        }
        if (!empty($newConfig['synctodrive']['enabled'])) {
            $newConfig['synctodrive']['enabled'] = false;
            $Logger->addLogData(['synctodrive' => 'Sync pictures to USB stick unsupported on Windows.']);
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
            $Logger->addLogData(['get_request' => 'No GET request server entered. Disabled GET request options.']);
        }
    }

    $collageLayout = $newConfig['collage']['layout'];
    $collageConfigFilePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private/collage.json';
    if ($collageLayout === '1+2' || $collageLayout == '2+1' || $collageLayout == '2x3') {
        $newConfig['collage']['limit'] = 3;
    } elseif ($collageLayout == 'collage.json' && file_exists($collageConfigFilePath)) {
        $collageConfig = json_decode(file_get_contents($collageConfigFilePath), true);
        if (is_array($collageConfig)) {
            $newConfig['collage']['limit'] = count($collageConfig);
        } else {
            $newConfig['collage']['limit'] = 4;
        }
    } else {
        $newConfig['collage']['limit'] = 4;
    }

    //If there is a collage placeholder whithin the correct range (0 < placeholderposition <= collage limit), we need to decrease the collage limit by 1
    if ($newConfig['collage']['placeholder']) {
        $collagePlaceholderPosition = (int) $newConfig['collage']['placeholderposition'];
        if ($collagePlaceholderPosition > 0 && $collagePlaceholderPosition <= $newConfig['collage']['limit']) {
            $newConfig['collage']['limit'] = $newConfig['collage']['limit'] - 1;
        } else {
            $newConfig['collage']['placeholderposition'] = false;
        }
    }

    if ($newConfig['logo']['enabled']) {
        if (empty($newConfig['logo']['path']) || !file_exists('..' . DIRECTORY_SEPARATOR . $newConfig['logo']['path'])) {
            $newConfig['logo']['enabled'] = false;
            $Logger->addLogData(['logo' => 'Logo file path does not exist or is empty. Logo disabled']);
        } else {
            $newConfig['logo']['path'] = Helper::fixSeperator($newConfig['logo']['path']);
        }
    }

    $content = "<?php\n\$config = " . var_export(arrayRecursiveDiff($newConfig, $defaultConfig), true) . ';';

    if (file_put_contents($my_config_file, $content)) {
        clearCache($my_config_file);
        $Logger->addLogData(['config' => 'New config saved']);

        if ($data['type'] == 'reset') {
            $Logger->addLogData(['reset' => 'Resetting Photobooth']);
            if ($newConfig['reset']['remove_images']) {
                $Logger->addLogData(['remove_images' => 'Removing images']);
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
                                    $Logger->addLogData([$file => 'deleted']);
                                }
                            }
                        }
                    } else {
                        $Logger->addLogData([$folder => 'skipped']);
                    }
                }
            }

            if ($newConfig['reset']['remove_print_db']) {
                $printManager = new PrintManager();
                $printManager->printDb = PRINT_DB;
                $printManager->printLockFile = PRINT_LOCKFILE;
                $printManager->printCounter = PRINT_COUNTER;
                // delete print database
                if ($printManager->removePrintDb()) {
                    $Logger->addLogData(['printed.csv' => 'deleted']);
                }
                if ($printManager->unlockPrint()) {
                    $Logger->addLogData(['print.lock' => 'deleted']);
                }
                if ($printManager->removePrintCounter()) {
                    $Logger->addLogData(['print.count' => 'deleted']);
                }
            }

            if ($newConfig['reset']['remove_mailtxt']) {
                if (is_file(MAIL_FILE)) {
                    unlink(MAIL_FILE); // delete file
                    $Logger->addLogData([MAIL_FILE => 'deleted']);
                }
            }

            if ($newConfig['reset']['remove_config']) {
                // delete personal config
                if (is_file('../config/my.config.inc.php')) {
                    unlink('../config/my.config.inc.php');
                    $Logger->addLogData(['my.config.inc.php' => 'deleted']);
                }
            }

            $logFiles = glob($config['foldersAbs']['tmp'] . '/*.log');
            foreach ($logFiles as $logFile) {
                // iterate files
                if (is_file($logFile)) {
                    // delete file
                    unlink($logFile);
                    $Logger->addLogData([$logFile => 'deleted']);
                }
            }

            // delete db.txt
            if (is_file(DB_FILE)) {
                // delete file
                unlink(DB_FILE);
                $Logger->addLogData([DB_FILE => 'deleted']);
            }
        }
        echo json_encode('success');
    } else {
        $Logger->addLogData(['config' => 'ERROR: Config can not be saved!']);
        echo json_encode('error');
    }
} else {
    $Logger->addLogData(['type' => 'ERROR: Unknown action.']);
    $Logger->logToFile();
    die(json_encode('error'));
}
$Logger->logToFile();

/* Kill service daemons after config has changed */
require_once '../lib/services_stop.php';

function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = [];

    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                if (count($aRecursiveDiff)) {
                    $aReturn[$mKey] = $aRecursiveDiff;
                }
            } else {
                if ($mValue != $aArray2[$mKey]) {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }
    return $aReturn;
}

function clearCache($file) {
    if (function_exists('opcache_invalidate') && strlen(ini_get('opcache.restrict_api')) < 1) {
        opcache_invalidate($file, true);
    } elseif (function_exists('apc_compile_file')) {
        apc_compile_file($file);
    }
}
