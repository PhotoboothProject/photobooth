<?php

require_once __DIR__ . '/config.php';

function processIsRunning($pName, $pidFile) {
    if (file_exists($pidFile)) {
        exec('pgrep -F ' . $pidFile, $output, $return);
        if ($return == 0) {
            return true;
        } // process is active
        unlink($pidFile); // remove stale PID file
    }

    exec('pgrep -a -f ' . $pName, $output, $return);
    return count($output) - 1 ? true : false; // true if process is active
}

if ($config['remotebuzzer']['usebuttons'] || $config['remotebuzzer']['userotary'] || $config['remotebuzzer']['usenogpio']) {
    $connection = @fsockopen('127.0.0.1', $config['remotebuzzer']['port']);

    if (!is_resource($connection)) {
        if ($config['dev']['loglevel'] > 0) {
            $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['remotebuzzer']['logfile'];
        } else {
            $logfile = '/dev/null';
        }

        echo '<!-- Remote Buzzer enabled --- starting server -->' . "\n";
        if (!empty($fileRoot)) {
            chdir($fileRoot);
        }
        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/remotebuzzer_server.js 1>' . $logfile . ' 2>&1 &', [], $foo));
    } else {
        echo '<!-- Remote Buzzer Enabled --- server already started (port in use) -->' . "\n";
    }
}

if ($config['synctodrive']['enabled']) {
    if ($config['dev']['loglevel'] > 0) {
        $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['synctodrive']['logfile'];
    } else {
        $logfile = '/dev/null';
    }

    if (processIsRunning('sync-to-drive.js', $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'synctodrive_server.pid')) {
        echo '<!-- Sync To Drive enabled --- server already active -->' . "\n";
    } else {
        echo '<!-- Sync To Drive enabled --- starting server -->' . "\n";
        if (!empty($fileRoot)) {
            chdir($fileRoot);
        }
        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/sync-to-drive.js 1>' . $logfile . ' 2>&1 &', [], $foo));
    }
}
?>
