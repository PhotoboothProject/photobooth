<?php

/** @var array $config */

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Environment;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\QrCodeUtility;

header('Content-Type: application/json');

function handleDebugPanel(string $content, array $config): string|false
{
    switch ($content) {
        case 'nav-environmentinfo':
            return getEnvironmentInfo();
        case 'nav-devlog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/main.log'));
        case 'nav-remotebuzzerlog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/remotebuzzer.log'));
        case 'nav-synctodrivelog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/synctodrive.log'));
        case 'nav-remotestoragelog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/remotestorage.log'));
        case 'nav-remotestoragequeue':
            return getRemoteStorageQueueHtml();
        case 'nav-rembglog':
            return readFileContents(PathUtility::getAbsolutePath('var/log/rembg.log'));
        case 'nav-myconfig':
            return json_encode(maskedConfig($config), JSON_PRETTY_PRINT);
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

function getRemoteStorageQueueHtml(): string
{
    $queue = RemoteStorageQueueService::getInstance();
    $counts = $queue->getCounts();
    $entries = $queue->getEntries();

    $html = '<h2 class="center">Remote storage upload queue</h2>' . "\r\n";
    $countParts = [];
    foreach ($counts as $status => $count) {
        $countParts[] = $status . ': ' . $count;
    }
    $html .= '<p class="center">' . htmlspecialchars(implode(' | ', $countParts), ENT_QUOTES) . '</p>' . "\r\n";

    if (empty($entries)) {
        return $html . '<p class="center">Queue is empty. Note: successful uploads are removed from the queue after 24 hours, see the remote storage log for the full history.</p>';
    }

    // newest first: during an event the recent activity is what matters
    uasort($entries, fn ($a, $b) => ($b['enqueuedAt'] ?? 0) <=> ($a['enqueuedAt'] ?? 0));

    $statusColors = [
        RemoteStorageQueueService::STATUS_PENDING => '#6b7280',
        RemoteStorageQueueService::STATUS_UPLOADING => '#b45309',
        RemoteStorageQueueService::STATUS_DONE => '#1a7f37',
        RemoteStorageQueueService::STATUS_FAILED => '#b42318',
    ];

    $html .= '<table style="width:90%; margin-left: auto; margin-right: auto;">' . "\r\n";
    $html .= '    <thead><tr>';
    foreach (['File', 'Status', 'Attempts', 'Error', 'Queued at', 'Uploaded at'] as $column) {
        $html .= '<th>' . $column . '</th>';
    }
    $html .= '</tr></thead>' . "\r\n";
    $html .= '    <tbody>' . "\r\n";
    foreach ($entries as $entry) {
        $status = (string) ($entry['status'] ?? 'unknown');
        $color = $statusColors[$status] ?? '#6b7280';
        $html .= '        <tr>';
        $html .= '<td>' . htmlspecialchars((string) ($entry['file'] ?? ''), ENT_QUOTES) . '</td>';
        $html .= '<td class="center"><span style="color:' . $color . '; font-weight: bold;">' . htmlspecialchars($status, ENT_QUOTES) . '</span></td>';
        $html .= '<td class="center">' . (int) ($entry['attempts'] ?? 0) . '</td>';
        $html .= '<td>' . htmlspecialchars((string) ($entry['error'] ?? ''), ENT_QUOTES) . '</td>';
        $html .= '<td class="center">' . formatQueueTimestamp($entry['enqueuedAt'] ?? null) . '</td>';
        $html .= '<td class="center">' . formatQueueTimestamp($entry['uploadedAt'] ?? null) . '</td>';
        $html .= '</tr>' . "\r\n";
    }
    $html .= '    </tbody>' . "\r\n";
    $html .= '</table>' . "\r\n";

    return $html;
}

function formatQueueTimestamp(mixed $timestamp): string
{
    if (!is_numeric($timestamp) || (int) $timestamp <= 0) {
        return '-';
    }

    return date('Y-m-d H:i:s', (int) $timestamp);
}

