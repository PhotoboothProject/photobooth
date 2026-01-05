<?php
use Photobooth\Utility\PathUtility;

?>
<!-- Start Page -->
<div class="stage stage--start rotarygroup" data-stage="start">
    <?php include PathUtility::getAbsolutePath('template/components/start.logo.php'); ?>
    <div class="stage-inner">
        <?php if ($config['event']['enabled'] || $config['start_screen']['title_visible']): ?>
            <div class="names<?= ($config['ui']['decore_lines']) ? ' names--decoration' : '' ?>">
                <div class="names-inner">
                    <?php if ($config['event']['enabled']): ?>
                        <h1 class="event-text">
                            <?= $config['event']['textLeft'] ?>
                            <i class="fa <?= $config['event']['symbol'] ?>" aria-hidden="true"></i>
                            <?= $config['event']['textRight'] ?>
                        </h1>
                        <?php if ($config['start_screen']['title_visible']): ?>
                            <h1 class="start-text"><?= $config['start_screen']['title'] ?></h1>
                        <?php endif; ?>
                        <?php if ($config['start_screen']['subtitle_visible']): ?>
                            <h2 class="start-text"><?= $config['start_screen']['subtitle'] ?></h2>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($config['start_screen']['title_visible']): ?>
                        <h1 class="start-text"><?= $config['start_screen']['title'] ?></h1>
                        <?php endif; ?>
                        <?php if ($config['start_screen']['subtitle_visible']): ?>
                        <h2 class="start-text"><?= $config['start_screen']['subtitle'] ?></h2>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
<?php
if ($config['ui']['selfie_mode']) {
    include PathUtility::getAbsolutePath('template/components/selfieAction.php');
} else {
    include PathUtility::getAbsolutePath('template/components/actionBtn.php');
    if ($config['collage']['enabled'] && $config['collage']['allow_selection']) {
        include PathUtility::getAbsolutePath('template/components/collageSelection.php');
    }
}
?>
    </div>
    <?php
    $screensaverMode = $config['screensaver']['mode'] ?? 'image';
$screensaverImageSource = $config['screensaver']['image_source'] ?? '';
$screensaverVideoSource = $config['screensaver']['video_source'] ?? '';

$screensaverSource = '';
if ($screensaverMode === 'image' && $screensaverImageSource) {
    $screensaverSource = PathUtility::getPublicPath($screensaverImageSource);
} elseif ($screensaverMode === 'video' && $screensaverVideoSource) {
    $screensaverSource = PathUtility::getPublicPath($screensaverVideoSource);
}
?>
    <div
        id="screensaver-overlay"
        class="screensaver-overlay"
        data-mode="<?= $screensaverMode ?>"
        data-source="<?= $screensaverSource ?>"
        style="display: none;"
    >
        <div id="screensaver-text-top" class="screensaver-overlay__text screensaver-overlay__text--top"></div>
        <div id="screensaver-text-center" class="screensaver-overlay__text screensaver-overlay__text--center"></div>
        <img id="screensaver-image" class="screensaver-overlay__image" alt="screensaver">
        <video id="screensaver-video" loop muted playsinline></video>
        <div id="screensaver-text-bottom" class="screensaver-overlay__text screensaver-overlay__text--bottom"></div>
    </div>
    <?php include PathUtility::getAbsolutePath('template/components/github-corner.php'); ?>
</div>
