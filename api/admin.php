<?php
header('Content-Type: application/json');

require_once('../lib/config.php');
require_once('../lib/db.php');

$data = $_POST;
if (!isset($data['type'])) {
    echo json_encode('error');
}

if ($data['type'] == 'reset') {
    // empty folders
    foreach ($config['foldersAbs'] as $folder) {
        if (is_dir($folder)) {
            $files = glob($folder.'/*.jpg');
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file); // delete file
                }
            }
        }
    }

    // delete mail-addresses.txt
    if(is_file('../mail-addresses.txt')){
        unlink('../mail-addresses.txt');
    }

    // delete db.txt
    if (is_file(DB_FILE)) {
        unlink(DB_FILE); // delete file
    }

    die(json_encode('success'));
}

if ($data['type'] == 'config') {
    $file = __DIR__ . '/../config/my.config.inc.php';
    $newConfig = [];

    foreach ($config as $k=>$conf) {
        if (is_array($conf)) {
            foreach ($conf as $sk => $sc) {
                if (isset($data[$k][$sk]) && !empty($data[$k][$sk])) {
                    if ($data[$k][$sk] == 'true') {
                        $newConfig[$k][$sk] = true;
                    } else {
                        $newConfig[$k][$sk] = $data[$k][$sk];
                    }
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

    $content = "<?php\n\$config = ". var_export(arrayRecursiveDiff($newConfig, $defaultConfig), true) . ";";

    if (file_put_contents($file, $content)) {
        clearCache($file);

        echo json_encode('success');
    } else {
        echo json_encode('error');
    }
}

function arrayRecursiveDiff($aArray1, $aArray2)
{
    $aReturn = array();

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
    if (function_exists('opcache_invalidate') && strlen(ini_get("opcache.restrict_api")) < 1) {
        opcache_invalidate($file, true);
    } elseif (function_exists('apc_compile_file')) {
        apc_compile_file($file);
    }
}
