<?php

declare(strict_types=1);

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;
use Photobooth\Environment;
use Photobooth\Utility\PathUtility;
use Psr\Log\LoggerInterface;

/** File queue shared by capture requests and short-lived upload workers. */
class RemoteStorageQueueService
{
    public const QUEUE_FOLDER = 'remote-queue';

    protected LoggerInterface $logger;

    public function __construct(protected ?string $queuePath = null)
    {
        $this->queuePath ??= FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . self::QUEUE_FOLDER;
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
    }

    public function getQueuePath(): string
    {
        return (string) $this->queuePath;
    }

    public function enqueue(string $fileName): void
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/D', $fileName) || basename($fileName) !== $fileName || in_array($fileName, ['.', '..'], true)) {
            throw new \InvalidArgumentException('Invalid remote upload filename.');
        }
        $gate = self::openLock($this->getQueuePath(), '.dispatch');
        try {
            $remote = RemoteStorageService::getInstance();
            $remote->refreshConfiguration();
            $destination = $remote->getDestinationIdentity();
            foreach (glob($this->getQueuePath() . '/' . $fileName . '.*.json') ?: [] as $path) {
                $contents = @file_get_contents($path);
                $pending = $contents === false ? null : json_decode($contents, true);
                if (($pending['file'] ?? null) === $fileName && ($pending['destination'] ?? null) === $destination && ($pending['type'] ?? 'image') === 'image') {
                    return;
                }
            }
            $this->saveJob($this->getQueuePath() . '/' . $fileName . '.' . bin2hex(random_bytes(8)) . '.json', [
                'file' => $fileName,
                'type' => 'image',
                'destination' => $destination,
                'created' => time(),
            ]);
        } finally {
            self::releaseLock($gate);
        }
    }

    /** @return array<int, array{file: string, type: string, destination: string, created: int, path: string}> */
    public function getJobs(): array
    {
        $jobs = [];
        foreach (glob($this->getQueuePath() . '/*.json') ?: [] as $path) {
            $contents = @file_get_contents($path);
            if ($contents === false) {
                continue;
            }
            $job = json_decode($contents, true);
            $jobs[] = [
                'file' => is_string($job['file'] ?? null) ? $job['file'] : '',
                'type' => is_string($job['type'] ?? null) ? $job['type'] : 'image',
                'destination' => is_string($job['destination'] ?? null) ? $job['destination'] : '',
                'created' => (int) ($job['created'] ?? 0),
                'path' => $path,
            ];
        }
        usort($jobs, fn (array $a, array $b) => [$a['created'], $a['file']] <=> [$b['created'], $b['file']]);
        return $jobs;
    }

    /** Starts only a lightweight contender; it takes the worker lock before booting the app. */
    public function startWorker(): void
    {
        $gate = self::openLock($this->getQueuePath(), '.dispatch');
        try {
            $lock = self::acquireWorkerLock($this->getQueuePath());
            if ($lock === false) {
                return;
            }
            self::releaseLock($lock);
            $binaryName = Environment::isWindows() ? 'php.exe' : 'php';
            $candidates = [dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . $binaryName];
            if (in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
                array_unshift($candidates, PHP_BINARY);
            }
            // PHP_BINDIR is a build-time path; relocated Windows installs need their actual directory.
            $ini = php_ini_loaded_file();
            if ($ini !== false) {
                $candidates[] = dirname($ini) . DIRECTORY_SEPARATOR . $binaryName;
            }
            $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . $binaryName;
            $php = null;
            foreach ($candidates as $candidate) {
                if (is_file($candidate) && is_executable($candidate)) {
                    $php = $candidate;
                    break;
                }
            }
            if ($php === null) {
                throw new \RuntimeException('PHP CLI executable not found alongside PHP or its configuration.');
            }
            $console = PathUtility::getAbsolutePath('bin/remote-storage-worker');
            if (!is_file($console) || !is_readable($console)) {
                throw new \RuntimeException('Remote upload worker entry point is missing or unreadable.');
            }
            if (Environment::isWindows()) {
                // Reject cmd expansion characters rather than silently altering an executable path.
                if (strpbrk($php . $console, "\"%!?^&|<>\r\n") !== false) {
                    throw new \RuntimeException('Unsupported shell characters in worker path.');
                }
                $command = 'start "" /B "' . $php . '" "' . $console . '" > NUL 2>&1';
                $pipe = popen($command, 'r');
                if ($pipe === false || pclose($pipe) !== 0) {
                    throw new \RuntimeException('Unable to start remote upload worker.');
                }
            } else {
                exec('nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($console) . ' > /dev/null 2>&1 &', $output, $exit);
                if ($exit !== 0) {
                    throw new \RuntimeException('Unable to start remote upload worker.');
                }
            }
        } finally {
            self::releaseLock($gate);
        }
    }

    /**
     * Acquire before application bootstrap, including for automatic contenders.
     * @return resource|false
     */
    public static function acquireWorkerLock(string $queuePath)
    {
        return self::openLock($queuePath, '.lock', true);
    }

    /**
     * @param callable(string, bool, ?string): void|null $onResult
     * @param resource|null $lock Already held by the lightweight automatic entry point.
     * @return array{uploaded: int, failed: int, remaining: int, locked: bool}
     */
    public function process(?callable $onResult = null, $lock = null): array
    {
        $result = ['uploaded' => 0, 'failed' => 0, 'remaining' => 0, 'locked' => false];
        $lock ??= self::acquireWorkerLock($this->getQueuePath());
        if ($lock === false) {
            $result['locked'] = true;
            return $result;
        }
        $attempted = [];
        $stop = false;
        try {
            while (true) {
                $jobs = array_filter($this->getJobs(), fn (array $job) => !isset($attempted[$job['path']]));
                if ($jobs === []) {
                    // Producers use the same gate, so no job can arrive between the last scan and unlock.
                    $gate = self::openLock($this->getQueuePath(), '.dispatch');
                    try {
                        $jobs = array_filter($this->getJobs(), fn (array $job) => !isset($attempted[$job['path']]));
                        if ($jobs === []) {
                            $result['remaining'] = count(glob($this->getQueuePath() . '/*.json') ?: []);
                            self::releaseLock($lock);
                            $lock = null;
                            return $result;
                        }
                    } finally {
                        self::releaseLock($gate);
                    }
                }
                foreach ($jobs as $job) {
                    $attempted[$job['path']] = true;
                    $configuration = ConfigurationService::getInstance();
                    $configuration->load();
                    $config = $configuration->getConfiguration()['ftp'];
                    if (!$config['enabled']) {
                        $stop = true;
                        break;
                    }
                    $remote = RemoteStorageService::getInstance();
                    $remote->refreshConfiguration();
                    $file = $job['file'];
                    $valid = $job['type'] === 'webpage' || ($job['type'] === 'image' && preg_match('/^[A-Za-z0-9._-]+$/D', $file) && !in_array($file, ['.', '..'], true));
                    if (!$valid || $job['destination'] === '' || $job['destination'] !== $remote->getDestinationIdentity()) {
                        $this->logger->warning('Discarding remote upload job: invalid or changed destination.', ['job' => basename($job['path'])]);
                        $this->removeJob($job['path']);
                        continue;
                    }
                    if ($job['type'] === 'webpage' && !$config['create_webpage']) {
                        $this->removeJob($job['path']);
                        continue;
                    }
                    $image = FolderEnum::IMAGES->absolute() . '/' . $file;
                    if ($job['type'] === 'image' && !is_file($image)) {
                        $this->removeJob($job['path']);
                        continue;
                    }
                    try {
                        if ($job['type'] === 'webpage') {
                            $remote->createWebpage();
                        } else {
                            $folder = $remote->getStorageFolder();
                            $this->uploadFile($remote, $folder . '/images/' . $file, $image);
                            $thumb = FolderEnum::THUMBS->absolute() . '/' . $file;
                            if (is_file($thumb)) {
                                $this->uploadFile($remote, $folder . '/thumbs/' . $file, $thumb);
                            }
                            if ($config['create_webpage']) {
                                $pagePath = $this->getQueuePath() . '/webpage-' . $job['destination'] . '.json';
                                if (!is_file($pagePath)) {
                                    $pageJob = $job;
                                    unset($pageJob['path']);
                                    $pageJob['file'] = '[webpage]';
                                    $pageJob['type'] = 'webpage';
                                    $this->saveJob($pagePath, $pageJob);
                                }
                            }
                            $result['uploaded']++;
                        }
                        $this->removeJob($job['path']);
                        if ($onResult !== null) {
                            $onResult($file, true, null);
                        }
                    } catch (\Throwable $error) {
                        $result['failed']++;
                        // Exception messages can include connection credentials; report the type only.
                        $reason = get_class($error);
                        $this->logger->error('Remote upload failed; retained for a later run.', ['file' => $file, 'error' => $reason]);
                        if ($onResult !== null) {
                            $onResult($file, false, $reason);
                        }
                        if (!$error instanceof \UnexpectedValueException && $remote->shouldStopAfterUploadFailure($error)) {
                            $stop = true;
                            break;
                        }
                    }
                }
                if ($stop) {
                    break;
                }
            }
        } finally {
            if (is_resource($lock)) {
                $gate = self::openLock($this->getQueuePath(), '.dispatch');
                try {
                    $result['remaining'] = count(glob($this->getQueuePath() . '/*.json') ?: []);
                    self::releaseLock($lock);
                } finally {
                    self::releaseLock($gate);
                }
            }
        }
        return $result;
    }

    private function uploadFile(RemoteStorageService $remote, string $destination, string $source): void
    {
        $contents = @file_get_contents($source);
        if ($contents === false) {
            throw new \UnexpectedValueException('Unable to read local upload source.');
        }
        $remote->write($destination, $contents);
    }

    private function saveJob(string $path, array $job): void
    {
        unset($job['path']);
        $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $json = json_encode($job, JSON_THROW_ON_ERROR);
        if (file_put_contents($temporary, $json) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to persist remote upload job.');
        }
    }

    private function removeJob(string $path): void
    {
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException('Unable to remove completed remote upload job.');
        }
    }

    /** @return ($nonBlocking is true ? resource|false : resource) */
    private static function openLock(string $queuePath, string $name, bool $nonBlocking = false)
    {
        if (!is_dir($queuePath) && !mkdir($queuePath, 0775, true) && !is_dir($queuePath)) {
            throw new \RuntimeException('Unable to create remote upload queue.');
        }
        $lock = fopen($queuePath . '/' . $name, 'c');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open remote upload lock.');
        }
        if (!flock($lock, LOCK_EX | ($nonBlocking ? LOCK_NB : 0))) {
            fclose($lock);
            if ($nonBlocking) {
                return false;
            }
            throw new \RuntimeException('Unable to acquire remote upload lock.');
        }
        return $lock;
    }

    /** @param resource $lock */
    private static function releaseLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    public static function getInstance(): self
    {
        return $GLOBALS[self::class] ??= new self();
    }
}
