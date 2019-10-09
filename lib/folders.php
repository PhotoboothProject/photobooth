<?php
// DON'T MODIFY
// preparation
require_once(__DIR__ . '/config.php');

foreach($config['foldersAbs'] as $directory) {
	if(!is_dir($directory)){
		mkdir($directory, 0775);
	}
}
