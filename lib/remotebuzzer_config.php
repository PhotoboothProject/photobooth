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

	if ($config['dev'])
	{
		$logfile = $config['folders']['tmp']."/".$config['remotebuzzer_logfile'];
		$fp = fopen("../".$logfile, 'a');//opens file in append mode.
		fwrite($fp, "socket.io server [ config ]: Config has changed, kill existing remotebuzzer_server (PID ".$procPID.") and removed PID file\n");
		fclose($fp);
	}		

}
?>
