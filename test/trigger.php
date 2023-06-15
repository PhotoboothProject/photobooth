<?php
$fileRoot = '../';

require_once $fileRoot . 'lib/config.php';

$pageTitle = $config['ui']['branding'] . ' Remote Trigger';
$mainStyle = 'trigger.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include($fileRoot . 'template/components/main.head.php');
?>

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

    <?php include($fileRoot . 'template/components/main.footer.php'); ?>
</body>
</html>
