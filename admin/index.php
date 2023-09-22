<?php

require_once '../lib/boot.php';

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

require_once PathUtility::getAbsolutePath('lib/configsetup.inc.php');

$pageTitle = 'Adminpanel';
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
include PathUtility::getAbsolutePath('admin/inputs/index.php');
?>

    <div class="w-full h-full flex flex-col bg-brand-1 overflow-hidden fixed top-0 left-0">
        <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col overflow-hidden">

            <!-- body -->
            <div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <?php
                    $sidebarHeadline = $pageTitle;
include PathUtility::getAbsolutePath('admin/components/sidebar.php');
?>
                <div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">
                    <?php include PathUtility::getAbsolutePath('admin/components/content.php'); ?>
                </div>
            </div>

        </div>
    </div>


<?php
    include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
