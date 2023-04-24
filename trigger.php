<?php
require_once 'lib/config.php';

$btnClass = 'btn btn--' . $config['ui']['button'];
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

	<title><?=$config['ui']['branding']?> Remote Trigger</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="resources/img/favicon-16x16.png">
	<link rel="manifest" href="resources/img/site.webmanifest">
	<link rel="mask-icon" href="resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" href="node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" href="node_modules/material-icons/css/material-icons.css">
	<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_style.css" />
	<link rel="stylesheet" href="resources/css/trigger.css" />
	<?php if (is_file("private/overrides.css")): ?>
	<link rel="stylesheet" href="private/overrides.css" />
	<?php endif; ?>
</head>

<body>

    <div id="wrapper">
		<div class="buttonbar">

			<?php if ($config['remotebuzzer']['usebuttons']): ?>
                <?php if ($config['picture']['enabled'] && $config['remotebuzzer']['picturebutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remotePicture" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/start-picture');"><i class="<?php echo $config['icons']['take_picture'] ?>"></i> <span data-i18n="takePhoto"></span></a>
			    <?php endif; ?>

			    <?php if ($config['collage']['enabled'] && $config['remotebuzzer']['collagebutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remoteCollage" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/start-collage');"><i class="<?php echo $config['icons']['take_collage'] ?>"></i> <span data-i18n="takeCollage"></span></a>
			    <?php endif; ?>

			    <?php if ($config['custom']['enabled'] && $config['remotebuzzer']['custombutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remoteCustom" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/start-custom');"><i class="<?php echo $config['icons']['take_custom'] ?>"></i> <span><?php echo $config['custom']['btn_text'] ?></span></a>
			    <?php endif; ?>

			    <?php if ($config['video']['enabled'] && $config['remotebuzzer']['videobutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remoteVideo" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/start-video');"><i class="<?php echo $config['icons']['take_video'] ?>"></i> <span data-i18n="takeVideo"></span></a>
			    <?php endif; ?>

			    <?php if ($config['print']['from_result'] && $config['remotebuzzer']['printbutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remotePrint" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/start-print');"><i class="<?php echo $config['icons']['print'] ?>"></i> <span data-i18n="print"></span></a>
			    <?php endif; ?>

			    <?php if ($config['remotebuzzer']['rebootbutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remoteReboot" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/reboot-now');"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <span data-i18n="reboot_button"></span></a>
			    <?php endif; ?>

			    <?php if ($config['remotebuzzer']['shutdownbutton']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remoteShutdown" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/shutdown-now');"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <span data-i18n="shutdown_button"></span></a>
			    <?php endif; ?>

			<?php endif; ?>

			<?php if ($config['remotebuzzer']['userotary']): ?>
			    <a href="#" class="<?php echo $btnClass; ?> remotePrevious" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/rotary-ccw');"><i class="fa fa-chevron-left" aria-hidden="true"></i> <span data-i18n="previous_element"></span></a>
                <a href="#" class="<?php echo $btnClass; ?> remoteNext" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/rotary-cw');"><i class="fa fa-chevron-right" aria-hidden="true"></i> <span data-i18n="next_element"></span></a>
			    <a href="#" class="<?php echo $btnClass; ?> remoteClick" onclick="photoboothTools.getRequest('http://' + config.webserver.ip + ':' + config.remotebuzzer.port + '/commands/rotary-btn-press');"><i class="fa fa-circle" aria-hidden="true"></i> <span data-i18n="click_element"></span></a>
		    <?php endif; ?>
		</div>
	</div>

	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="resources/js/tools.js"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js"></script>
</body>
</html>
