<?php
require_once '../../lib/boot.php';

use Photobooth\Service\ApplicationService;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;
use Photobooth\Utility\FontUtility;
use Photobooth\Service\AssetService;
use Photobooth\Utility\CollageLayoutScanner;

// Login / Authentication check
if (!(
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['admin']
)) {
    header('location: ' . PathUtility::getPublicPath('login'));
    exit();
}

// =============================================================
// Designer-specific initializing
// Here we load the relevant services and helperclasses
// =============================================================
$configurationService = ConfigurationService::getInstance();
$languageService = LanguageService::getInstance();
$assetService = AssetService::getInstance();

// Example: Load available fonts
$font_paths = [
    PathUtility::getAbsolutePath('resources/fonts'),
    PathUtility::getAbsolutePath('private/fonts')
];
$font_styles = '<style>';
$font_family_options = [];
foreach ($font_paths as $path) {
    try {
        $files = FontUtility::getFontsFromPath($path, false);
        $files = array_map(fn ($file): string => PathUtility::getPublicPath($file), $files);
        if (count($files) > 0) {
            foreach ($files as $name => $path) {
                $font_styles .= '@font-face { font-family: "' . $name . '"; src: url(' . $path . ') format("truetype"); }';
                $font_family_options[$path] = $name;
            }
        }
    } catch (\Exception $e) { // Handle error or log
    }
}
$font_styles .= '</style>';

// Initial loading of a collage layout or empty design
$currentLayout = $config['collage']['layout'];
//TEST:
$currentLayout = 'private/collage/landscape/1+2-1';
//
$currentLayoutData = null;
if ($currentLayout) {
    $currentLayoutData = CollageLayoutScanner::getLayoutData($currentLayout);
} else {
    // Fallback: Lade ein Standard-Layout oder ein leeres Layout, falls keines konfiguriert ist
    $currentLayoutData = CollageLayoutScanner::getLayoutData('template/collage/landscape/1+3-1');
}
// convert JSON to JavaScript-Variable
echo '<script type="text/javascript">';
echo 'const initialCollageLayout = ' . json_encode($currentLayoutData) . ';';
echo '</script>';

// Base URL for the designer (for AJAX calls etc.)
$designerUrl = PathUtility::getPublicPath('admin/collage-designer');
$designerUrl = rtrim($designerUrl, '/') . '/'; // make sure, the path ends with a slash

// =============================================================
// Standard Admin Panel Head & Body
// =============================================================
$pageTitle = 'Collage Designer - ' . ApplicationService::getInstance()->getTitle();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php'); // Contains e.g. getMenuBtn

?>

<div class="w-full h-screen bg-brand-2 px-3 md:px-6 py-6 md:py-12 overflow-x-hidden overflow-y-auto">
    <?= $font_styles ?>
     <!-- Modal styles and other general designer styles go here -->
    <style>
        /* Your modal and other general designer styles */
    </style>
    <style id="fontselectedStyle"></style>

    <div class="w-full flex items-center justify-center flex-col">
        <div class="w-full max-w-[1500px] rounded-lg p-4 md:p-8 bg-white flex flex-col shadow-xl place-items-center relative">

            <div class="w-full flex items-center justify-center relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 ml-4 md:ml-8">
                    <?= getBackBtn(false, false); ?>
                </div>
                <div class="text-center text-2xl font-bold text-brand-1 mb-2">
                    <?= $languageService->translate('collage_designer_title') ?>
                </div>
            </div>

            <!-- Main designer area -->
            <div class="main_editor_area mt-4 w-full flex flex-col gap-2">
                 <div class="design-selector-container w-full p-2 md:p-4 border border-gray-200 rounded-md flex flex-col gap-4">
