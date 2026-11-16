<?php

declare(strict_types=1);

namespace Photobooth\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/** Real processes and the production entrypoint, without application configuration or remote servers. */
final class RemoteStorageWorkerTest extends TestCase
{
    private string $root;
    private string $fixtures;
    private array $children = [];

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/photobooth worker ' . bin2hex(random_bytes(8));
        $this->fixtures = dirname(__DIR__, 2) . '/fixtures/remote-storage';
        foreach (['bin', 'vendor', 'lib', 'data/images', 'data/remote-queue', 'var/log'] as $folder) {
            mkdir($this->root . '/' . $folder, 0700, true);
        }
        $project = dirname(__DIR__, 3);
        copy($project . '/bin/remote-storage-worker', $this->root . '/bin/remote-storage-worker');
        copy($this->fixtures . '/boot.php', $this->root . '/lib/boot.php');
        file_put_contents($this->root . '/vendor/autoload.php', '<?php require ' . var_export($project . '/vendor/autoload.php', true)
            . '; require ' . var_export($this->fixtures . '/environment.php', true)
            . '; workerEnvironment(' . var_export($this->root, true) . ');');
    }

    protected function tearDown(): void
    {
        foreach ($this->children as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testContendersAndRepeatedTriggersDoNotBootstrapWhileUploading(): void
    {
        $this->enqueue('a.jpg');
        $worker = $this->start();
        $this->expectEvent($worker, 'upload:a.jpg');
        $this->enqueue('a.jpg');
        $this->assertCount(1, glob($this->root . '/data/remote-queue/*.json') ?: []);
        for ($i = 0; $i < 3; $i++) {
            $contender = $this->start();
            $this->expectExit($contender);
            $trigger = $this->start('start');
            $this->expectEvent($trigger, 'ready');
            $this->expectEvent($trigger, 'started');
            $this->expectExit($trigger);
        }
        $this->assertSame("boot\n", file_get_contents($this->root . '/boot-count'));
        $this->enqueue('b.jpg');
        $this->release($worker);
        $this->expectEvent($worker, 'upload:b.jpg');
        $this->release($worker);
        $this->expectExit($worker);
        $this->assertSame([], glob($this->root . '/data/remote-queue/*.json'));
    }

    public function testJobArrivingAfterEmptySnapshotIsCaughtByFinalGatedScan(): void
    {
        $worker = $this->start(null, true);
        $this->expectEvent($worker, 'empty');
        $this->enqueue('late.jpg');
        $this->release($worker);
        $this->expectEvent($worker, 'upload:late.jpg');
        $this->release($worker);
        $this->expectExit($worker);
        $this->assertSame([], glob($this->root . '/data/remote-queue/*.json'));
    }

    public function testProducerWaitsForDispatchThenNextWorkerProcessesJob(): void
    {
        $worker = $this->start(null, 2);
        $this->expectEvent($worker, 'empty');
        $producer = $this->start('enqueue', false, 'handoff.jpg');
        $this->expectEvent($producer, 'ready');
        $this->assertTrue(proc_get_status($this->children[$producer][0])['running']);
        $this->assertSame([], glob($this->root . '/data/remote-queue/*.json'));
        $this->release($worker);
        $this->expectExit($worker);
        $this->expectEvent($producer, 'enqueued');
        $this->expectExit($producer);
        file_put_contents($this->root . '/data/images/handoff.jpg', 'synthetic-image');
        $trigger = $this->start('start');
        $this->expectEvent($trigger, 'ready');
        $this->expectEvent($trigger, 'started');
        $this->expectExit($trigger);
        $deadline = microtime(true) + 10;
        $lock = fopen($this->root . '/data/remote-queue/.lock', 'c');
        $this->assertIsResource($lock);
        do {
            clearstatcache();
            if (is_file($this->root . '/uploads') && glob($this->root . '/data/remote-queue/*.json') === [] && flock($lock, LOCK_EX | LOCK_NB)) {
                flock($lock, LOCK_UN);
                break;
            }
            usleep(1000);
        } while (microtime(true) < $deadline);
        fclose($lock);
        $this->assertSame("handoff.jpg\n", @file_get_contents($this->root . '/uploads'));
        $this->assertSame([], glob($this->root . '/data/remote-queue/*.json'));
    }

    public function testLaunchFailureKeepsJobAndReleasesDispatchForRetry(): void
    {
        $this->enqueue('retry.jpg');
        $workerPath = $this->root . '/bin/remote-storage-worker';
        rename($workerPath, $workerPath . '.disabled');
        $trigger = $this->start('fail-start');
        $this->expectEvent($trigger, 'ready');
        $this->expectEvent($trigger, 'launch-failed');
        $this->expectExit($trigger);
        $jobs = glob($this->root . '/data/remote-queue/*.json');
        $this->assertIsArray($jobs);
        $this->assertCount(1, $jobs);
        rename($workerPath . '.disabled', $workerPath);
        $worker = $this->start();
        $this->expectEvent($worker, 'upload:retry.jpg');
        $this->release($worker);
        $this->expectExit($worker);
        $this->assertSame([], glob($this->root . '/data/remote-queue/*.json'));
    }

    public function testConcurrentProducersCreateOnlyOnePendingJob(): void
    {
        $gate = fopen($this->root . '/data/remote-queue/.dispatch', 'c');
        $this->assertIsResource($gate);
        $this->assertTrue(flock($gate, LOCK_EX));
        try {
            $first = $this->start('enqueue', false, 'same.jpg');
            $second = $this->start('enqueue', false, 'same.jpg');
            $this->expectEvent($first, 'ready');
            $this->expectEvent($second, 'ready');
        } finally {
            flock($gate, LOCK_UN);
            fclose($gate);
        }
        foreach ([$first, $second] as $producer) {
            $this->expectEvent($producer, 'enqueued');
            $this->expectExit($producer);
        }
        $this->assertCount(1, glob($this->root . '/data/remote-queue/*.json') ?: []);
    }

    private function enqueue(string $file): void
    {
        file_put_contents($this->root . '/data/images/' . $file, 'synthetic-image');
        $producer = $this->start('enqueue', false, $file);
        $this->expectEvent($producer, 'ready');
        $this->expectEvent($producer, 'enqueued');
        $this->expectExit($producer);
    }

    private function start(?string $action = null, bool|int $emptyBarrier = false, string $file = ''): int
    {
        $command = $action === null
            ? [PHP_BINARY, $this->root . '/bin/remote-storage-worker']
            : [PHP_BINARY, $this->fixtures . '/client.php', $this->root, $action, $file];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->root, ['WORKER_EMPTY_BARRIER' => (string) (int) $emptyBarrier, 'WORKER_RECORD_UPLOAD' => $action === 'start' ? '1' : '0']);
        $this->assertIsResource($process);
        $this->children[] = [$process, $pipes];
        return array_key_last($this->children);
    }

    private function expectEvent(int $child, string $expected): void
    {
        $read = [$this->children[$child][1][1]];
        $write = $except = [];
        $this->assertSame(1, stream_select($read, $write, $except, 10), 'Timed out waiting for ' . $expected);
        $this->assertSame($expected . "\n", fgets($read[0]), 'Unexpected worker event.');
    }

    private function release(int $child): void
    {
        fwrite($this->children[$child][1][0], "continue\n");
        fflush($this->children[$child][1][0]);
    }

    private function expectExit(int $child): void
    {
        $read = [$this->children[$child][1][1]];
        $write = $except = [];
        $this->assertSame(1, stream_select($read, $write, $except, 10), 'Worker did not exit.');
        $this->assertSame('', stream_get_contents($read[0]));
        $error = stream_get_contents($this->children[$child][1][2]);
        $this->assertIsString($error);
        foreach ($this->children[$child][1] as $pipe) {
            fclose($pipe);
        }
        $exit = proc_close($this->children[$child][0]);
        unset($this->children[$child]);
        $this->assertSame(0, $exit, $error);
    }
}
