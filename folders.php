<?php
// DON'T MODIFY
// preparation
$my_config = 'my.config.inc.php';
if (file_exists($my_config)) {
	require_once('my.config.inc.php');
} else {
        require_once('config.inc.php');
}

foreach($config['folders'] as $directory) {
	if(!is_dir($directory)){
		mkdir($directory, 0777);
	}
}
