<?php
session_start();
require_once '../lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && isset($_SERVER['SERVER_ADDR']) &&  $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    require_once '../lib/configsetup.inc.php';
} else {
    header('location: ../login');
    exit();
}

$btnShape = 'shape--' . $config['ui']['button'];
$uiShape = 'shape--' . $config['ui']['style'];
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
        <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
        <meta name="theme-color" content="<?=$config['colors']['primary']?>">

        <!-- Favicon + Android/iPhone Icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
        <link rel="manifest" href="../resources/img/site.webmanifest">
        <link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

        <link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/material-icons/iconfont/material-icons.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/material-icons/css/material-icons.css">
        <link rel="stylesheet" type="text/css" href="../node_modules/selectize/dist/css/selectize.css">

        <!-- tw admin -->
        <link rel="stylesheet" href="../resources/css/tailwind.admin.css"/>
</head> 
<body>

    <?php 
        include("helper/index.php");
        include("inputs/index.php");
    ?>

    <div class="w-full h-full flex flex-col bg-brand-1">
        <div class="max-w-[2000px] mx-auto w-full h-full flex flex-col">
            
            <!-- body -->
			<div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <?php 
                    $sidebarHeadline = "Adminpanel";
                    include("components/sidebar.php"); 
                ?>
				<div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">
                    <?php include("components/content.php"); ?>
                </div>
            </div>

        </div>
    </div>

    <div class="pageLoader w-full h-full fixed top-0 left-0 z-50 hidden place-items-center [&.isActive]:grid">
        <div class="w-full h-full left-0 top-0 z-10 absolute bg-black bg-opacity-60"></div>
        <div class="px-4 py-6 rounded-md bg-white shadow-md flex flex-col items-center justify-center relative z-20 text-center">
            <?php echo getLoader("sm"); ?>
            <label class="text-xs text-brand-1 mt-4 font-bold"></label>
        </div>
    </div>

    <?php echo getToast(); ?>

    <script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
    <script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
    <script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="../node_modules/waypoints/lib/jquery.waypoints.min.js"></script>
    <script type="text/javascript" src="../node_modules/selectize/dist/js/standalone/selectize.min.js"></script>
    <script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>

    <script type="text/javascript" src="../resources/js/main.admin.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
 </body>
</html>
