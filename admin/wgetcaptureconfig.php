<?php

/** @var array $config */
require_once __DIR__ . '/admin_boot.php';

use Photobooth\Environment;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\ProcessService;
use Photobooth\Utility\PathUtility;

$loggerService = LoggerService::getInstance();
$logger = $loggerService->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

$configurationService = ConfigurationService::getInstance();
$logger->debug('Saving Photobooth configuration for go2rtc...');

$config['commands']['preview'] = '';
$config['commands']['take_picture'] = 'wget -O %s http://' . Environment::getIp() . ':1984/api/frame.jpeg?src=photobooth';
$config['commands']['take_collage'] = 'wget -O %s http://' . Environment::getIp() . ':1984/api/frame.jpeg?src=photobooth';

$config['picture']['cheese_time'] = '0';

$config['preview']['mode'] = 'url';
$config['preview']['url'] = 'http://' . Environment::getIp() . ':1984/api/stream.mjpeg?src=photobooth';
$config['preview']['camTakesPic'] = false;

try {
    $configurationService->update($config);
    $message = 'New config saved.';
    $status = 'success';
} catch (\Exception $exception) {
    $message = $exception->getMessage();
    $status = 'error';
}

// Kill service daemons after config has changed
ProcessService::getInstance()->shutdown();
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="2;url=<?= PathUtility::getPublicPath('admin') ?>">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="<?= $status ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <p>Redirecting…</p>
</body>
</html>
<?php
exit;
