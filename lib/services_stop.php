<?php
/* check PID file and if found, kill process and delete PID file */

require_once(__DIR__ . '/config.php');

function killProcessIfActive($pName, $pidFile, $logfileName)
{
        global $config;

        exec("pgrep -f ".$pName,$pids);

        if (count($pids) > 1) {
           foreach ($pids as $procPID) {
                if ($config['dev'])
                {
                        $logfile = $config['foldersAbs']['tmp']."/".$logfileName;
                        $fp = fopen($logfile, 'a');//opens file in append mode.
                        fwrite($fp, "Service Control [ config ]: Photobooth config has changed, killed processes by name ".$pName." -> PID ".$procPID."\n");
                        fclose($fp);
                }

                posix_kill($procPID, 9);
           }
        }

        if (file_exists($pidFile)) { unlink ($pidFile); }

}

killProcessIfActive('remotebuzzer_server.js','../'.$config['folders']['tmp'].'/remotebuzzer_server.pid',$config['remotebuzzer_logfile']);
killProcessIfActive('sync-to-drive.js','../'.$config['folders']['tmp'].'/synctodrive_server.pid',$config['synctodrive_logfile']);

?>
