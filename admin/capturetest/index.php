<?php

require_once '../../lib/boot.php';

use Photobooth\PhotoboothCaptureTest;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

$languageService = LanguageService::getInstance();
$pageTitle = 'Capture test - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

?>
<div class="w-full h-full grid place-items-center fixed bg-brand-1 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col px-6 py-12">

        <div class="w-full max-w-xl h-144 rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl">
            <div class="w-full flex items-center pb-3 mb-3 border-b border-solid border-gray-200">
                <a href="<?=PathUtility::getPublicPath('admin')?>" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-black border-opacity-20 pr-3">
                    <span class="fa fa-chevron-left text-brand-1 text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
                </a>
                <h2 class="text-brand-1 text-xl font-bold">
                    <?= $languageService->translate('test_capture') ?>
                </h2>
            </div>
<?php

$test = new PhotoboothCaptureTest();

foreach ($test->captureCmds as $index => $command) {
    // Set filename for each test command
    $test->fileName = sprintf('test-%d.jpg', $index + 1);
    $test->tmpFile = $test->tmpFolder . DIRECTORY_SEPARATOR . $test->fileName;

    $test->addLog('debug', 'Executing Command #' . ($index + 1), ['command' => $command]);

    // Execute the command
    $test->executeCmd($command);

    foreach ($test->logData as $log) {
        $level = htmlspecialchars($log['level']);
        $message = htmlspecialchars($log['message']);
        $context = htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT));

        echo '<div class="log-entry">
            <div class="log-level ' . $level . '">' . strtoupper($level) . '</div>
            <div class="log-message">' . $message . '</div>
            <div class="log-context"><pre>' . $context . '</pre></div>
          </div>';
    }
    $test->logData = [];
}

?>
        </div>

    </div>
</div>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
