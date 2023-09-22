<?php

require_once '../../lib/boot.php';

use Photobooth\Helper;
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

$pageTitle = 'Diskusage';
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
include PathUtility::getAbsolutePath('admin/inputs/index.php');
?>
<div class="w-full h-full grid place-items-center fixed bg-brand-1 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col px-6 py-12">

        <div class="w-full max-w-xl h-144 rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl">
            <div class="w-full flex items-center pb-3 mb-3 border-b border-solid border-gray-200">
                <a href="<?=PathUtility::getPublicPath('admin')?>" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-black border-opacity-20 pr-3">
                    <span class="fa fa-chevron-left text-brand-1 text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
                </a>
                <h2 class="text-brand-1 text-xl font-bold">
                    <?= $config['ui']['branding'] ?>
                    <span data-i18n="disk_usage"></span>
                </h2>
            </div>
            <?php
            foreach ($config['foldersAbs'] as $key => $folder) {
                try {
                    $folderSize = Helper::getFolderSize($folder);
                    $formattedSize = Helper::formatSize($folderSize);
                    $fileCount = Helper::getFileCount($folder);

                    echo '<div class="pb-3 mb-3 border-b border-solid border-gray-200 flex flex-col">';
                    echo '<h3 class="font-bold whitespace-pre-wrap break-all"><span data-i18n="path"></span> ' . $folder . '</h3>';
                    echo '<div><span class="flex text-sm mt-2" data-i18n="foldersize"></span></div><span class="text-brand-1">' . $formattedSize . '</span>';
                    echo '<div><span class="flex text-sm mt-2" data-i18n="filecount"></span></div><span class="text-brand-1">' . $fileCount . '</span>';
                    echo '</div>';
                } catch (Exception $e) {
                    echo '<div class="pb-3 mb-3 border-b border-solid border-gray-200 flex flex-col">';
                    echo '<h3 class="font-bold whitespace-pre-wrap break-all"><span data-i18n="path"></span> ' . $folder . '</h3>';
                    echo '<div><span class="flex text-sm mt-2" data-i18n="foldersize"></span></div><span class="text-brand-1">' . $e->getMessage() . '</span>';
                    echo '<div><span class="flex text-sm mt-2" data-i18n="filecount"></span></div><span class="text-brand-1">' . $e->getMessage() . '</span>';
                    echo '</div>';
                }
            }
?>
        </div>

    </div>
</div>

<?php
    include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
