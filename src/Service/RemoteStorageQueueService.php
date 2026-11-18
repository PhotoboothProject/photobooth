<?php

namespace Photobooth\Service;

use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\PathUtility;

/**
 * Class RemoteStorageQueueService
 *
 * Persistent queue for asynchronous remote storage (FTP/SFTP) uploads.
 * Entries are tracked in a JSON file so uploads survive crashes and
 * their outcome (done/failed) can be inspected and retried.
 */
class RemoteStorageQueueService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_UPLOADING = 'uploading';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public const MAX_ATTEMPTS = 3;
    public const STALE_UPLOADING_SECONDS = 600;
    public const DONE_RETENTION_SECONDS = 86400;
    public const MAX_ENTRIES = 500;

    protected string $queueFile;
    protected string $drainLockFile;
    protected NamedLogger $logger;
    /** @var resource|null */
    protected $drainLockHandle = null;

    public function __construct()
    {
        $this->queueFile = PathUtility::getAbsolutePath('var/run/remotestorage_queue.json');
        $this->drainLockFile = PathUtility::getAbsolutePath('var/run/remotestorage_drain.lock');
        $this->logger = LoggerService::getInstance()->getLogger('remotestorage');
    }

    /**
     * Add files to the queue. Existing entries with the same name are reset to pending.
     *
     * @param string[] $filenames
     */
    public function enqueue(array $filenames): void
    {
        if (empty($filenames)) {
            return;
        }

        $this->modify(function (array $data) use ($filenames): array {
            $now = time();
            foreach ($filenames as $filename) {
                $data['entries'][$filename] = [
                    'file' => $filename,
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'error' => null,
                    'enqueuedAt' => $now,
                    'updatedAt' => $now,
                    'uploadedAt' => null,
                ];
            }

            return $data;
        });
        $this->logger->debug('Queued for upload', $filenames);
    }

    /**
     * Claim the next pending entry and mark it uploading.
     * Stale uploading entries (crashed drain) are reset to pending first.
     * Files listed in $priority are claimed before the FIFO backlog.
     *
     * @param string[] $priority
     */
    public function claimNext(array $priority = []): ?array
    {
        $claimed = null;
        $this->modify(function (array $data) use ($priority, &$claimed): array {
            $now = time();
            foreach ($data['entries'] as $filename => $entry) {
                if ($entry['status'] === self::STATUS_UPLOADING && $now - $entry['updatedAt'] > self::STALE_UPLOADING_SECONDS) {
                    $data['entries'][$filename]['status'] = self::STATUS_PENDING;
                    $data['entries'][$filename]['updatedAt'] = $now;
                }
            }

            $pending = array_filter($data['entries'], fn ($entry) => $entry['status'] === self::STATUS_PENDING);
            if (empty($pending)) {
                return $data;
            }

            $next = null;
            foreach ($priority as $filename) {
                if (isset($pending[$filename])) {
                    $next = $filename;
                    break;
                }
            }
            if ($next === null) {
                uasort($pending, fn ($a, $b) => $a['enqueuedAt'] <=> $b['enqueuedAt']);
                $next = array_key_first($pending);
            }

            $data['entries'][$next]['status'] = self::STATUS_UPLOADING;
            $data['entries'][$next]['attempts']++;
            $data['entries'][$next]['updatedAt'] = $now;
            $claimed = $data['entries'][$next];

            return $data;
        });

        return $claimed;
    }

    /**
     * Mark an entry as uploaded. Returns false if the entry no longer
     * exists (photo was deleted while the upload was in flight).
     */
    public function markDone(string $filename): bool
    {
        $found = false;
        $this->modify(function (array $data) use ($filename, &$found): array {
            if (isset($data['entries'][$filename])) {
                $found = true;
                $now = time();
                $data['entries'][$filename]['status'] = self::STATUS_DONE;
                $data['entries'][$filename]['error'] = null;
                $data['entries'][$filename]['updatedAt'] = $now;
                $data['entries'][$filename]['uploadedAt'] = $now;
            }

            return $data;
        });

        return $found;
    }

    /**
     * Record a failed attempt. The entry goes back to pending until
     * MAX_ATTEMPTS is reached (or $terminal is set), then stays failed
     * until retried manually.
     */
    public function markFailed(string $filename, string $error, bool $terminal = false): void
    {
        $this->modify(function (array $data) use ($filename, $error, $terminal): array {
            if (isset($data['entries'][$filename])) {
                $entry = $data['entries'][$filename];
                $failed = $terminal || $entry['attempts'] >= self::MAX_ATTEMPTS;
                $data['entries'][$filename]['status'] = $failed ? self::STATUS_FAILED : self::STATUS_PENDING;
                $data['entries'][$filename]['error'] = $error;
                $data['entries'][$filename]['updatedAt'] = time();
            }

            return $data;
        });
    }

    /**
     * Reset failed entries (or a single one) back to pending.
     * Returns the number of entries reset.
     */
    public function retryFailed(?string $filename = null): int
    {
        $count = 0;
        $this->modify(function (array $data) use ($filename, &$count): array {
            foreach ($data['entries'] as $name => $entry) {
                if ($entry['status'] !== self::STATUS_FAILED || ($filename !== null && $name !== $filename)) {
                    continue;
                }
                $data['entries'][$name]['status'] = self::STATUS_PENDING;
                $data['entries'][$name]['attempts'] = 0;
                $data['entries'][$name]['error'] = null;
                $data['entries'][$name]['updatedAt'] = time();
                $count++;
            }

            return $data;
        });

        return $count;
    }

    /**
     * Remove an entry (e.g. when the photo is deleted) and return it,
     * so the caller can inspect the previous status.
     */
    public function remove(string $filename): ?array
    {
        $removed = null;
        $this->modify(function (array $data) use ($filename, &$removed): array {
            if (isset($data['entries'][$filename])) {
                $removed = $data['entries'][$filename];
                unset($data['entries'][$filename]);
            }

            return $data;
        });

        return $removed;
    }

    /**
     * Remove all done and failed entries.
     */
    public function clearFinished(): int
    {
        $count = 0;
        $this->modify(function (array $data) use (&$count): array {
            foreach ($data['entries'] as $name => $entry) {
                if (in_array($entry['status'], [self::STATUS_DONE, self::STATUS_FAILED], true)) {
                    unset($data['entries'][$name]);
                    $count++;
                }
            }

            return $data;
        });

        return $count;
    }

    /**
     * @param string[] $filenames
     */
    public function getStatusForFiles(array $filenames): array
    {
        $entries = $this->read()['entries'];
        $result = [];
        foreach ($filenames as $filename) {
            if (isset($entries[$filename])) {
                $result[$filename] = [
                    'status' => $entries[$filename]['status'],
                    'attempts' => $entries[$filename]['attempts'],
                    'error' => $entries[$filename]['error'],
                ];
            } else {
                $result[$filename] = [
                    'status' => 'unknown',
                    'attempts' => 0,
                    'error' => null,
                ];
            }
        }

        return $result;
    }

    public function getCounts(): array
    {
        $counts = [
            self::STATUS_PENDING => 0,
            self::STATUS_UPLOADING => 0,
            self::STATUS_DONE => 0,
            self::STATUS_FAILED => 0,
        ];
        foreach ($this->read()['entries'] as $entry) {
            if (isset($counts[$entry['status']])) {
                $counts[$entry['status']]++;
            }
        }

        return $counts;
    }

    public function getEntries(): array
    {
        return $this->read()['entries'];
    }

    /**
     * Cache whether the remote gallery webpage was already created for a
     * storage folder, to avoid remote fileExists checks on every upload.
     */
    public function isWebpageCreated(string $storageFolder): bool
    {
        return $this->read()['webpage']['createdFor'] === $storageFolder;
    }

    public function markWebpageCreated(string $storageFolder): void
    {
        $this->modify(function (array $data) use ($storageFolder): array {
            $data['webpage']['createdFor'] = $storageFolder;

            return $data;
        });
    }

    /**
     * Try to become the only running drain worker. Non-blocking; the lock
     * is released explicitly or by the OS when the process ends.
     */
    public function acquireDrainLock(): bool
    {
        if ($this->drainLockHandle !== null) {
            return true;
        }
        $handle = fopen($this->drainLockFile, 'c');
        if ($handle === false) {
            return false;
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }
        $this->drainLockHandle = $handle;

        return true;
    }

    public function releaseDrainLock(): void
    {
        if ($this->drainLockHandle !== null) {
            flock($this->drainLockHandle, LOCK_UN);
            fclose($this->drainLockHandle);
            $this->drainLockHandle = null;
        }
    }

    /**
     * Read the queue without locking (cheap, for status polling).
     */
    public function read(): array
    {
        if (!file_exists($this->queueFile)) {
            return $this->getDefaultData();
        }
        $contents = file_get_contents($this->queueFile);
        if ($contents === false) {
            return $this->getDefaultData();
        }

        return $this->decodeOrRecover($contents);
    }

    /**
     * Apply a mutation to the queue atomically: the file is locked
     * exclusively for the whole read-modify-write cycle so concurrent
     * requests (enqueue, drain, delete, admin) cannot lose updates.
     */
    protected function modify(callable $mutator): array
    {
        $handle = fopen($this->queueFile, 'c+');
        if ($handle === false) {
            throw new \Exception('Unable to open remote storage queue file: ' . $this->queueFile);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \Exception('Unable to lock remote storage queue file: ' . $this->queueFile);
            }

            $contents = (string) stream_get_contents($handle);
            $data = $this->decodeOrRecover($contents);
            $data = $mutator($data);
            $data = $this->prune($data);

            $encoded = json_encode($data);
            if ($encoded === false) {
                throw new \Exception('Failed to encode remote storage queue to JSON: ' . json_last_error_msg());
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $encoded);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $data;
    }

    protected function decodeOrRecover(string $contents): array
    {
        if (trim($contents) === '') {
            return $this->getDefaultData();
        }
        $data = json_decode($contents, true);
        if (!is_array($data) || !isset($data['entries']) || !is_array($data['entries'])) {
            $this->logger->warning('Remote storage queue file corrupt, resetting.', [$this->queueFile]);

            return $this->getDefaultData();
        }
        $data['version'] = $data['version'] ?? 1;
        $data['webpage'] = is_array($data['webpage'] ?? null) ? $data['webpage'] : ['createdFor' => null];
        $data['webpage']['createdFor'] = $data['webpage']['createdFor'] ?? null;

        return $data;
    }

    /**
     * Keep the queue file small: drop old done entries and cap the total,
     * sacrificing oldest done first, then oldest failed.
     */
    protected function prune(array $data): array
    {
        $now = time();
        foreach ($data['entries'] as $name => $entry) {
            if ($entry['status'] === self::STATUS_DONE && $now - $entry['updatedAt'] > self::DONE_RETENTION_SECONDS) {
                unset($data['entries'][$name]);
            }
        }

        $overflow = count($data['entries']) - self::MAX_ENTRIES;
        if ($overflow > 0) {
            foreach ([self::STATUS_DONE, self::STATUS_FAILED] as $status) {
                if ($overflow <= 0) {
                    break;
                }
                $candidates = array_filter($data['entries'], fn ($entry) => $entry['status'] === $status);
                uasort($candidates, fn ($a, $b) => $a['updatedAt'] <=> $b['updatedAt']);
                foreach (array_keys($candidates) as $name) {
                    if ($overflow <= 0) {
                        break;
                    }
                    unset($data['entries'][$name]);
                    $overflow--;
                }
            }
        }

        return $data;
    }

    protected function getDefaultData(): array
    {
        return [
            'version' => 1,
            'webpage' => ['createdFor' => null],
            'entries' => [],
        ];
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
