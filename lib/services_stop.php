<?php

// check PID file and if found, kill process and delete PID file

require_once __DIR__ . '/config.php';

function killProcessIfActive($pName, $pidFile, $logfileName, $killSig)
{
    global $config;

    exec('pgrep -f ' . $pName, $pids);

    if (count($pids) > 1) {
        foreach ($pids as $procPID) {
            if ($config['dev']['loglevel'] > 0) {
                $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $logfileName;
                $fp = fopen($logfile, 'a'); //opens file in append mode.
                fwrite($fp, 'Service Control [ config ]: Photobooth config has changed, killed processes by name ' . $pName . ' -> PID ' . $procPID . "\n");
                fclose($fp);
            }

            posix_kill($procPID, $killSig);
        }
    }

    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
}

// can be killed if active independent of $config['remotebuzzer']['startserver']
killProcessIfActive(
    'remotebuzzer_server.js',
    '..' . DIRECTORY_SEPARATOR . $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'remotebuzzer_server.pid',
    $config['remotebuzzer']['logfile'],
    9
);

killProcessIfActive(
    'sync-to-drive.js',
    '..' . DIRECTORY_SEPARATOR . $config['foldersRoot']['tmp'] . DIRECTORY_SEPARATOR . 'synctodrive_server.pid',
    $config['synctodrive']['logfile'],
    15
);
