<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_manual'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['manual']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

require_once PathUtility::getAbsolutePath('lib/configsetup.inc.php');

$languageService = LanguageService::getInstance();
$pageTitle = 'Manual';
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');
include PathUtility::getAbsolutePath('admin/inputs/index.php');
?>
    <div class="w-full h-full flex flex-col bg-brand-1 overflow-hidden fixed top-0 left-0">
       <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col">


            <!-- body -->
            <div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
<?php
$sidebarHeadline = $pageTitle;
include PathUtility::getAbsolutePath('admin/components/sidebar.php');
?>
                <div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">

                    <div class="w-full h-full flex flex-col" autocomplete="off">
                        <div class="adminContent w-full flex flex-1 flex-col py-5 overflow-x-hidden overflow-y-auto">
                            <form>
<?php
$i = 0;
foreach($configsetup as $panel => $fields) {
    $panelHidden = 'visible';
    if (empty($fields['view'])) {
        $fields['view'] = 'basic';
    }
    switch ($fields['view']) {
        case 'experimental':
            if (!$config['adminpanel']['experimental_settings']) {
                $panelHidden = 'hidden';
            }
            // no break
        case 'expert':
            if ($config['adminpanel']['view'] == 'advanced') {
                $panelHidden = 'hidden';
            }
            if ($config['adminpanel']['view'] == 'basic') {
                $panelHidden = 'hidden';
            }
            // no break
        case 'advanced':
            if ($config['adminpanel']['view'] == 'basic') {
                $panelHidden = 'hidden';
            }
            // no break
        case 'basic':
            break;
    }

    // headline
    echo '<div id="' . $panel . '" class="adminSection ' . $panelHidden . '">';
    echo '<h2 class="text-brand-1 text-xl font-bold pt-4 px-4 lg:pt-8 lg:px-8 mb-4">' . $languageService->translate($panel) . '</h2>';
    echo '<div class="flex flex-col px-4 lg:px-8 py-2">';
    echo '<div class="flex flex-col rounded-xl p-3 shadow-xl bg-white">';

    foreach($fields as $key => $field) {
        if ($key == 'platform' || $key == 'view') {
            continue;
        }

        if (!isset($field['view'])) {
            $field['view'] = 'basic';
        }

        switch ($field['view']) {
            case 'expert':
                if ($config['adminpanel']['view'] == 'advanced') {
                    $field['type'] = 'hidden';
                }
                // no break
            case 'advanced':
                if ($config['adminpanel']['view'] == 'basic') {
                    $field['type'] = 'hidden';
                }
                // no break
            case 'basic':
                break;
        }

        switch($field['type']) {
            case 'checkbox':
                echo '<div class="w-full max-w-3xl pb-3 mb-3 border-b border-solid border-gray-200">';
                echo '<h3 class="text-brand-1 text-md font-bold mb-1">' . $languageService->translate($panel . ':' . $key) . '</span></h3>';
                echo '<p class="leading-8">' . $languageService->translate('manual:' . $panel . ':' . $key) . '</p>';
                echo '</div>';
                break;
            case 'multi-select':
            case 'range':
            case 'select':
            case 'input':
                echo '<div class="w-full max-w-3xl pb-3 mb-3 border-b border-solid border-gray-200">';
                echo '<h3 class="text-brand-1 text-md font-bold mb-1">' . $languageService->translate($panel . ':' . $key) . '</h3>';
                echo '<p class="leading-8">' . $languageService->translate('manual:' . $panel . ':' . $key) . '</p>';
                echo '</div>';
                break;
            case 'color':
            case 'hidden':
                if(is_string($field['value'])) {
                    echo '<input type="hidden" name="' . $field['name'] . '" value="' . $field['value'] . '"/>';
                }
                break;
        }
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
    $i++;
}
?>
                                <div class="py-4 px-4 lg:px-8">
                                    <a href="<?=PathUtility::getPublicPath('faq')?>" class="flex items-center hover:underline hover:text-brand-1 mb-2" title="FAQ" target="newwin">
                                        <?=$languageService->translate('show_faq')?>
                                        <i class="ml-2 <?php echo $config['icons']['faq']; ?>"></i>
                                    </a>
                                    <a href="https://photoboothproject.github.io" target="_blank" class="flex items-center hover:underline hover:text-brand-1">
                                        <?=$languageService->translate('show_wiki')?>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

<?php
    include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
