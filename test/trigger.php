<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Remote Trigger';
$mainStyle = 'trigger.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>
<body>

    <div id="wrapper">
        <div class="buttonbar">
            <?php if ($config['remotebuzzer']['usebuttons']): ?>
                <?php if ($config['picture']['enabled'] && $config['remotebuzzer']['picturebutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remotePicture" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/start-picture');">
                        <i class="<?php echo $config['icons']['take_picture'] ?>"></i>
                        <?=$languageService->translate('takePhoto')?>
                    </a>
                <?php endif; ?>

                <?php if ($config['collage']['enabled'] && $config['remotebuzzer']['collagebutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remoteCollage" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/start-collage');">
                        <i class="<?php echo $config['icons']['take_collage'] ?>"></i>
                        <?=$languageService->translate('takeCollage')?>
                    </a>
                <?php endif; ?>

                <?php if ($config['custom']['enabled'] && $config['remotebuzzer']['custombutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remoteCustom" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/start-custom');">
                        <i class="<?php echo $config['icons']['take_custom'] ?>"></i>
                        <?=$config['custom']['btn_text']?>
                    </a>
                <?php endif; ?>

                <?php if ($config['video']['enabled'] && $config['remotebuzzer']['videobutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remoteVideo" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/start-video');">
                        <i class="<?php echo $config['icons']['take_video'] ?>"></i>
                        <?=$languageService->translate('takeVideo')?>
                    </a>
                <?php endif; ?>

                <?php if ($config['print']['from_result'] && $config['remotebuzzer']['printbutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remotePrint" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/start-print');">
                        <i class="<?php echo $config['icons']['print'] ?>"></i>
                        <?=$languageService->translate('print')?>
                    </a>
                <?php endif; ?>

                <?php if ($config['remotebuzzer']['rebootbutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remoteReboot" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/reboot-now');">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        <?=$languageService->translate('reboot_button')?>
                    </a>
                <?php endif; ?>

                <?php if ($config['remotebuzzer']['shutdownbutton']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> remoteShutdown" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/shutdown-now');">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        <?=$languageService->translate('shutdown_button')?>
                    </a>
                <?php endif; ?>

            <?php endif; ?>

            <?php if ($config['remotebuzzer']['userotary']): ?>
                <a href="#" class="<?php echo $btnClass; ?> remotePrevious" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/rotary-ccw');">
                    <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    <?=$languageService->translate('previous_element')?>
                </a>
                <a href="#" class="<?php echo $btnClass; ?> remoteNext" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/rotary-cw');">
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    <?=$languageService->translate('next_element')?>
                </a>
                <a href="#" class="<?php echo $btnClass; ?> remoteClick" onclick="photoboothTools.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/rotary-btn-press');">
                    <i class="fa fa-circle" aria-hidden="true"></i>
                    <?=$languageService->translate('click_element')?>
                </a>
            <?php endif; ?>

            <a href="<?php echo PathUtility::getPublicPath('test'); ?>" class="<?php echo $btnClass; ?>">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
                <?=$languageService->translate('back')?>
            </a>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
</body>
</html>
