<?php
/* check PID file and if found, kill process and delete PID file */

require_once(__DIR__ . '/config.php');

function killProcessIfActive($pName, $pidFile, $logfileName)
{
	global $config;

	if (file_exists($pidFile))
	{
		$myfile = fopen($pidFile, "r");
		$procPID = fread($myfile,100);
		fclose($myfile);

		posix_kill($procPID, 9);

		unlink ($pidFile);

		if ($config['dev'])
		{
			$logfile = $config['folders']['tmp']."/".$logfileName;
			$fp = fopen("../".$logfile, 'a');//opens file in append mode.
			fwrite($fp, "Service Control [ config ]: Photobooth config has changed, kill existing $pName process (PID ".$procPID.") and remove PID file\n");
			fclose($fp);
		}
	}
	else
	{
		exec("pgrep -f ".$pName,$pids);

		foreach ($pids as $procPID) {
			if ($config['dev'])
			{	
				$logfile = $config['folders']['tmp']."/".$logfileName;
				$fp = fopen("../".$logfile, 'a');//opens file in append mode.
				fwrite($fp, "Service Control [ config ]: Photobooth config has changed, killed processes by name ".$pName." -> ".$procPID."\n");
				fclose($fp);
			}

			posix_kill($procPID, 9);
		}

	}
}


killProcessIfActive('remotebuzzer_server.js','../'.$config['folders']['tmp'].'/remotebuzzer_server.pid',$config['remotebuzzer_logfile']);
killProcessIfActive('sync-to-drive.js','../'.$config['folders']['tmp'].'/synctodrive_server.pid',$config['synctodrive_logfile']);
?>
