<?php
/* check PID file and if found, kill process and delete PID file */

$filename = '../'.$config['folders']['tmp'].'/remotebuzzer_server.pid';

if (file_exists($filename))
{
	$myfile = fopen($filename, "r");
	$procPID = fread($myfile,100);
	fclose($myfile);

	posix_kill($procPID, 9);

	unlink ($filename);
}
?>
