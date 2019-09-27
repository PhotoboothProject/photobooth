<?php

$default_config = __DIR__ . '/../config/config.inc.php';
$my_config = __DIR__ . '/../config/my.config.inc.php';

if (file_exists($my_config)) {
    require_once($my_config);
} else {
    require_once($default_config);
}

foreach($config['folders'] as $key => $folder) {
	$config['foldersAbs'][$key] = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $folder);
}