<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';

$os = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';

$data = $_POST;
if (!isset($data['type'])) {
    echo json_encode('error');
}

if ($data['type'] == 'reset') {
    if ($config['reset']['remove_images']) {
        // empty folders
        foreach ($config['foldersAbs'] as $folder) {
            if (is_dir($folder)) {
                $files = glob($folder . '/*.jpg');
                foreach ($files as $file) {
                    // iterate files
                    if (is_file($file)) {
                        // delete file
                        unlink($file);
                    }
                }
            }
        }
    }

    if ($config['reset']['remove_mailtxt']) {
        if (is_file(MAIL_FILE)) {
            unlink(MAIL_FILE); // delete file
        }
    }

    if ($config['reset']['remove_config']) {
        // delete personal config
        if (is_file('../config/my.config.inc.php')) {
            unlink('../config/my.config.inc.php');
        }
    }

    $logFiles = glob($config['foldersAbs']['tmp'] . '/*.log');
    foreach ($logFiles as $logFile) {
        // iterate files
        if (is_file($logFile)) {
            // delete file
            unlink($logFile);
        }
    }

    // delete db.txt
    if (is_file(DB_FILE)) {
        // delete file
        unlink(DB_FILE);
    }

    die(json_encode('success'));
}

if ($data['type'] == 'config') {
    $newConfig = [];

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
        }
    } else {
        $newConfig['login']['password'] = null;
    }

    if ($newConfig['preview']['mode'] != 'device_cam' && $newConfig['preview']['mode'] != 'gphoto') {
        $newConfig['preview']['camTakesPic'] = false;
    }

    if ($newConfig['ui']['style'] === 'custom') {
        if (
            !is_readable('../template/custom.template.php') &&
            !is_readable('../resources/css/custom_style.css') &&
            !is_readable('../resources/css/custom_chromakeying.css') &&
            !is_readable('../resources/css/custom_live_chromakeying.css')
        ) {
            $newConfig['ui']['style'] = 'default';
        } else {
            if (!file_exists('../template/custom.template.php')) {
                copy('../template/modern.template.php', '../template/custom.template.php');
            }
            if (!file_exists('../resources/css/custom_style.css')) {
                copy('../resources/css/modern_style.css', '../resources/css/custom_style.css');
            }
            if (!file_exists('../resources/css/custom_chromakeying.css')) {
                copy('../resources/css/modern_chromakeying.css', '../resources/css/custom_chromakeying.css');
            }
            if (!file_exists('../resources/css/custom_live_chromakeying.css')) {
                copy('../resources/css/modern_live_chromakeying.css', '../resources/css/custom_live_chromakeying.css');
            }
        }
    }

    if ($os === 'windows') {
        $newConfig['remotebuzzer']['enabled'] = false;
        $newConfig['synctodrive']['enabled'] = false;
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

    if (isset($newConfig['get_request']['server']) && empty($newConfig['get_request']['server'])) {
        $newConfig['get_request']['countdown'] = false;
        $newConfig['get_request']['processed'] = false;
    }

    $collageLayout = $newConfig['collage']['layout'];
    if ($collageLayout === '1+2' || $collageLayout == '2+1' || $collageLayout == '2x3') {
        $newConfig['collage']['limit'] = 3;
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

    $content = "<?php\n\$config = " . var_export(arrayRecursiveDiff($newConfig, $defaultConfig), true) . ';';

    if (file_put_contents($my_config_file, $content)) {
        clearCache($my_config_file);

        echo json_encode('success');
    } else {
        echo json_encode('error');
    }
}

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
