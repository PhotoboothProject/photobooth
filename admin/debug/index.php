<?php

require_once '../../lib/boot.php';

use Photobooth\Environment;
use Photobooth\Service\AssetService;
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

require_once PathUtility::getAbsolutePath('lib/configsetup.inc.php');

$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();
$pageTitle = 'Debugpanel';
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
include PathUtility::getAbsolutePath('admin/components/navItem.debug.php');

?>
    <div class="w-full h-full flex flex-col bg-brand-1 overflow-hidden fixed top-0 left-0">
        <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col">


            <!-- body -->
            <div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <div class="w-full flex md:hidden px-5 pb-5 items-center">
                    <div class="w-full flex flex-col">
                        <span class="text-2xl text-white">Debugpanel</span>
                    </div>
                    <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center" onclick="toggleAdminNavi()">
                        <span class="text-white text-3xl fa fa-bars"></span>
                    </div>
                </div>

                <div class="adminNavi hidden md:!hidden w-full h-full z-40 fixed top-0 left-0 bg-black bg-opacity-70 cursor-pointer [&.isActive]:flex" onclick="toggleAdminNavi();"></div>
                <div class="adminNavi hidden [&.isActive]:flex z-50 bg-brand-1 h-full pb-10 overflow-hidden w-3/4 fixed top-0 right-0 md:w-64 md:flex md:static md:bg-transparent">
                    <div class="w-full h-full pl-5 flex flex-col overflow-hidden">
                        <div class="flex items-center shrink-0 border-b border-solid border-white border-opacity-20 py-4 mr-4">
                            <a href="<?=PathUtility::getPublicPath('admin')?>" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-white border-opacity-20 px-3">
                                <span class="fa fa-chevron-left text-white text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
                            </a>
                            <h1 class="text-white font-bold">Debugpanel</h1>
                            <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center md:hidden" onclick="toggleAdminNavi()">
                                <span class="text-white !text-2xl fa fa-close"></span>
                            </div>
                        </div>
                        <div class="w-full h-full flex flex-col overflow-hidden">
                            <ul class="w-full h-full flex flex-col overflow-x-hidden overflow-y-auto">
                                <li class="flex w-full h-6 shrink-0"></li>
<?php
echo getNavItemDebug('myconfig');
echo getNavItemDebug('remotebuzzerlog');
echo getNavItemDebug('synctodrivelog');
echo getNavItemDebug('devlog');
if (Environment::isLinux()) {
    echo getNavItemDebug('serverprocesses');
}
echo getNavItemDebug('bootconfig');
echo getNavItemDebug('printdb');
echo getNavItemDebug('installlog');
echo getNavItemDebug('githead');
?>
                            </ul>
                    </div>
                </div>
                </div>
                <div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">

                    <div class="w-full h-full flex flex-col" autocomplete="off">
                        <div class="adminContent w-full flex flex-1 flex-col py-5 overflow-x-hidden overflow-y-auto">
                            <div class="debugcontent py-2 px-5"></div>
                        </div>
                        <div class="w-full flex px-5 py-3 border-t border-solid border-gray-300">

                            <div class="flex flex-center ml-auto shrink-0">
                                <div class="mr-2">
                                    <label class="settinglabel"><?=$languageService->translate('debugpanel:autorefresh')?></label>
                                </div>
                                <label id="debugpanel_autorefresh" class="adminCheckbox relative flex items-center cursor-pointer">
                                    <input class="hidden peer" type="checkbox" id="debugpanel_autorefresh" value="true"/>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="hidden ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        <label class="adminCheckbox-true hidden"><?=$languageService->translate('adminpanel_toggletextON')?></label>
                                        <label class="adminCheckbox-false"><?=$languageService->translate('adminpanel_toggletextOFF')?></label>
                                    </span>
                                </label>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="<?=$assetService->getUrl('resources/js/tools.js')?>"></script>
<script src="<?=$assetService->getUrl('resources/js/debugpanel.js')?>"></script>

<?php
    include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
