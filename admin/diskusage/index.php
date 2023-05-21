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
    require_once '../../lib/diskusage.php';
} else {
    header('location: ../../login');
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?> Disk Usage</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="../../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="../../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../../node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" type="text/css" href="../../node_modules/material-icons/css/material-icons.css">
	<!-- tw admin -->
	<link rel="stylesheet" href="../../resources/css/tailwind.admin.css"/>
</head>

<body>
	<?php 
		include("../helper/index.php");
		include("../inputs/index.php");
	?>
	<div class="w-full h-screen grid place-items-center absolute bg-brand-1 px-6 py-12 overflow-x-hidden overflow-y-auto">
		<div class="w-full flex items-center justify-center flex-col">

			<div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
				<div class="w-full flex items-center pb-3 mb-3 border-b border-solid border-gray-200">
					<a href="/admin/" class="h-4 mr-4 flex items-center justify-center border-r border-solid border-black border-opacity-20 pr-3">
						<span class="fa fa-chevron-left text-brand-1 text-opacity-60 text-md hover:text-opacity-100 transition-all"></span>
					</a>
					<h2 class="text-brand-1 text-xl font-bold">
						<?=$config['ui']['branding']?>
						<span data-i18n="disk_usage"></span>
					</h2>
				</div>
				<?php
					foreach ($config['foldersAbs'] as $key => $folder) {
						$path = $config['foldersAbs'][$key];
						$disk_used = foldersize($config['foldersAbs'][$key]);

						echo('<div class="pb-3 mb-3 border-b border-solid border-gray-200 flex flex-col">');
						echo('<h3 class="font-bold"><span data-i18n="path"></span> ' . $folder . '</h3>');
						echo('<div><span class="flex text-sm mt-2" data-i18n="foldersize"></span></div><span class="text-brand-1">'. format_size($disk_used) .'</span>');
						echo('<div><span class="flex text-sm mt-2" data-i18n="filecount"></span></div><span class="text-brand-1">' . get_filecount($path) .'</span>'); 
						echo('</div>');
						

					}
				?>
			</div>

		</div>
	</div>

	<script type="text/javascript" src="../../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../../resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../../resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../../resources/js/diskusage.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="module" src="../../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
