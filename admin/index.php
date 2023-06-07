<?php
session_start();
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    require_once $fileRoot . 'lib/configsetup.inc.php';
} else {
    header('location: ' . $fileRoot . 'login');
    exit(); 
}

$pageTitle = 'Adminpanel';
include('components/head.admin.php');
include('helper/index.php');
include('inputs/index.php'); 
?>

    <div class="w-full h-full flex flex-col bg-brand-1 overflow-hidden fixed top-0 left-0">
        <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col overflow-hidden">
            
            <!-- body -->
			<div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <?php 
                    $sidebarHeadline = $pageTitle;
                    include('components/sidebar.php'); 
                ?>
				<div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">
                    <?php include('components/content.php'); ?>
                </div>
            </div>

        </div>
    </div>


<?php
    include('components/footer.admin.php');
?>
