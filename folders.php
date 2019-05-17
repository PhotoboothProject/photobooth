<?php
// DON'T MODIFY
// preparation
foreach($config['folders'] as $directory) {
	if(!is_dir($directory)){
		mkdir($directory, 0777);
	}
}
