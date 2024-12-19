<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Service\PrintManagerService;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

function handleDebugPanel(string $content, array $config): string|false
{
    switch ($content) {
        case 'nav-devlog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/main.log'));
        case 'nav-remotebuzzerlog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/remotebuzzer.log'));
        case 'nav-synctodrivelog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/synctodrive.log'));
        case 'nav-myconfig':
            echo implode("\n", showConfig($config));
            return json_encode('');
        case 'nav-serverprocesses':
            return (string)shell_exec('/bin/ps -ef');
        case 'nav-bootconfig':
            return readFileContents('/boot/config.txt');
        case 'nav-installlog':
            return readFileContents('/var/log/photobooth_install.log');
        case 'nav-githead':
            return getLatestCommits();
        case 'nav-printdb':
            $result = [];
            $printManager = PrintManagerService::getInstance();
            if (!file_exists($printManager->printDb)) {
                return 'No database found.';
            } elseif (!read_csv($printManager->printDb, $result)) {
                return 'Can\'t read print database.';
            } else {
                $columns = [
                    0 => 'Count',
                    1 => 'Date',
                    2 => 'Time',
                    3 => 'Image',
                    4 => 'Unique name',
                ];
                return generateTableHtml($columns, $result);
            }
            // no break
        default:
            http_response_code(400);
            return json_encode(['error' => 'Unknown debug panel parameter']);
    }
}

function getLatestCommits(): string|false
{
    try {
        $getHead = shell_exec('git rev-parse --is-inside-work-tree 2>/dev/null && git log --format="%h %s" -n 20 || false');
        $headFilePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'HEAD';
        if (is_file($headFilePath)) {
            $result = 'Latest commits:' . "\r\n";
            $result .= file_get_contents($headFilePath);
        } elseif ($getHead) {
            $result = 'Latest commits:' . "\r\n";
            $result .= $getHead;
        } else {
            http_response_code(404);
            return json_encode(['error' => 'Can not get latest commits']);
        }
        return $result;
    } catch (\Exception $e) {
        http_response_code(500);
        return json_encode(['error' => $e->getMessage()]);
    }
}

function readFileContents(string $file): string|false
{
    global $config;
    try {
        if ($config['dev']['loglevel'] < 1) {
            throw new \Exception('INFO: Loglevel is ' . $config['dev']['loglevel'] . '. Please set Loglevel > 1 to see logs.');
        }

        if (!file_exists($file)) {
            throw new \Exception('INFO: File (' . $file . ') does not exist');
        }

        if (!is_file($file)) {
            throw new \Exception('INFO: Path (' . $file . ') is not a file');
        }

        return file_get_contents($file);
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function read_csv(string $path_to_csv_file, array &$result): bool
{
    $handle = fopen($path_to_csv_file, 'r');

    if (!$handle) {
        return false;
    }

    while (false !== ($data = fgetcsv($handle, null, ','))) {
        $result[] = $data;
    }

    if (count($result) === 0) {
        $result[] = ['No data found in the file'];
        return false;
    }

    return true;
}

function processItem(string $key, mixed $content): array
{
    $output = [];

    $output[] = "Subconfig: $key";

    if (isset($content)) {
        if (is_array($content)) {
            $contentString = implode(', ', array_map(function ($item) {
                return is_object($item) ? json_encode($item) : $item;
            }, $content));
            $output[] = "Value:     $contentString";
        } elseif (is_bool($content)) {
            $contentString = $content ? 'true' : 'false';
            $output[] = "Value:     $contentString";
        } else {
            $output[] = 'Value:     ' . json_encode($content);
        }
    } else {
        $output[] = 'Value:     Not defined';
    }

    $output[] = '----------------';

    return $output;
}

function showConfig(array $config): array
{
    $output = [];

    foreach ($config as $name => $items) {
        $output[] = '################################';
        $output[] = "Config: $name";
        $output[] = '----------------';

        if (is_array($items)) {
            foreach ($items as $key => $content) {
                $itemOutput = processItem($key, $content);
                $output = array_merge($output, $itemOutput);
            }
        } else {
            $output[] = 'Invalid value for items';
        }
    }

    return $output;
}

function generateTableHtml(array $columns, array $result): string
{
    $html = '<h2 class="center">Print database</h2>' . "\r\n";
    $html .= '<table style="width:90%; margin-left: auto; margin-right: auto;">' . "\r\n";
    $html .= '    <thead>' . "\r\n";
    $html .= '        <tr>' . "\r\n";
    foreach ($columns as $column) {
        $html .= '            <th>' . htmlspecialchars($column) . '</th>' . "\r\n";
    }
    $html .= '        </tr>' . "\r\n";
    $html .= '    </thead>' . "\r\n";
    $count = 0;
    $data = [];
    $html .= '    <tbody>' . "\r\n";
    foreach ($result as $row_number => $data) {
        $count++;
        $html .= '        <tr>' . "\r\n";
        $html .= '            <td class="end">' . $count . '</td>' . "\r\n";
        $html .= '            <td class="center">' . $data[0] . '</td>' . "\r\n";
        $html .= '            <td class="center">' . $data[1] . '</td>' . "\r\n";
        $html .= '            <td class="center">' . $data[2] . '</td>' . "\r\n";
        $html .= '            <td class="center">' . $data[3] . '</td>' . "\r\n";
        $html .= '        </tr>' . "\r\n";
    }
    $html .= '    </tbody>' . "\r\n";
    $html .= '</table>' . "\r\n";
    return $html;
}

if (!empty($_GET['content'])) {
    echo handleDebugPanel($_GET['content'], $config);
}
