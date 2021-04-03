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

if ($config['remotebuzzer']['enabled']) {
    $connection = @fsockopen('127.0.0.1', $config['remotebuzzer']['port']);

    if (!is_resource($connection)) {
        if ($config['dev']['enabled']) {
            $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['remotebuzzer']['logfile'];
        } else {
            $logfile = '/dev/null';
        }

        print "\t<!-- Remote Buzzer enabled --- starting server -->\n";

        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/remotebuzzer_server.js 1>>' . $logfile . ' 2>&1 &', [], $foo));
    } else {
        print "\t<!-- Remote Buzzer Enabled --- server already started (port in use) -->\n";
    }

    print "\t<script type=\"text/javascript\" src=\"node_modules/socket.io-client/dist/socket.io.min.js\"></script>\n";
}

if ($config['synctodrive']['enabled']) {
    if ($config['dev']['enabled']) {
        $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['synctodrive']['logfile'];
    } else {
        $logfile = '/dev/null';
    }

    if (processIsRunning('sync-to-drive.js', $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'synctodrive_server.pid')) {
        print "\t<!-- Sync To Drive enabled --- server already active -->\n";
    } else {
        print "\t<!-- Sync To Drive enabled --- starting server -->\n";
        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/sync-to-drive.js 1>>' . $logfile . ' 2>&1 &', [], $foo));
    }
}
?>
