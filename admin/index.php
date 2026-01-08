<?php

require_once __DIR__ . '/admin_boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Utility\PathUtility;

$configsetup = require PathUtility::getAbsolutePath('lib/configsetup.inc.php');

$appName = ApplicationService::getInstance()->getTitle();
$appVersion = ApplicationService::getInstance()->getVersion();
$page = 'Adminpanel';

$pageTitle = $page . ' - ' . $appName . ' (' . $appVersion . ')';
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

?>

    <div class="w-full h-full flex flex-col bg-brand-1 overflow-hidden fixed top-0 left-0">
        <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col overflow-hidden">

            <!-- body -->
            <div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <?php
                    $sidebarHeadline = $page . ' - ' . $appName;
include PathUtility::getAbsolutePath('admin/components/sidebar.php');
?>
                <div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">
                    <?php include PathUtility::getAbsolutePath('admin/components/content.php'); ?>
                </div>
            </div>

        </div>
    </div>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
