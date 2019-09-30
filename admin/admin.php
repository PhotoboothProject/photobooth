<?php
header('Content-Type: application/json');

require_once('../lib/config.php');

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

    // delete db.txt
    if (is_file('../data/db.txt')) {
        unlink('../data/db.txt'); // delete file
    }

    die(json_encode('success'));
}

if ($data['type'] == 'config') {
    $file = __DIR__ . '/../config/my.config.inc.php';

    foreach ($config as $k=>$conf) {
        if (is_array($conf)) {
            foreach ($conf as $sk => $sc) {
                if (isset($data[$k][$sk]) && !empty($data[$k][$sk])) {
                    if ($data[$k][$sk] == 'true') {
                        $config[$k][$sk] = true;
                    } else {
                        $config[$k][$sk] = $data[$k][$sk];
                    }
                }
            }
        } else {
            if (isset($data[$k]) && !empty($data[$k])) {
                if ($data[$k] == 'true') {
                    $config[$k] = true;
                } else {
                    $config[$k] = $data[$k];
                }
            } else {
                $config[$k] = false;
            }
        }
    }

    $content = "<?php\n\$config = ". var_export($config, true) . ";";

    if (file_put_contents($file, $content)) {
        echo json_encode('success');
    } else {
        echo json_encode('error');
    }
}
