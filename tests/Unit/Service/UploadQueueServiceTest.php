<?php

namespace Photobooth\Tests\Unit\Service;

use PDO;
use Photobooth\Service\LoggerService;
use Photobooth\Service\UploadQueueService;
use PHPUnit\Framework\TestCase;

final class UploadQueueServiceTest extends TestCase
{
    public function testClaimNextPendingJobMarksTheOldestPendingJobInProgress(): void
    {
        $databasePath = $this->createDatabasePath();
        $queue = $this->createQueueService($databasePath);

        $firstId = $queue->enqueue('first.jpg', 'first-thumb.jpg', false);
        $secondId = $queue->enqueue('second.jpg', 'second-thumb.jpg', false);

        $job = $queue->claimNextPendingJob();

        $this->assertNotNull($job);
        $this->assertSame($firstId, $job['id']);
        $this->assertSame('in_progress', $job['status']);

        $status = $queue->getStatus();
        $this->assertCount(2, $status);
        $this->assertSame($firstId, $status[0]['id']);
        $this->assertSame('in_progress', $status[0]['status']);
        $this->assertSame($secondId, $status[1]['id']);
        $this->assertSame('pending', $status[1]['status']);

        @unlink($databasePath);
    }

    public function testClaimNextPendingJobDoesNotReturnAnAlreadyClaimedJob(): void
    {
        $databasePath = $this->createDatabasePath();
        $producer = $this->createQueueService($databasePath);
        $firstConsumer = $this->createQueueService($databasePath);
        $secondConsumer = $this->createQueueService($databasePath);

        $jobId = $producer->enqueue('claimed.jpg', 'claimed-thumb.jpg', false);

        $claimedJob = $firstConsumer->claimNextPendingJob();
        $nextJob = $secondConsumer->claimNextPendingJob();

        $this->assertNotNull($claimedJob);
        $this->assertSame($jobId, $claimedJob['id']);
        $this->assertNull($nextJob);

        @unlink($databasePath);
    }

    private function createQueueService(string $databasePath): UploadQueueService
    {
        return new class ($databasePath) extends UploadQueueService {
            public function __construct(string $databasePath)
            {
                $this->logger = LoggerService::getInstance()->getLogger('uploadqueue-test');
                $this->db = new PDO('sqlite:' . $databasePath);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->exec('PRAGMA busy_timeout = 5000');
                $this->initializeDatabase();
            }
        };
    }

    private function createDatabasePath(): string
    {
        return tempnam(sys_get_temp_dir(), 'upload-queue-test-') ?: $this->fail('Failed to create temp database path.');
    }
}
