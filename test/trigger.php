<?php

require_once '../lib/boot.php';

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$pageTitle = $config['ui']['branding'] . ' Remote Trigger';
$mainStyle = $config['ui']['style'] . '_style.css';
$photoswipe = false;
$remoteBuzzer = false;
$chromaKeying = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>
<body>

    <div id="wrapper">
        <div id="trigger">
            <div class="buttonbar">
                <?php if ($config['remotebuzzer']['usebuttons']): ?>
                    <?php if ($config['picture']['enabled'] && $config['remotebuzzer']['picturebutton']): ?>
                        <a href="#" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="start-picture">
                            <i class="<?php echo $config['icons']['take_picture'] ?>"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('takePhoto')?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['collage']['enabled'] && $config['remotebuzzer']['collagebutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="start-collage">
                            <i class="<?php echo $config['icons']['take_collage'] ?>"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('takeCollage')?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['custom']['enabled'] && $config['remotebuzzer']['custombutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="start-custom">
                            <i class="<?php echo $config['icons']['take_custom'] ?>"></i>
                            <span class="text-sm whitespace-nowrap"><?=$config['custom']['btn_text']?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['video']['enabled'] && $config['remotebuzzer']['videobutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="start-video">
                            <i class="<?php echo $config['icons']['take_video'] ?>"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('takeVideo')?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['print']['from_result'] && $config['remotebuzzer']['printbutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="start-print">
                            <i class="<?php echo $config['icons']['print'] ?>"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('print')?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['remotebuzzer']['rebootbutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="reboot-now">
                            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('reboot_button')?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($config['remotebuzzer']['shutdownbutton']): ?>
                        <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="shutdown-now">
                            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                            <span class="text-sm whitespace-nowrap"><?=$languageService->translate('shutdown_button')?></span>
                        </a>
                    <?php endif; ?>

                <?php endif; ?>

                <?php if ($config['remotebuzzer']['userotary']): ?>
                    <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="rotary-ccw">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                        <span class="text-sm whitespace-nowrap"><?=$languageService->translate('previous_element')?>
                    </a>
                    <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="rotary-cw">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                        <span class="text-sm whitespace-nowrap"><?=$languageService->translate('next_element')?>
                    </a>
                    <a href="#" type="button" class="<?php echo $btnClass; ?>" data-command="remotebuzzer" data-action="rotary-btn-press">
                        <i class="fa fa-circle" aria-hidden="true"></i>
                        <span class="text-sm whitespace-nowrap"><?=$languageService->translate('click_element')?></span>
                    </a>
                <?php endif; ?>

                <a href="<?php echo PathUtility::getPublicPath('test'); ?>" class="<?php echo $btnClass; ?>">
                    <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    <span class="text-sm whitespace-nowrap"><?=$languageService->translate('back')?>
                </a>
            </div>
        </div>
    </div>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
</body>
</html>
