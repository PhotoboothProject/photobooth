<?php

declare(strict_types=1);

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;
use Psr\Log\LoggerInterface;

/**
 * File based queue for remote storage uploads.
 *
 * Uploading to FTP/SFTP inside the request that processes a picture makes the
 * guest wait for the network (or for a timeout when the server is unreachable).
 * With this queue the request only drops a small job file and starts a detached
 * worker (`bin/photobooth photobooth:remote-storage:process`) that uploads the
 * files and retries on the next run if the upload fails.
 */
class RemoteStorageQueueService
{
    public const QUEUE_FOLDER = 'remote-queue';

    protected LoggerInterface $logger;

    public function __construct(
        protected ?string $queuePath = null
    ) {
        $this->queuePath ??= FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . self::QUEUE_FOLDER;
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
    }

    public function getQueuePath(): string
    {
        return (string) $this->queuePath;
    }

    public function enqueue(string $fileName): void
    {
        $fileName = basename($fileName);
        $this->ensureQueueFolder();

        $job = [
            'file' => $fileName,
            'attempts' => 0,
            'created' => time(),
        ];
        $jobFile = $this->getJobFile($fileName);
        if (file_put_contents($jobFile . '.tmp', (string) json_encode($job)) === false || !rename($jobFile . '.tmp', $jobFile)) {
            throw new \RuntimeException('Unable to queue remote upload for ' . $fileName);
        }
        $this->logger->debug('Queued for upload.', [$fileName]);
    }

    /**
     * @return array<int, array{file: string, attempts: int, created: int, path: string}>
     */
    public function getJobs(): array
    {
        $jobs = [];
        foreach (glob($this->getQueuePath() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $path) {
            $job = json_decode((string) file_get_contents($path), true);
            if (!is_array($job) || !isset($job['file'])) {
                continue;
            }
            $jobs[] = [
                'file' => (string) $job['file'],
                'attempts' => (int) ($job['attempts'] ?? 0),
                'created' => (int) ($job['created'] ?? 0),
                'path' => $path,
            ];
        }
        usort($jobs, fn (array $a, array $b) => [$a['created'], $a['file']] <=> [$b['created'], $b['file']]);

        return $jobs;
    }

    /**
     * Starts a detached worker process and returns immediately.
     */
    public function startWorker(): void
    {
        $php = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
        if (!is_executable($php)) {
            $php = 'php';
        }
        $console = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'photobooth';
        $cmd = sprintf(
            'nohup %s %s photobooth:remote-storage:process > /dev/null 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($console)
        );
        exec($cmd);
    }

    /**
     * Uploads all queued pictures. Only one worker runs at a time; a second call
     * returns immediately while another worker holds the lock.
     *
     * @param callable(string $file, bool $success, ?string $error): void|null $onResult
     * @return array{uploaded: int, failed: int, remaining: int, locked: bool}
     */
    public function process(?callable $onResult = null): array
    {
        $result = ['uploaded' => 0, 'failed' => 0, 'remaining' => 0, 'locked' => false];

        $this->ensureQueueFolder();
        $lock = fopen($this->getQueuePath() . DIRECTORY_SEPARATOR . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            $result['locked'] = true;
            return $result;
        }

        try {
            $remoteStorage = RemoteStorageService::getInstance();
            $folder = $remoteStorage->getStorageFolder();
            $config = ConfigurationService::getInstance()->getConfiguration();

            // Jobs queued while uploading are picked up by the next loop iteration.
            while (($jobs = $this->getJobs()) !== []) {
                $progress = false;
                foreach ($jobs as $job) {
                    $file = $job['file'];
                    $image = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $file;
                    $thumb = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $file;

                    if (!is_file($image)) {
                        // Picture was deleted before it could be uploaded.
                        $this->logger->debug('Skipping upload, picture no longer exists.', [$file]);
                        @unlink($job['path']);
                        continue;
                    }

                    try {
                        $remoteStorage->write($folder . '/images/' . $file, (string) file_get_contents($image));
                        if (is_file($thumb)) {
                            $remoteStorage->write($folder . '/thumbs/' . $file, (string) file_get_contents($thumb));
                        }
                        @unlink($job['path']);
                        $result['uploaded']++;
                        $progress = true;
                        if ($onResult) {
                            $onResult($file, true, null);
                        }
                    } catch (\Throwable $e) {
                        $job['attempts']++;
                        $path = $job['path'];
                        unset($job['path']);
                        @file_put_contents($path, (string) json_encode($job));
                        $result['failed']++;
                        $this->logger->error('Background upload failed, will retry later.', ['file' => $file, 'attempts' => $job['attempts'], 'error' => $e->getMessage()]);
                        if ($onResult) {
                            $onResult($file, false, $e->getMessage());
                        }
                        // The server is most likely unreachable: stop here and retry on the next run.
                        break 2;
                    }
                }
                if (!$progress) {
                    break;
                }
            }

            if ($result['uploaded'] > 0 && $config['ftp']['create_webpage']) {
                try {
                    $remoteStorage->createWebpage();
                } catch (\Throwable $e) {
                    $this->logger->error('Unable to create remote webpage.', [$e->getMessage()]);
                }
            }
        } finally {
            $result['remaining'] = count($this->getJobs());
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return $result;
    }

    protected function getJobFile(string $fileName): string
    {
        return $this->getQueuePath() . DIRECTORY_SEPARATOR . $fileName . '.json';
    }

    protected function ensureQueueFolder(): void
    {
        if (!is_dir($this->getQueuePath()) && !mkdir($this->getQueuePath(), 0775, true) && !is_dir($this->getQueuePath())) {
            throw new \RuntimeException('Unable to create remote upload queue folder ' . $this->getQueuePath());
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
