<?php
header('Content-Type: application/json');

ob_start();
require_once 'config.php';
$output = ob_end_clean();

$content = $_GET['content'];

switch ($content) {
    case 'nav-remotebuzzerlog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['remotebuzzer']['logfile'], true);
        break;

    case 'nav-synctodrivelog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['synctodrive']['logfile'], true);
        break;

    case 'nav-myconfig':
        print_r($config);
        break;

    case 'nav-serverprocesses':
        echo shell_exec('/bin/ps -ef');
        break;

    case 'nav-bootconfig':
        echo dumpfile('/boot/config.txt', null);
        break;

    case 'nav-devlog':
        echo dumpfile($config['foldersAbs']['tmp'] . '/' . $config['dev']['logfile'], null);
        break;

    case 'nav-installlog':
        echo dumpfile($config['foldersAbs']['private'] . '/install.log', null);
        break;

    case 'nav-githead':
        $get_head = shell_exec('git rev-parse --is-inside-work-tree 2>/dev/null && git log --format="%h %s" -n 20 || false');
        $file_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'HEAD';
        $head_file = realpath($file_path);

        if (is_file($head_file)) {
            echo 'Latest commits:' . "\r\n";
            echo dumpfile($head_file, null);
        } elseif ($get_head) {
            echo 'Latest commits:' . "\r\n";
            echo $get_head;
        } else {
            echo 'Can not get latest commits of this Photobooth installation.';
        }
        break;

    case 'nav-printdb':
        $printlog = $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'print.log';
        $resp = [];
        if (!file_exists($printlog)) {
            echo 'No database found.' . "\r\n";
        } elseif (!read_csv($printlog, $resp)) {
            echo 'Can\'t read CSV.' . "\r\n";
        } else {
            echo '<table style="width:90%; margin-left: auto; margin-right: auto;">' . "\r\n";
            echo '    <thead>' . "\r\n";
            echo '        <tr>' . "\r\n";
            echo '            <th>Number</th>' . "\r\n";
            echo '            <th>Date</th>' . "\r\n";
            echo '            <th>Time</th>' . "\r\n";
            echo '            <th>Image</th>' . "\r\n";
            echo '            <th>Unique name</th>' . "\r\n";
            echo '        </tr>' . "\r\n";
            echo '    </thead>' . "\r\n";
            echo '    <tbody>' . "\r\n";

            $count = 0;
            $data = [];
            foreach ($resp as $row_number => $data) {
                $count++;
                echo '        <tr>' . "\r\n";
                echo '            <td class="end">' . $count . '</td>' . "\r\n";
                echo '            <td class="center">' . $data[0] . '</td>' . "\r\n";
                echo '            <td class="center">' . $data[1] . '</td>' . "\r\n";
                echo '            <td class="center">' . $data[2] . '</td>' . "\r\n";
                echo '            <td class="center">' . $data[3] . '</td>' . "\r\n";
                echo '        </tr>' . "\r\n";
            }
            echo '    </tbody>' . "\r\n";
            echo '</table>' . "\r\n";
        }
        break;

    default:
        echo 'Unknown debug panel parameter';
        break;
}

function dumpfile($file, $devModeRequired) {
    global $config;

    if ($devModeRequired !== null && $devModeRequired && $config['dev']['loglevel'] < 1) {
        return 'INFO: Loglevel is ' . $config['dev']['loglevel'] . '. Please set Loglevel > 1 to see logs.';
    }

    if (!file_exists($file)) {
        return 'INFO: File (' . $file . ') does not exist';
    } elseif (!is_file($file)) {
        return 'INFO: Path (' . $file . ') is not a file';
    } else {
        return file_get_contents($file);
    }
}

function read_csv(string $path_to_csv_file, array &$result): bool {
    $handle = fopen($path_to_csv_file, 'r');

    if (!$handle) {
        return false;
    }

    while (false !== ($data = fgetcsv($handle, null, ','))) {
        $result[] = $data;
    }

    return true;
}

return true;
