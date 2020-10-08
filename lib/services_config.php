<?php
/* check PID file and if found, kill process and delete PID file */

function killProcessIfActive($pName, $pidFile, $logfileName)
{

	if (file_exists($pidFile))
	{
		$myfile = fopen($pidFile, "r");
		$procPID = fread($myfile,100);
		fclose($myfile);

		posix_kill($procPID, 9);

		unlink ($pidFile);

		if ($config['dev'])
		{
			$logfile = $config['folders']['tmp']."/".logfileName;
			$fp = fopen("../".$logfile, 'a');//opens file in append mode.
			fwrite($fp, "Service Control [ config ]: Photobooth config has changed, kill existing $pName process (PID ".$procPID.") and remove PID file\n");
			fclose($fp);
		}
	}
}

killProcessIfActive('remotebuzzer','../'.$config['folders']['tmp'].'/remotebuzzer_server.pid',$config['remotebuzzer_logfile']);
killProcessIfActive('sync-to-drive','../'.$config['folders']['tmp'].'/synctodrive_server.pid',$config['synctodrive_logfile']);
?>
