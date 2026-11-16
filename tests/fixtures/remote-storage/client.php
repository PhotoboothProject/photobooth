<?php

declare(strict_types=1);

require $argv[1] . '/vendor/autoload.php';

$queue = \Photobooth\Service\RemoteStorageQueueService::getInstance();
echo "ready\n";
flush();
switch ($argv[2]) {
    case 'enqueue':
        $queue->enqueue($argv[3]);
        echo "enqueued\n";
        break;
    case 'start':
        $queue->startWorker();
        echo "started\n";
        break;
    case 'fail-start':
        try {
            $queue->startWorker();
            throw new RuntimeException('Expected missing worker to reject launch.');
        } catch (RuntimeException $error) {
            if ($error->getMessage() !== 'Remote upload worker entry point is missing or unreadable.') {
                throw $error;
            }
            echo "launch-failed\n";
        }
        break;
}
