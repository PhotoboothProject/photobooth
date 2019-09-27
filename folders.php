<?php
// DON'T MODIFY
// preparation
require_once('lib/config.php');

foreach($config['folders'] as $directory) {
	if(!is_dir($directory)){
		mkdir($directory, 0775);
	}
}