function getEnvironmentInfo(): string
{
    // rendered via innerHTML in the debug panel; SSIDs and interface
    // descriptions are externally controlled strings, so every dynamic
    // string is escaped individually (the output contains QR <img> tags)
    $lines = [];
    $lines[] = 'Operating system: ' . Environment::getOperatingSystem();
    $lines[] = 'PHP version:      ' . PHP_VERSION;
    $lines[] = 'Base URL:         ' . PathUtility::getBaseUrl();
    $lines[] = 'Primary IP:       ' . (Environment::getIp() ?: 'unknown');
    $lines[] = '';
    $lines[] = 'Network interfaces (addresses to reach this machine, e.g. via SSH/VNC):';
    $lines[] = '';
    $html = htmlspecialchars(implode("\r\n", $lines), ENT_QUOTES) . "\r\n";

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $portSuffix = '';
    if ($port !== '' && !($scheme === 'http' && $port === '80') && !($scheme === 'https' && $port === '443')) {
        $portSuffix = ':' . $port;
    }
    $basePath = PathUtility::getBaseUrl();

    $interfaces = Environment::getNetworkInterfaces();
    if (empty($interfaces)) {
        $html .= 'No network interface information available.' . "\r\n";
    }
    foreach ($interfaces as $name => $interface) {
        $block = [];
        $block[] = '################################';
        $title = 'Interface: ' . $name . ' (' . ($interface['up'] ? 'up' : 'down') . ')';
        if ($interface['description'] !== null && $interface['description'] !== $name) {
            $title .= ' - ' . $interface['description'];
        }
        $block[] = $title;
        if ($interface['ssid'] !== null) {
            $block[] = 'Wi-Fi network (SSID): ' . $interface['ssid'];
        }
        $html .= htmlspecialchars(implode("\r\n", $block), ENT_QUOTES) . "\r\n";

        foreach ($interface['addresses'] as $address) {
            $line = str_pad($address['family'] . ':', 6) . '  ' . $address['address'];
            if ($address['network'] !== null) {
                $line .= '   (network: ' . $address['network'] . ')';
            }
            $html .= htmlspecialchars($line, ENT_QUOTES) . "\r\n";

            // one QR code per IPv4 address: scan it from a device on the
            // same network to open the photobooth from there
            if ($address['family'] === 'IPv4') {
                $url = $scheme . '://' . $address['address'] . $portSuffix . $basePath;
                $html .= htmlspecialchars('Open from this network: ' . $url, ENT_QUOTES) . "\r\n";
                try {
                    $qr = QrCodeUtility::create($url, '', 200, 10);
                    $html .= '<img src="data:' . htmlspecialchars($qr->getMimeType(), ENT_QUOTES) . ';base64,' . base64_encode($qr->getString()) . '" width="140" height="140" alt="' . htmlspecialchars($url, ENT_QUOTES) . '" style="margin: 4px 0 8px;" />' . "\r\n";
                } catch (\Throwable $e) {
                    $html .= htmlspecialchars('QR code generation failed: ' . $e->getMessage(), ENT_QUOTES) . "\r\n";
                }
            }
        }
        $html .= '----------------' . "\r\n";
    }

    return $html;
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

    while (false !== ($data = fgetcsv($handle, null, ',', '\\'))) {
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

function maskedConfig(array $config): array
{
    $sensitiveKeys = ['password', 'pin', 'username', 'api_key', 'secret'];

    $maskRecursive = function ($value) use (&$maskRecursive, $sensitiveKeys) {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                if (is_string($k) && in_array(strtolower($k), $sensitiveKeys, true)) {
                    $result[$k] = '***';
                } else {
                    $result[$k] = $maskRecursive($v);
                }
            }

            return $result;
        }

        return $value;
    };

    return $maskRecursive($config);
}

if (!empty($_GET['content'])) {
    echo handleDebugPanel($_GET['content'], $config);
}
