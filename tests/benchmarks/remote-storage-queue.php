<?php

declare(strict_types=1);

// Run: php tests/benchmarks/remote-storage-queue.php --sizes=100,1000,5000 [--baseline]
// Native runner measurements; no network, application bootstrap or private config.
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\PathUtility;

const BASELINE = 'b32f5c7';

function childOutput(array $command): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start benchmark child.');
    }
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $output === false || $error === false) {
        throw new RuntimeException($error === false ? 'Unable to read benchmark child output.' : $error);
    }
    return $output;
}

$options = getopt('', ['sizes:', 'baseline', 'child:', 'size:', 'case:']);
if ($options === false) {
    throw new InvalidArgumentException('Unable to parse benchmark options.');
}
if (!isset($options['child'])) {
    $sizesOption = $options['sizes'] ?? '100,1000,5000';
    if (!is_string($sizesOption) || !preg_match('/^[1-9][0-9]*(,[1-9][0-9]*)*$/D', $sizesOption)) {
        throw new InvalidArgumentException('Sizes must be positive integers.');
    }
    $sizes = array_map('intval', explode(',', $sizesOption));
    $report = ['php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'runner' => 'native PHP CLI, synthetic local files, warm filesystem cache, offline fake remote', 'repetitions' => 3, 'results' => []];
    foreach (isset($options['baseline']) ? [BASELINE, 'current'] : ['current'] as $version) {
        foreach ($sizes as $size) {
            foreach (['scan', 'offline'] as $case) {
                $samples = [];
                for ($repeat = 0; $repeat < 3; $repeat++) {
                    $samples[] = json_decode(childOutput([PHP_BINARY, __FILE__, '--child=' . $version, '--size=' . $size, '--case=' . $case]), true, 512, JSON_THROW_ON_ERROR);
                }
                $medians = [];
                foreach (array_keys($samples[0]) as $metric) {
                    $values = array_column($samples, $metric);
                    sort($values);
                    $medians[$metric] = $values[1];
                }
                $report['results'][] = ['version' => $version, 'size' => $size, 'case' => $case, 'median' => $medians, 'samples' => $samples];
            }
        }
    }
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';
if ($options['child'] === BASELINE) {
    $source = childOutput(['git', 'show', BASELINE . ':src/Service/RemoteStorageQueueService.php']);
    eval(substr($source, 5));
} elseif ($options['child'] !== 'current') {
    throw new InvalidArgumentException('Unknown benchmark version.');
}
$sizeOption = $options['size'] ?? '';
if (!is_string($sizeOption) || !preg_match('/^[1-9][0-9]*$/D', $sizeOption)) {
    throw new InvalidArgumentException('Child size must be a positive integer.');
}

$root = sys_get_temp_dir() . '/photobooth-queue-benchmark-' . bin2hex(random_bytes(8));
mkdir($root . '/data/images', 0700, true);
mkdir($root . '/data/remote-queue', 0700, true);
mkdir($root . '/var/log', 0700, true);
PathUtility::$rootPathCache = $root . '/';
$GLOBALS[ConfigurationService::class] = new class () extends ConfigurationService {
    public function load(): void
    {
    }
    public function getConfiguration(): array
    {
        return ['ftp' => ['enabled' => true, 'create_webpage' => false], 'dev' => ['loglevel' => 0]];
    }
};
$remote = new class () extends RemoteStorageService {
    public ?int $firstFailure = null;
    public function __construct()
    {
    }
    public function refreshConfiguration(): void
    {
    }
    public function getDestinationIdentity(): string
    {
        return str_repeat('a', 64);
    }
    public function getStorageFolder(): string
    {
        return 'benchmark';
    }
    public function write(string $location, string $contents): void
    {
        $this->firstFailure ??= hrtime(true);
        throw new RuntimeException('Synthetic offline destination.');
    }
    public function shouldStopAfterUploadFailure(Throwable $error): bool
    {
        return true;
    }
};
$GLOBALS[RemoteStorageService::class] = $remote;
try {
    $size = (int) $sizeOption;
    for ($index = 0; $index < $size; $index++) {
        $file = sprintf('%08d.jpg', $index);
        file_put_contents($root . '/data/remote-queue/' . $file . '.json', json_encode(['file' => $file, 'type' => 'image', 'destination' => $remote->getDestinationIdentity(), 'created' => $index], JSON_THROW_ON_ERROR));
    }
    file_put_contents($root . '/data/images/00000000.jpg', 'synthetic image');
    $queue = new RemoteStorageQueueService($root . '/data/remote-queue');
    memory_reset_peak_usage();
    $start = hrtime(true);
    if ($options['case'] === 'scan') {
        $jobs = $queue->getJobs();
        if (count($jobs) !== $size || $jobs[0]['file'] !== '00000000.jpg') {
            throw new RuntimeException('Scan did not return the expected jobs.');
        }
    } elseif ($options['case'] === 'offline') {
        $result = $queue->process();
        if ($remote->firstFailure === null || $result['failed'] !== 1 || $result['remaining'] !== $size || $result['uploaded'] !== 0) {
            throw new RuntimeException('Offline processing did not retain the expected jobs.');
        }
    } else {
        throw new InvalidArgumentException('Unknown benchmark case.');
    }
    $metrics = ['elapsed_ms' => (hrtime(true) - $start) / 1e6, 'peak_memory_bytes' => memory_get_peak_usage(true)];
    if ($remote->firstFailure !== null) {
        $metrics['first_failed_upload_ms'] = ($remote->firstFailure - $start) / 1e6;
    }
    echo json_encode($metrics, JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
