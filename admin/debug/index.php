<?php
session_start();
require_once '../../lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_admin'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    (isset($_SESSION['auth']) && $_SESSION['auth'] === true) ||
    !$config['protect']['admin']
) {
    require_once '../../lib/configsetup.inc.php';
} else {
    header('location: ../../login');
    exit();
}

$uiShape = 'shape--' . $config['ui']['style'];
$btnShape = 'shape--' . $config['ui']['button'];
$btnClass = 'adminnavlistelement ' . $btnShape . ' noborder';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
    <meta name="theme-color" content="<?=$config['colors']['primary']?>">

    <!-- do not cache in browser -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="../../resources/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../resources/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../resources/img/favicon-16x16.png">
    <link rel="manifest" href="../../resources/img/site.webmanifest">
    <link rel="mask-icon" href="../../resources/img/safari-pinned-tab.svg" color="#5bbad5">

    <link rel="stylesheet" type="text/css" href="../../node_modules/normalize.css/normalize.css">
    <link rel="stylesheet" type="text/css" href="../../node_modules/font-awesome/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="../../node_modules/material-icons/iconfont/material-icons.css">
    <link rel="stylesheet" type="text/css" href="../../node_modules/material-icons/css/material-icons.css">
    <link rel="stylesheet" type="text/css" href="../../node_modules/selectize/dist/css/selectize.css">
        
	<!-- tw admin -->
	<link rel="stylesheet" href="../../resources/css/tailwind.admin.css"/>
</head>
<body>
	<?php 
		include("../../admin/helper/index.php");
		include("../../admin/inputs/index.php");
		include("../../admin/components/navItem.debug.php");
	?>
	<div class="w-full h-full flex flex-col bg-brand-1">
		<div class="max-w-[2000px] mx-auto w-full h-full flex flex-col">


			<!-- body -->
			<div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
                <div class="w-full flex md:hidden px-5 pb-5 items-center">
                    <div class="w-full flex flex-col">
                        <span class="text-2xl text-white"><?=$headline ?></span>
                        <span class="text-white text-opacity-60 flex items-center">
                            <span class="fa fa-location-arrow text-white text-opacity-60 text-sm flex items-center mr-1"></span>
                            <span id="activeTabLabel" class="capitalize">General</span>
                        </span>
                    </div>
                    <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center" onclick="toggleAdminNavi()">
                        <span class="text-white text-3xl fa fa-bars"></span>
                    </div>
                </div>

                <div class="adminNavi hidden md:!hidden w-full h-full z-40 fixed top-0 left-0 bg-black bg-opacity-70 cursor-pointer [&.isActive]:flex" onclick="toggleAdminNavi();"></div>
                <div class="adminNavi hidden [&.isActive]:flex z-50 bg-brand-1 h-full pb-10 overflow-hidden w-3/4 fixed top-0 right-0 md:w-64 md:flex md:static md:bg-transparent">
                    <div class="w-full h-full pl-5 flex flex-col overflow-hidden">
                        <div class="flex items-center shrink-0 border-b border-solid border-white border-opacity-20 py-4 mr-4">
                            <a href="/admin/" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-white border-opacity-20 px-3">
                                <span class="fa fa-chevron-left text-white text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
                            </a>
                            <h1 class="text-white font-bold">Debugpanel</h1>
                            <div class="w-12 h-12 ml-auto text white cursor-pointer flex items-center justify-center md:hidden" onclick="toggleAdminNavi()">
                                <span class="text-white text-2xl fa fa-close"></span>
                            </div>
                        </div>
                        <div class="w-full h-full flex flex-col overflow-hidden">
                            <ul class="w-full h-full flex flex-col overflow-x-hidden overflow-y-auto">
                                <li class="flex w-full h-6 shrink-0"></li>
                                    <?php
                                        echo getNavItemDebug("myconfig" );
                                        echo getNavItemDebug("remotebuzzerlog" );
                                        echo getNavItemDebug("synctodrivelog" );
                                        echo getNavItemDebug("devlog" );
                                        echo getNavItemDebug("serverprocesses" );
                                        echo getNavItemDebug("bootconfig" );
                                        echo getNavItemDebug("printdb" );
                                        echo getNavItemDebug("installlog" );
                                        echo getNavItemDebug("githead" );
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
                                    <label class="settinglabel">
                                        <span data-i18n="debugpanel:autorefresh">debugpanel:autorefresh</span>
                                    </label>
                                </div>
                                <label id="debugpanel_autorefresh" class="adminCheckbox relative flex items-center cursor-pointer">
                                    <input class="hidden peer" type="checkbox" id="debugpanel_autorefresh" value="true"/>
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="hidden ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        <label class="adminCheckbox-true hidden" data-i18n="adminpanel_toggletextON"></label>
                                        <label class="adminCheckbox-false" data-i18n="adminpanel_toggletextOFF"></label>
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
        <script src="../../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
        <script type="text/javascript" src="../../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="../../node_modules/jquery/dist/jquery.min.js"></script>
        <script type="text/javascript" src="../../node_modules/selectize/dist/js/standalone/selectize.min.js"></script>
        <script type="text/javascript" src="../../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="../../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="../../resources/js/debugpanel.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script src="../../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
        <script type="text/javascript" src="../../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

        <script type="text/javascript" src="../../resources/js/main.admin.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
</body>
</html>

