<?php
session_start();

require_once('../lib/config.php');

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_manual'] && isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['manual'])
) {
    require_once('../lib/configsetup.inc.php');
} else {
    header('location: ../login');
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

	<title><?=$config['ui']['branding']?> Manual</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<link rel="stylesheet" type="text/css" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" type="text/css" href="../node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" type="text/css" href="../node_modules/material-icons/css/material-icons.css">

	<!-- tw admin -->
	<link rel="stylesheet" href="../resources/css/tailwind.admin.css"/>
</head>

<body>
	<?php 
		include("../admin/helper/index.php");
		include("../admin/inputs/index.php");
	?>
	<div class="w-full h-full flex flex-col bg-brand-1">
		<div class="max-w-[2000px] mx-auto w-full h-full flex flex-col">


			<!-- body -->
			<div class="w-full h-full flex flex-1 flex-col md:flex-row mt-5 overflow-hidden">
				<?php 
					$sidebarHeadline = "Manual";
					include("../admin/components/sidebar.php"); 
				?>
				<div class="flex flex-1 flex-col bg-content-1 rounded-xl ml-5 mr-5 mb-5 md:ml-0 overflow-hidden">

					<div class="w-full h-full flex flex-col" autocomplete="off">
						<div class="adminContent w-full flex flex-1 flex-col py-5 overflow-x-hidden overflow-y-auto">
							<form>
								<?php
									$i = 0;
									foreach($configsetup as $panel => $fields) {
										$panelHidden = "visible";
										if (empty($fields['view'])) {
											$fields['view'] = 'basic';
										};
										switch ($fields['view'])
										{
											case 'experimental':
													if (!$config['adminpanel']['experimental_settings']) { $panelHidden = 'hidden'; };
											case 'expert':
													if ($config['adminpanel']['view'] == 'advanced') { $panelHidden = 'hidden'; };
													if ($config['adminpanel']['view'] == 'basic') { $panelHidden = 'hidden'; };
											case 'advanced':
													if ($config['adminpanel']['view'] == 'basic') { $panelHidden = 'hidden'; };
											case 'basic':
													break;
										};
						
										// headline
										echo '<div id="'.$panel.'" class="adminSection '. $panelHidden .'">';
											echo '<h2 class="text-brand-1 text-xl font-bold pt-4 px-4 lg:pt-8 lg:px-8 mb-4"> <span data-i18n="'.$panel.'">'.$panel.'</span></h2>';
											echo '<div class="flex flex-col px-4 lg:px-8 py-2">';
												echo '<div class="flex flex-col rounded-xl p-3 shadow-xl bg-white">';

													foreach($fields as $key => $field) {
														if ($key == 'platform' || $key == 'view') {
														continue;
														};

														if (!isset($field['view'])) {
															$field['view'] = 'basic';
														};

														switch ($field['view'])
														{
															case 'expert':
																		if ($config['adminpanel']['view'] == 'advanced') { $field['type'] = 'hidden'; };
															case 'advanced':
																		if ($config['adminpanel']['view'] == 'basic') { $field['type'] = 'hidden'; };
															case 'basic':
																		break;
														};

														switch($field['type']) {
															case 'checkbox':
																echo '<div class="w-full max-w-3xl pb-3 mb-3 border-b border-solid border-gray-200">';
																echo '<h3 class="text-brand-1 text-md font-bold mb-1"><span data-i18n="'.$panel.':'.$key.'">'.$panel.':'.$key.'</span></h3>';
																echo '<p class="leading-8" data-i18n="manual:'.$panel.':'.$key.'">manual:'.$panel.':'.$key.'</p>';
																echo '</div>';
																break;
															case 'multi-select':
															case 'range':
															case 'select':
															case 'input':
																echo '<div class="w-full max-w-3xl pb-3 mb-3 border-b border-solid border-gray-200">';
																echo '<h3 class="text-brand-1 text-md font-bold mb-1"><span data-i18n="'.$panel.':'.$key.'"></span></h3>';
																echo '<p class="leading-8" data-i18n="manual:'.$panel.':'.$key.'">manual:'.$panel.':'.$key.'</p>';
																echo '</div>';
																break;
															case 'color':
															case 'hidden':
																if(is_string($field['value'])) {
																	echo '<input type="hidden" name="'.$field['name'].'" value="'.$field['value'].'"/>';
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
									<a href="/faq/" class="flex items-center hover:underline hover:text-brand-1 mb-2" title="FAQ" target="newwin"><span data-i18n="show_faq"></span> <i class="ml-2 <?php echo $config['icons']['faq']; ?>"></i></a>
									<a href="https://photoboothproject.github.io" target="_blank" class="flex items-center hover:underline hover:text-brand-1"><span data-i18n="show_wiki"></span></a>
								</div>
							</form>
						</div>
					</div>

				</div>
			</div>

		</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/manual.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

	<script type="text/javascript" src="../resources/js/main.admin.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

</body>
</html>
