<?php

declare(strict_types=1);

namespace Photobooth\Tests\Unit\Service;

use League\Flysystem\UnableToWriteFile;
use Photobooth\Logger\NamedLogger;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\PathUtility;
use PHPUnit\Framework\TestCase;

final class RemoteStorageQueueServiceTest extends TestCase
{
    private string $root;
    private mixed $previousRoot;
    private array $globals = [];
    private array $config;
    private RemoteStorageService&\PHPUnit\Framework\MockObject\MockObject $remote;
    private RemoteStorageQueueService $queue;
    private bool $stopAfterFailure = false;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/photobooth-queue-' . bin2hex(random_bytes(8));
        foreach (['data/images', 'data/thumbs', 'data/remote-queue', 'config', 'var/log'] as $folder) {
            mkdir($this->root . '/' . $folder, 0700, true);
        }
        $this->previousRoot = PathUtility::$rootPathCache;
        PathUtility::$rootPathCache = $this->root . '/';
        foreach ([ConfigurationService::class, LoggerService::class, RemoteStorageService::class] as $class) {
            $this->globals[$class] = $GLOBALS[$class] ?? null;
        }
        $this->config = ['ftp' => ['enabled' => true, 'title' => 'event-a', 'create_webpage' => false]];
        $configuration = $this->createStub(ConfigurationService::class);
        $configuration->method('getConfiguration')->willReturnCallback(fn () => $this->config);
        $GLOBALS[ConfigurationService::class] = $configuration;
        $logger = $this->createStub(LoggerService::class);
        $logger->method('getLogger')->willReturn($this->createStub(NamedLogger::class));
        $GLOBALS[LoggerService::class] = $logger;
        $this->remote = $this->createMock(RemoteStorageService::class);
        $this->remote->method('getStorageFolder')->willReturnCallback(fn () => $this->config['ftp']['title']);
        $this->remote->method('getDestinationIdentity')->willReturnCallback(fn () => hash('sha256', $this->config['ftp']['title']));
        $this->remote->method('shouldStopAfterUploadFailure')->willReturnCallback(fn () => $this->stopAfterFailure);
        $GLOBALS[RemoteStorageService::class] = $this->remote;
        $this->queue = new RemoteStorageQueueService($this->root . '/data/remote-queue');
    }

    protected function tearDown(): void
    {
        foreach ($this->globals as $class => $instance) {
            if ($instance === null) {
                unset($GLOBALS[$class]);
            } else {
                $GLOBALS[$class] = $instance;
            }
        }
        PathUtility::$rootPathCache = $this->previousRoot;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    private function enqueue(string $name): void
    {
        file_put_contents($this->root . '/data/images/' . $name, 'synthetic-image');
        file_put_contents($this->root . '/data/thumbs/' . $name, 'synthetic-thumb');
        $this->queue->enqueue($name);
    }

    public function testDuplicatePendingJobIsNotRewrittenButDifferentTargetIsIndependent(): void
    {
        $this->remote->expects($this->never())->method('write');
        $this->enqueue('a.jpg');
        $original = $this->queue->getJobs()[0];
        $contents = file_get_contents($original['path']);
        touch($original['path'], 946684800);
        $this->queue->enqueue('a.jpg');
        $this->assertCount(1, $this->queue->getJobs());
        clearstatcache();
        $this->assertSame($contents, file_get_contents($original['path']));
        $this->assertSame(946684800, filemtime($original['path']));
        $this->config['ftp']['title'] = 'event-b';
        $this->queue->enqueue('a.jpg');
        $this->assertCount(2, $this->queue->getJobs());
    }

    public function testFailedRunsLeaveJobFileUnchangedAndSuccessfulRetryRemovesIt(): void
    {
        $this->enqueue('a.jpg');
        $job = $this->queue->getJobs()[0];
        $contents = file_get_contents($job['path']);
        touch($job['path'], 946684800);
        $failures = [true, true, false, false];
        $this->remote->expects($this->exactly(4))->method('write')->willReturnCallback(function () use (&$failures): void {
            if (array_shift($failures)) {
                throw new \RuntimeException('offline');
            }
        });
        foreach ([true, false] as $stop) {
            $this->stopAfterFailure = $stop;
            $this->assertSame(1, $this->queue->process()['failed']);
            clearstatcache();
            $this->assertSame($contents, file_get_contents($job['path']));
            $this->assertSame(946684800, filemtime($job['path']));
        }
        $this->assertSame(1, $this->queue->process()['uploaded']);
        $this->assertFileDoesNotExist($job['path']);
        $this->queue->enqueue('a.jpg');
        $this->assertCount(1, $this->queue->getJobs());
    }

    public function testMissingLocalImageRemovesPendingJob(): void
    {
        $this->enqueue('gone.jpg');
        unlink($this->root . '/data/images/gone.jpg');
        $this->remote->expects($this->never())->method('write');
        $this->assertSame(0, $this->queue->process()['remaining']);
        $this->assertSame([], $this->queue->getJobs());
    }

    public function testFileFailureDoesNotBlockFollowingJobOrRetryInSameRun(): void
    {
        $this->enqueue('a.jpg');
        $this->enqueue('b.jpg');
        $attempts = [];
        $this->remote->expects($this->exactly(3))->method('write')->willReturnCallback(function (string $path) use (&$attempts): void {
            $attempts[] = $path;
            if (str_ends_with($path, '/a.jpg')) {
                throw UnableToWriteFile::atLocation($path, 'permission denied');
            }
        });
        $result = $this->queue->process();
        $this->assertSame(['event-a/images/a.jpg', 'event-a/images/b.jpg', 'event-a/thumbs/b.jpg'], $attempts);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(1, $result['remaining']);
        $this->assertSame('a.jpg', $this->queue->getJobs()[0]['file']);
    }

    public function testWebpageFailureSurvivesEmptyImageQueueAndRetries(): void
    {
        $this->config['ftp']['create_webpage'] = true;
        $this->enqueue('last.jpg');
        $calls = 0;
        $pagePath = '';
        $contents = false;
        $this->remote->method('createWebpage')->willReturnCallback(function () use (&$calls, &$pagePath, &$contents): void {
            if (++$calls === 1) {
                $pagePath = $this->queue->getJobs()[0]['path'];
                $contents = file_get_contents($pagePath);
                touch($pagePath, 946684800);
                throw new \RuntimeException('connection lost');
            }
        });
        $this->remote->expects($this->exactly(2))->method('write');
        $first = $this->queue->process();
        $this->assertGreaterThan(0, $first['failed']);
        $this->assertNotEmpty($this->queue->getJobs());
        clearstatcache();
        $this->assertSame($contents, file_get_contents($pagePath));
        $this->assertSame(946684800, filemtime($pagePath));
        $second = $this->queue->process();
        $this->assertSame(0, $second['failed']);
        $this->assertSame([], $this->queue->getJobs());
        $this->assertSame(2, $calls);
    }

    public function testConnectionFailureStopsBeforeFollowingJob(): void
    {
        $this->stopAfterFailure = true;
        $this->enqueue('a.jpg');
        $this->enqueue('b.jpg');
        $this->remote->expects($this->once())->method('write')->willThrowException(new \RuntimeException('offline'));
        $result = $this->queue->process();
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, $result['remaining']);
        $this->assertSame(0, $result['uploaded']);
    }

    public function testLegacyJobAndTraversalAreDiscardedWithoutRemoteWrites(): void
    {
        file_put_contents($this->queue->getQueuePath() . '/old.json', json_encode(['file' => 'old.jpg']));
        file_put_contents($this->queue->getQueuePath() . '/invalid.json', json_encode(['file' => '../secret', 'destination' => hash('sha256', 'event-a')]));
        $this->remote->expects($this->never())->method('write');
        $this->queue->process();
        $this->assertSame([], $this->queue->getJobs());
    }

    public function testDisabledStorageKeepsQueueIntact(): void
    {
        $this->enqueue('a.jpg');
        $this->config['ftp']['enabled'] = false;
        $this->remote->expects($this->never())->method('write');
        $this->assertSame(1, $this->queue->process()['remaining']);
    }

    public function testDestinationChangeDuringUploadDiscardsNewJobForDifferentTarget(): void
    {
        $this->enqueue('a.jpg');
        $paths = [];
        $this->remote->expects($this->exactly(4))->method('write')->willReturnCallback(function (string $path) use (&$paths): void {
            $paths[] = $path;
            if (count($paths) === 1) {
                $this->enqueue('old.jpg');
                $this->config['ftp']['title'] = 'event-b';
                $this->enqueue('new.jpg');
            }
        });
        $this->queue->process();
        $this->assertNotContains('event-b/images/old.jpg', $paths);
        $this->assertContains('event-b/images/new.jpg', $paths);
        $this->assertSame('synthetic-image', file_get_contents($this->root . '/data/images/old.jpg'));
        $this->assertSame([], $this->queue->getJobs());
    }

    public function testQueueLockPreventsConcurrentProcessing(): void
    {
        $this->enqueue('a.jpg');
        $lock = RemoteStorageQueueService::acquireWorkerLock($this->queue->getQueuePath());
        $this->assertIsResource($lock);
        try {
            $this->remote->expects($this->never())->method('write');
            $this->assertTrue($this->queue->process()['locked']);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function testManualCommandReportsPendingWebpageAndFailure(): void
    {
        $this->remote->expects($this->never())->method('write');
        $queue = $this->createStub(RemoteStorageQueueService::class);
        $queue->method('getJobs')->willReturn([['file' => '[webpage]', 'created' => time()]]);
        $queue->method('process')->willReturn(['uploaded' => 0, 'failed' => 1, 'remaining' => 1, 'locked' => false]);
        $previous = $GLOBALS[RemoteStorageQueueService::class] ?? null;
        $GLOBALS[RemoteStorageQueueService::class] = $queue;
        try {
            $command = new \Symfony\Component\Console\Tester\CommandTester(new \Photobooth\Command\RemoteStorageProcessCommand());
            $this->assertSame(0, $command->execute(['--status' => true]));
            $this->assertStringContainsString('[webpage]', $command->getDisplay());
            $this->assertStringNotContainsString('Attempts', $command->getDisplay());
            $this->assertStringNotContainsString('Upload queue is empty', $command->getDisplay());
            $this->assertSame(1, $command->execute([]));
            $this->assertStringContainsString('failed: 1, remaining: 1', $command->getDisplay());
        } finally {
            if ($previous === null) {
                unset($GLOBALS[RemoteStorageQueueService::class]);
            } else {
                $GLOBALS[RemoteStorageQueueService::class] = $previous;
            }
        }
    }

    public function testBackgroundUploadRemainsDisabledByDefault(): void
    {
        $config = (new \Symfony\Component\Config\Definition\Processor())->processConfiguration(new \Photobooth\Configuration\PhotoboothConfiguration(), [[]]);
        $this->remote->expects($this->never())->method('write');
        $this->assertFalse($config['ftp']['upload_in_background']);
    }

    public function testPendingWebpageIsDiscardedAtDestinationChange(): void
    {
        $this->config['ftp']['create_webpage'] = true;
        $this->enqueue('last.jpg');
        $this->remote->expects($this->exactly(2))->method('write');
        $this->remote->expects($this->once())->method('createWebpage')->willThrowException(new \RuntimeException('offline'));
        $this->assertSame(1, $this->queue->process()['remaining']);
        $this->config['ftp']['title'] = 'event-b';
        $this->queue->process();
        $this->assertSame([], $this->queue->getJobs());
        $this->assertFileExists($this->root . '/data/images/last.jpg');
    }

    public function testLocalReadFailureDoesNotProbeOrBlockRemoteUploads(): void
    {
        $this->stopAfterFailure = true;
        $this->enqueue('a.jpg');
        $this->enqueue('b.jpg');
        $removed = false;
        $this->remote->method('getStorageFolder')->willReturnCallback(function () use (&$removed): string {
            if (!$removed) {
                unlink($this->root . '/data/images/a.jpg');
                $removed = true;
            }
            return 'event-a';
        });
        $this->remote->expects($this->exactly(2))->method('write');
        $result = $this->queue->process();
        $this->assertSame(1, $result['failed']);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(1, $result['remaining']);
    }

}
