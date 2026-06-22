<?php

declare(strict_types=1);

namespace Photobooth\Service;

use Photobooth\Logger\NamedLogger;
use Photobooth\Utility\ProcessUtility;
use Photobooth\Utility\PathUtility;

class UploadQueueService
{
    protected \PDO $db;
    protected NamedLogger $logger;

    public function __construct()
    {
        $this->logger = LoggerService::getInstance()->getLogger('uploadqueue');
        $dbPath = PathUtility::getAbsolutePath('var/run/upload_queue.sqlite');
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->db = new \PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout = 5000');
        $this->initializeDatabase();
    }

    protected function initializeDatabase(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS upload_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            image_file TEXT NOT NULL,
            thumb_file TEXT NOT NULL,
            remote_filename TEXT NOT NULL DEFAULT \'\',
            create_webpage INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT \'pending\',
            retries INTEGER NOT NULL DEFAULT 0,
            error_message TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_upload_queue_status ON upload_queue (status)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_upload_queue_image_file ON upload_queue (image_file)');

        // Migrate: add remote_filename column if missing (existing installations)
        $columns = $this->db->query('PRAGMA table_info(upload_queue)');
        $hasRemoteFilename = false;
        if ($columns !== false) {
            while ($col = $columns->fetch(\PDO::FETCH_ASSOC)) {
                if ($col['name'] === 'remote_filename') {
                    $hasRemoteFilename = true;
                    break;
                }
            }
        }
        if (!$hasRemoteFilename) {
            $this->db->exec('ALTER TABLE upload_queue ADD COLUMN remote_filename TEXT NOT NULL DEFAULT \'\'');
        }
    }

    public function enqueue(string $imageFile, string $thumbFile, bool $createWebpage): int
    {
        $extension = strtolower(pathinfo($imageFile, PATHINFO_EXTENSION));
        $remoteFilename = bin2hex(random_bytes(16)) . '.' . $extension;

        $stmt = $this->db->prepare(
            'INSERT INTO upload_queue (image_file, thumb_file, remote_filename, create_webpage, status) VALUES (:image_file, :thumb_file, :remote_filename, :create_webpage, \'pending\')'
        );
        $stmt->execute([
            ':image_file' => $imageFile,
            ':thumb_file' => $thumbFile,
            ':remote_filename' => $remoteFilename,
            ':create_webpage' => $createWebpage ? 1 : 0,
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->logger->debug('Enqueued upload job', ['id' => $id, 'image' => $imageFile, 'remote' => $remoteFilename]);

        return $id;
    }

    public function getRemoteFilename(string $localFilename): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT remote_filename FROM upload_queue WHERE image_file = :image_file ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':image_file' => $localFilename]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false || $row['remote_filename'] === '') {
            return null;
        }

        return (string) $row['remote_filename'];
    }

    /**
     * @return array{id: int, image_file: string, thumb_file: string, remote_filename: string, create_webpage: int, status: string, retries: int, error_message: string|null, created_at: string, updated_at: string}|null
     */
    public function claimNextPendingJob(): ?array
    {
        while (true) {
            $this->db->exec('BEGIN IMMEDIATE TRANSACTION');

            try {
                $stmt = $this->db->prepare(
                    'SELECT * FROM upload_queue WHERE status = \'pending\' ORDER BY id ASC LIMIT 1'
                );
                $stmt->execute();

                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row === false) {
                    $this->db->commit();

                    return null;
                }

                $claimStmt = $this->db->prepare(
                    'UPDATE upload_queue SET status = \'in_progress\', updated_at = datetime(\'now\') WHERE id = :id AND status = \'pending\''
                );
                $claimStmt->execute([':id' => $row['id']]);

                if ($claimStmt->rowCount() !== 1) {
                    $this->db->rollBack();
                    continue;
                }

                $refreshStmt = $this->db->prepare('SELECT * FROM upload_queue WHERE id = :id');
                $refreshStmt->execute([':id' => $row['id']]);
                $claimedRow = $refreshStmt->fetch(\PDO::FETCH_ASSOC);
                $this->db->commit();

                if ($claimedRow === false) {
                    return null;
                }

                /** @var array{id: int, image_file: string, thumb_file: string, remote_filename: string, create_webpage: int, status: string, retries: int, error_message: string|null, created_at: string, updated_at: string} $claimedRow */
                return $claimedRow;
            } catch (\Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                throw $exception;
            }
        }
    }

    public function ensureWorkerRunning(): void
    {
        $phpBinary = PHP_BINARY;
        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg(PathUtility::getAbsolutePath('bin/photobooth')) . ' photobooth:upload:worker';

        ProcessUtility::startProcess('uploadworker', $command);
    }

    public function markCompleted(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE upload_queue SET status = \'completed\', updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $this->logger->debug('Upload completed', ['id' => $id]);
    }

    public function markFailed(int $id, string $errorMessage, int $maxRetries = 5): void
    {
        $stmt = $this->db->prepare(
            'SELECT retries FROM upload_queue WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return;
        }

        $retries = (int) $row['retries'] + 1;

        if ($retries >= $maxRetries) {
            $updateStmt = $this->db->prepare(
                'UPDATE upload_queue SET status = \'failed\', retries = :retries, error_message = :error, updated_at = datetime(\'now\') WHERE id = :id'
            );
            $this->logger->error('Upload permanently failed', ['id' => $id, 'retries' => $retries, 'error' => $errorMessage]);
        } else {
            $updateStmt = $this->db->prepare(
                'UPDATE upload_queue SET status = \'pending\', retries = :retries, error_message = :error, updated_at = datetime(\'now\') WHERE id = :id'
            );
            $this->logger->debug('Upload failed, will retry', ['id' => $id, 'retries' => $retries, 'error' => $errorMessage]);
        }

        $updateStmt->execute([
            ':id' => $id,
            ':retries' => $retries,
            ':error' => $errorMessage,
        ]);
    }

    public function resetStaleJobs(int $timeoutMinutes = 10): void
    {
        $stmt = $this->db->prepare(
            'UPDATE upload_queue SET status = \'pending\', updated_at = datetime(\'now\') WHERE status = \'in_progress\' AND updated_at < datetime(\'now\', :timeout)'
        );
        $stmt->execute([':timeout' => '-' . $timeoutMinutes . ' minutes']);
    }

    public function getPendingCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM upload_queue WHERE status IN (\'pending\', \'in_progress\')');
        if ($stmt === false) {
            return 0;
        }

        return (int) $stmt->fetchColumn();
    }

    public function getFailedCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM upload_queue WHERE status = \'failed\'');
        if ($stmt === false) {
            return 0;
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array{id: int, image_file: string, status: string, retries: int, error_message: string|null}>
     */
    public function getStatus(): array
    {
        $stmt = $this->db->query('SELECT id, image_file, status, retries, error_message FROM upload_queue ORDER BY id ASC');
        if ($stmt === false) {
            return [];
        }

        /** @var array<int, array{id: int, image_file: string, status: string, retries: int, error_message: string|null}> */
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