<?php
include 'components/design-selector.php';
?>
                </div> <!-- End design-selector-container -->

                 <!-- Main design area with two columns for settings and preview -->
                <div class="main-design-panel w-full flex flex-col md:flex-row gap-2">
                    <!-- LEFT PANEL: Element-specific settings and managers -->
                    <div class="left-panel w-full flex-1 flex flex-col gap-2 border border-gray-200 rounded-md">
                        <div class="flex flex-col w-full px-4 pt-4">
                            <span class="w-full flex flex-col items-center text-xl font-bold text-brand-1">
                                <?= $languageService->translate('element_settings_panel') ?>
                            </span>
                            <div class="border-t border-gray-200 w-full my-6"></div>
                        </div>
<?php
// Include components relevant to element-specific adjustments
include 'components/element-settings-panel.php';    // Dynamic settings for active element
include 'components/img-settings-panel.php';        // Image settings management
include 'components/txt-settings-panel.php';       // Text fields management
?>
                    </div><!-- End left-panel -->

                    <!-- RIGHT PANEL -->
                      <!-- Container for Tools and Preview -->
                    <div class="right-column-container w-full flex-1 lg:flex-[2_1_0%] flex flex-col gap-2">

                        <!-- Tool Buttons -->
                        <div class="w-full py-2 md:py-3 border border-gray-200 rounded-md flex justify-center">
                            <?php include 'components/general-tools-panel.php'; // General tools like alignment?>
                        </div>

                        <!-- RIGHT PANEL: PREVIEW -->
                        <div class="right-panel w-full flex flex-col gap-2 rounded-md">
                            <span class="w-full flex flex-col items-center text-xl font-bold text-brand-1 mb-2">
                                <?= $languageService->translate('preview_title') ?>
                            </span>
                            <?php include 'components/preview-canvas.php'; // Contains #result_canvas?>
                        </div><!-- End right-panel -->

                    </div><!-- End right-column-container -->

                </div> <!-- End main-design-panel -->

                <!-- BOTTOM PANEL: General and placeholder settings (if not element-specific) -->
                <div class="bottom-panel w-full flex flex-col gap-4 p-2 md:p-4 border border-gray-200 rounded-md">
                    <span class="w-full flex flex-col text-xl font-bold text-brand-1 mb-2">
                        <?= $languageService->translate('general_placeholder_settings_title') ?>
                    </span>
<?php
include 'components/general-settings.php';     // General settings
include 'components/placeholder-settings.php'; // Placeholder settings
?>
                </div> <!-- End bottom-panel -->

            </div> <!-- End main_editor_area -->

            <form id="configuration_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="hidden" name="new-configuration" value="" />
                <input type="hidden" name="current-design-name" value="" />
            </form>
        </div>
        <div class="my-4"></div>
        <div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
<?php
echo getMenuBtn(PathUtility::getPublicPath('admin'), 'admin_panel', $config['icons']['admin']);
echo getMenuBtn(PathUtility::getPublicPath('test/collage.php'), 'collageTest', $config['icons']['take_collage'], true);
if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    echo getMenuBtn(PathUtility::getPublicPath('login/logout.php'), 'logout', $config['icons']['logout']);
}
?>
            </div>
        </div>
    </div>
</div>

<script>
    window.AppBaseUrl = <?php echo json_encode($designerUrl); ?>; 
</script>

<?php
include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
echo '<script src="' . $assetService->getUrl('admin/collage-designer/assets/js/collage-designer.js') . '"></script>'; // Your main JS
echo '<script src="' . $assetService->getUrl('admin/collage-designer/assets/js/collage-designer-tools.js') . '"></script>'; // Tools JS
echo '<script src="' . $assetService->getUrl('admin/collage-designer/assets/js/collage-designer-elemntSetPnl.js') . '"></script>'; // Element Settings Panel JS
echo '<script src="' . $assetService->getUrl('admin/collage-designer/assets/js/collage-designer-imgSetPnl.js') . '"></script>'; // Image Settings Panel JS
// Optional: Specific toasts/messages depending on PHP processing
if (isset($_SESSION['designer_message'])) {
    echo '<script>setTimeout(function(){openToast("' . $_SESSION['designer_message']['text'] . '", "' . $_SESSION['designer_message']['type'] . '", 5000)},500);</script>';
    unset($_SESSION['designer_message']);
}
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
?>
