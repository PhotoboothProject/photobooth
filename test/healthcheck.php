<?php

require_once '../lib/boot.php';

use Photobooth\HealthCheck;
use Photobooth\Utility\PathUtility;

$pageTitle = $config['ui']['branding'] . ' Health Check';
$remoteBuzzer = false;
$photoswipe = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
include PathUtility::getAbsolutePath('admin/inputs/index.php');

$healthCheck = new HealthCheck();
$healthData = '<h3 class="font-bold uppercase underline pb-2"><span data-i18n="healthStatus"></span></h3>';

$healthData .= '<p class="pb-2"><span data-i18n="currentPhpVersion"></span> ' . $healthCheck->phpMajor . '.' . $healthCheck->phpMinor . '<br>';
if ($healthCheck->phpMajor >= 8) {
    $healthData .= '<i class="fa fa-check mr-2"></i><span data-i18n="phpVersionOk"></span><br>';
} else {
    $healthData .= $healthCheck->healthStatus ? '</p><p class="text-red-500">' : '</p><p>';
    $healthData .= '<i class="fa fa-times mr-2"></i><span data-i18n="phpVersionError"></span><br>';
    $healthData .= $healthCheck->healthStatus ? '<span data-i18n="phpVersionWarning"></span></b><br><span data-i18n="phpUpdateRequired"></span><br></p><p>' : '<b><span data-i18n="phpUpdateRequired"></span></b><br>';
}
$healthData .= $healthCheck->gdEnabled ? '<i class="fa fa-check mr-2"></i><span data-i18n="phpGdEnabled"></span><br>' : '<i class="fa fa-times mr-2"></i><span data-i18n="phpGdDisabled"></span><br>';
$healthData .= $healthCheck->zipEnabled ? '<i class="fa fa-check mr-2"></i><span data-i18n="phpZipEnabled"></span></p><br>' : '<i class="fa fa-times mr-2"></i><span data-i18n="phpZipDisabled"></span></p><br>';
$healthData .= $healthCheck->healthStatus ? '<p><b><span data-i18n="healthGood"></span></b></p>' : '<p><b><span data-i18n="healthError"></span></b></p>';

?>

<div class="w-full h-full grid place-items-center fixed bg-brand-2 overflow-x-hidden overflow-y-auto">
        <div class="w-full flex items-center justify-center flex-col px-6 py-12">
            <div class="w-full max-w-4xl h-144 rounded-lg bg-white flex flex-col shadow-xl">
                <div class="p-4 md:p-8">
                    <?php
                    $healthCheckBg = $healthCheck->healthStatus ? 'bg-green-500' : 'bg-red-500';
echo '<div class="w-full p-5 mx-auto mt-2 rounded-lg ' . $healthCheckBg . ' text-white text-center">';
echo $healthData;
echo '</div>';
echo '<div class="w-full max-w-md p-5 mx-auto mt-2">';
echo getMenuBtn(PathUtility::getPublicPath('test'), 'back', 'fa fa-chevron-left');
echo '</div>';
?>
                </div>
            </div>
        </div>
    </div>

<?php
    include PathUtility::getAbsolutePath('template/components/main.footer.php');
?>

</body>
</html>
