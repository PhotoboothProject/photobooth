<?php

require_once '../lib/boot.php';

use Photobooth\HealthCheck;
use Photobooth\Utility\PathUtility;

$pageTitle = $config['ui']['branding'] . ' Health Check';
$remoteBuzzer = false;
$photoswipe = false;

include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

?>

<div class="w-full h-full grid place-items-center fixed bg-brand-2 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col px-6 py-12">
        <div class="w-full max-w-4xl h-144 rounded-lg bg-white flex flex-col shadow-xl">
            <div class="p-4 md:p-8">
                <?=(new HealthCheck())->renderReport()?>
                <div class="w-full max-w-md p-5 mx-auto mt-2">
                    <?=getMenuBtn(PathUtility::getPublicPath('test'), 'back', 'fa fa-chevron-left')?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    include PathUtility::getAbsolutePath('template/components/main.footer.php');
?>
</body>
</html>
