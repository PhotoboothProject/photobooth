<?php

require_once __DIR__ . '/../admin_boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Helper;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = 'Diskusage - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

?>
<div class="w-full h-full grid place-items-center fixed bg-brand-1 overflow-x-hidden overflow-y-auto">
    <div class="w-full flex items-center justify-center flex-col px-6 py-12">

        <div class="w-full max-w-xl rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl">
            <div class="w-full flex items-center pb-3 mb-3 border-b border-solid border-gray-200">
                <a href="<?=PathUtility::getPublicPath('admin')?>" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-black border-opacity-20 pr-3">
                    <span class="fa fa-chevron-left text-brand-1 text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
                </a>
                <h2 class="text-brand-1 text-xl font-bold">
                    <?= $languageService->translate('disk_usage') ?>
                </h2>
            </div>
<?php

$folders = [
    FolderEnum::IMAGES->absolute(),
    FolderEnum::KEYING->absolute(),
    FolderEnum::PRINT->absolute(),
    FolderEnum::QR->absolute(),
    FolderEnum::THUMBS->absolute(),
    FolderEnum::TEMP->absolute(),
];

foreach ($folders as $key => $folder) {
    echo '<div class="pb-3 mb-3 border-b border-solid border-gray-200 flex flex-col">';
    echo '<h3 class="font-bold whitespace-pre-wrap break-all">' . $languageService->translate('path') . ' ' . $folder . '</h3>';
    try {
        $folderSize = Helper::getFolderSize($folder);
        $formattedSize = Helper::formatSize($folderSize);
        echo '<div><span class="flex text-sm mt-2">' . $languageService->translate('foldersize') . '</span></div><span class="text-brand-1">' . $formattedSize . '</span>';
    } catch (\Exception $e) {
        echo '<div><span class="flex text-sm mt-2">' . $languageService->translate('filecount') . '</span></div><span class="text-brand-1">' . $e->getMessage() . '</span>';
    }
    try {
        $fileCount = Helper::getFileCount($folder);
        echo '<div><span class="flex text-sm mt-2">' . $languageService->translate('filecount') . '</span></div><span class="text-brand-1">' . $fileCount . '</span>';
    } catch (\Exception $e) {
        echo '<div><span class="flex text-sm mt-2">' . $languageService->translate('filecount') . '</span></div><span class="text-brand-1">' . $e->getMessage() . '</span>';
    }
    echo '</div>';
}

?>
        </div>

    </div>
</div>

<?php

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
