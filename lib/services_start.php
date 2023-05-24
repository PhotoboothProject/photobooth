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

        print "\t<!-- Remote Buzzer enabled --- starting server -->\n";

        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/remotebuzzer_server.js 1>' . $logfile . ' 2>&1 &', [], $foo));
    } else {
        print "\t<!-- Remote Buzzer Enabled --- server already started (port in use) -->\n";
    }

    print "\t<script type=\"text/javascript\" src=\"node_modules/socket.io-client/dist/socket.io.min.js\"></script>\n";
}

if ($config['synctodrive']['enabled']) {
    if ($config['dev']['loglevel'] > 0) {
        $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['synctodrive']['logfile'];
    } else {
        $logfile = '/dev/null';
    }

    if (processIsRunning('sync-to-drive.js', $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'synctodrive_server.pid')) {
        print "\t<!-- Sync To Drive enabled --- server already active -->\n";
    } else {
        print "\t<!-- Sync To Drive enabled --- starting server -->\n";
        proc_close(proc_open($config['nodebin']['cmd'] . ' resources/js/sync-to-drive.js 1>' . $logfile . ' 2>&1 &', [], $foo));
    }
}

if ($config['nextcloud']['mntEnabled'] && isset($config['nextcloud']['mnt']) && !empty($config['nextcloud']['mnt'])) {
    if ($config['dev']['loglevel'] > 0) {
        $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['nextcloud']['logfile'];
    } else {
        $logfile = '/dev/null';
    }

    if (processIsRunning('nc_copy_on_mnt.sh', $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . 'nc_copy_on_mnt.pid')) {
        print "\t<!-- Nextcloud Copy On Mount --- server already active -->\n";
    } else {
        print "\t<!-- Nextcloud Copy On Mount --- starting server -->\n";
        $command = sprintf(
            'scripts/nc_copy_on_mnt.sh "%s" "%s" "%s" 1>%s 2>&1 &',
            $config['foldersAbs']['images'],
            $config['nextcloud']['mnt'],
            $config['foldersAbs']['tmp'],
            $logfile
        );
        proc_open($command, [], $foo);
    }
}
?>
