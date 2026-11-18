<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Service\RemoteStorageService;

header('Content-Type: application/json');

/**
 * Sanitize a comma separated list of filenames from request data.
 *
 * @return string[]
 */
function sanitizeFileList(string $list): array
{
    $files = [];
    foreach (explode(',', $list) as $file) {
        $file = basename(trim($file));
        if ($file !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            $files[] = $file;
        }
    }

    return array_values(array_unique($files));
}

$queue = RemoteStorageQueueService::getInstance();

// Status polling: cheap read-only request used by the frontend to update
// the QR upload indicator. No CSRF needed (same as gallery.php?status).
if (isset($_GET['status'])) {
    session_write_close();
    $files = sanitizeFileList((string) ($_GET['files'] ?? ''));
    echo json_encode([
        'enabled' => (bool) $config['ftp']['enabled'],
        'files' => $queue->getStatusForFiles($files),
        'counts' => $queue->getCounts(),
    ]);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

checkCsrfOrFail($_POST);

$logger = LoggerService::getInstance()->getLogger('remotestorage');

if (!$config['ftp']['enabled']) {
    echo json_encode(['ok' => true, 'skipped' => true]);
    exit();
}

// The drain may outlive the triggering request and run for a while on slow
// connections. Keep going if the client disconnects and, critically, release
// the PHP session lock so the booth UI is not frozen while uploading.
ignore_user_abort(true);
set_time_limit(0);
session_write_close();

// Files of the photo just captured jump the backlog so its QR indicator
// turns green as fast as possible.
$priority = sanitizeFileList((string) ($_POST['files'] ?? ''));

if (!$queue->acquireDrainLock()) {
    // Another drain is running; it re-checks the queue before exiting and
    // the frontend poller re-triggers if something is left pending.
    echo json_encode(['ok' => true, 'running' => true]);
    exit();
}

$remoteStorage = RemoteStorageService::getInstance();
$storageFolder = $remoteStorage->getStorageFolder();
$drained = 0;
$consecutiveFailures = 0;
$lingered = false;

while (true) {
    $entry = $queue->claimNext($priority);
    if ($entry === null) {
        if ($lingered) {
            break;
        }
        // Linger shortly and re-check: an enqueue racing with our exit
        // would otherwise have to wait for the next trigger.
        $lingered = true;
        usleep(1500000);
        continue;
    }
    $lingered = false;

    $file = $entry['file'];
    try {
        $imagePath = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $file;
        $thumbPath = FolderEnum::THUMBS->absolute() . DIRECTORY_SEPARATOR . $file;
        if (!is_file($imagePath)) {
            $queue->markFailed($file, 'Local file missing', true);
            $logger->error('Remote upload skipped, local file missing', ['file' => $file]);
            continue;
        }

        $remoteStorage->write($storageFolder . '/images/' . $file, (string) file_get_contents($imagePath));
        if (is_file($thumbPath)) {
            $remoteStorage->write($storageFolder . '/thumbs/' . $file, (string) file_get_contents($thumbPath));
        }

        if (!$queue->markDone($file)) {
            // The photo was deleted while its upload was in flight; undo the
            // remote copy if remote deletion is enabled.
            if ($config['ftp']['delete']) {
                $remoteStorage->delete($storageFolder . '/images/' . $file);
                $remoteStorage->delete($storageFolder . '/thumbs/' . $file);
            }
        } else {
            $drained++;
        }
        $consecutiveFailures = 0;

        if ($config['ftp']['create_webpage'] && !$queue->isWebpageCreated($storageFolder)) {
            try {
                $remoteStorage->createWebpage();
                $queue->markWebpageCreated($storageFolder);
            } catch (\Throwable $e) {
                $logger->error('createWebpage failed', ['error' => $e->getMessage()]);
            }
        }
    } catch (\Throwable $e) {
        $queue->markFailed($file, $e->getMessage());
        $logger->error('Remote upload failed', ['file' => $file, 'error' => $e->getMessage()]);
        // Circuit breaker: consecutive failures usually mean the server is
        // unreachable (timeouts can take ~90s each) - stop hammering it and
        // leave the backlog pending for the next trigger.
        if (++$consecutiveFailures >= 2) {
            break;
        }
    }
}

$queue->releaseDrainLock();

echo json_encode(['ok' => true, 'drained' => $drained]);
exit();
