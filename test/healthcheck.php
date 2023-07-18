<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/healthcheck.php';

$healthCheck = new HealthCheck();


if ($healthCheck->phpMajor >= 8) {
    echo 'Status: ok. PHP machtes our requirements. ';
} else {
    echo 'Status: Error - Please update PHP to PHP8! PHP does not match our requirements! ';
}
echo 'Current PHP version: ' . $healthCheck->phpMajor . '.' . $healthCheck->phpMinor, '<br>';

echo $healthCheck->gdEnabled ? 'Status: ok. GD is enabled.' : 'Status: Error - GD must be enabled!', '<br>';
echo $healthCheck->zipEnabled ? 'Status: ok. ZIP is enabled.' : 'Status: Error - ZIP must be enabled!', '<br>';
echo '', '<br>';
echo 'H E A L T H   S T A T U S', '<br>';
echo $healthCheck->healthStatus ? 'No errors found. Enjoy your Photobooth!' : 'ERROR Please fix mentioned errors to enjoy your Photobooth!', '<br>';
