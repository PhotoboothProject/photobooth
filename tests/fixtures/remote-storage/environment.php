<?php

declare(strict_types=1);

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\PathUtility;

function workerBarrier(string $event): void
{
    echo $event . "\n";
    flush();
    if (fgets(STDIN) === false) {
        throw new RuntimeException('Worker test barrier lost its controller.');
    }
}

function workerEnvironment(string $root): void
{
    PathUtility::$rootPathCache = $root . '/';
    $GLOBALS[ConfigurationService::class] = new class () extends ConfigurationService {
        public function __construct()
        {
        }
        public function load(): void
        {
        }
        public function getConfiguration(): array
        {
            return ['ftp' => ['enabled' => true, 'create_webpage' => false]];
        }
    };
    $GLOBALS[RemoteStorageService::class] = new class () extends RemoteStorageService {
        public function __construct()
        {
        }
        public function refreshConfiguration(): void
        {
        }
        public function getDestinationIdentity(): string
        {
            return 'test-destination';
        }
        public function getStorageFolder(): string
        {
            return 'event';
        }
        public function write(string $location, string $contents): void
        {
            if (getenv('WORKER_RECORD_UPLOAD') === '1') {
                file_put_contents(PathUtility::getRootPath() . 'uploads', basename($location) . "\n", FILE_APPEND | LOCK_EX);
                return;
            }
            workerBarrier('upload:' . basename($location));
        }
    };
    $GLOBALS[RemoteStorageQueueService::class] = new class () extends RemoteStorageQueueService {
        private bool $paused = false;
        private int $emptyScans = 0;
        public function getJobs(): array
        {
            $jobs = parent::getJobs();
            if ($jobs === []) {
                $this->emptyScans++;
            }
            if ((int) getenv('WORKER_EMPTY_BARRIER') === $this->emptyScans && $this->emptyScans > 0 && $jobs === [] && !$this->paused) {
                $this->paused = true;
                workerBarrier('empty');
            }
            return $jobs;
        }
    };
}
