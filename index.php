<?php
session_start();
$fileRoot = '';
require_once $fileRoot . 'lib/config.php';
if (!$config['ui']['skip_welcome']) {
    if (!is_file($fileRoot . 'welcome/.skip_welcome')) {
        header('location: ' . $fileRoot . 'welcome/');
        exit();
    }
}

if ($config['chromaCapture']['enabled']) {
    header('location: ' . $fileRoot . 'chroma/');
    exit();
}

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_index'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['index'])
) {
    require_once $fileRoot . 'lib/filter.php';

    $pageTitle = $config['ui']['branding'];
    $mainStyle = $config['ui']['style'] . '_style.css';
    $photoswipe = true;
    $randomImage = false;
    $remoteBuzzer = true;
    $chromaKeying = false;
    $GALLERY_FOOTER = true;
} else {
    header('location: ' . $config['protect']['index_redirect']);
    exit();
}

include($fileRoot . 'template/components/main.head.php');
?>

<body class="deselect">

<?php if ($config['preview']['showFrame'] && !empty($config['picture']['htmlframe'])): ?>
<img id="picture--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['picture']['htmlframe']; ?>" alt="pictureFrame" />
<?php endif; ?>
<?php if ($config['preview']['showFrame'] && !empty($config['collage']['htmlframe'])): ?>
<img id="collage--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['collage']['htmlframe']; ?>" alt="collageFrame" />
<?php endif; ?>

<video id="video--view" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>"
       autoplay playsinline></video>
<div id="blocker"></div>
<div id="aperture"></div>
<?php if ($config['video']['enabled'] && $config['video']['animation']): ?>
    <div id="videoAnimation">
        <ul class="left">
            <?php for ($i = 1; $i <= 50; $i++) {
                print('<li class="reel-item"></li>');
            } ?>
        </ul>
        <ul class="right">
            <?php for ($i = 1; $i <= 50; $i++) {
                print('<li class="reel-item"></li>');
            } ?>
        </ul>
    </div>
<?php endif; ?>
<div id="wrapper">
    <?php include($fileRoot . 'template/' . $config['ui']['style'] . '.template.php'); ?>

    <!-- image Filter Pane -->
    <?php if ($config['filters']['enabled']): ?>
        <div id="mySidenav" class="dragscroll sidenav rotarygroup">
            <a href="#" class="<?php echo $btnClass; ?> closebtn rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>

            <?php foreach (AVAILABLE_FILTERS as $filter => $name): ?>
                <?php if (!in_array($filter, $config['filters']['disabled'])): ?>
                    <div id="<?= $filter ?>"
                         class="filter <?php if ($config['filters']['defaults'] === $filter) echo 'activeSidenavBtn'; ?>">
                        <a class="btn btn--small rotaryfocus" href="#"><?= $name ?></a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Loader -->
    <div class="stages" id="loader">
        <div class="loaderInner">
            <div class="spinner">
                <i class="<?php echo $config['icons']['spinner']; ?>"></i>
            </div>

            <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

            <div id="counter">
                <canvas id="video--sensor"></canvas>
            </div>
            <div class="cheese"></div>
            <div class="loaderImage"></div>
            <div class="loading rotarygroup"></div>
        </div>
    </div>

    <!-- Result Page -->
    <div class="stages rotarygroup" id="result">

        <div class="resultInner hidden">

            <?php if ($config['button']['homescreen']): ?>
                <a href="#" class="<?php echo $btnClass; ?> homebtn rotaryfocus"><i class="<?php echo $config['icons']['home']; ?>"></i> <span
                            data-i18n="home"></span></a>
            <?php endif; ?>

            <?php if ($config['ui']['result_buttons']): ?>
                <?php if ($config['video']['enabled']): ?>
                    <?php if ($config['video']['qr']): ?>
                        <img src="" id="resultVideoQR" alt="video qr code">
                    <?php endif; ?>
                    <?php if ($config['video']['gif']) { ?>
                        <img id="resultVideo" src="" alt="result gif">
                    <?php } else { ?>
                        <video id="resultVideo" autoplay loop muted>
                        </video>
                    <?php } ?>
                <?php endif; ?>

                <?php if ($config['gallery']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> gallerybtn rotaryfocus"><i class="<?php echo $config['icons']['gallery']; ?>"></i> <span data-i18n="gallery"></span></a>
                <?php endif; ?>

                <?php if ($config['qr']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> qrbtn rotaryfocus"><i class="<?php echo $config['icons']['qr']; ?>"></i> <span
                                data-i18n="qr"></span></a>
                <?php endif; ?>

                <?php if ($config['mail']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> mailbtn rotaryfocus"><i class="<?php echo $config['icons']['mail']; ?>"></i> <span
                                data-i18n="mail"></span></a>
                <?php endif; ?>

                <?php if ($config['print']['from_result']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> printbtn rotaryfocus"><i class="<?php echo $config['icons']['print']; ?>"></i> <span
                                data-i18n="print"></span></a>
                <?php endif; ?>

                <?php if (!$config['button']['force_buzzer']): ?>
                    <?php if ($config['picture']['enabled']): ?>
                        <a href="#" class="<?php echo $btnClass; ?> newpic rotaryfocus"><i class="<?php echo $config['icons']['take_picture']; ?>"></i> <span
                                    data-i18n="newPhoto"></span></a>
                    <?php endif; ?>

                    <?php if ($config['custom']['enabled']): ?>
                        <a href="#" class="<?php echo $btnClass; ?> newcustom rotaryfocus"><i class="<?php echo $config['icons']['take_custom']; ?>"></i>
                            <span><?php echo $config['custom']['btn_text']; ?></span></a>
                    <?php endif; ?>

                    <?php if ($config['collage']['enabled']): ?>
                        <a href="#" class="<?php echo $btnClass; ?> newcollage rotaryfocus"><i class="<?php echo $config['icons']['take_collage']; ?>"></i>
                            <span
                                    data-i18n="newCollage"></span></a>
                    <?php endif; ?>

                    <?php if ($config['video']['enabled']): ?>
                        <a href="#" class="<?php echo $btnClass; ?> newVideo rotaryfocus"><i class="<?php echo $config['icons']['take_video']; ?>"></i> <span
                                    data-i18n="newVideo"></span></a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($config['filters']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> imageFilter rotaryfocus"><i class="<?php echo $config['icons']['filter']; ?>"></i> <span
                                data-i18n="selectFilter"></span></a>
                <?php endif; ?>

                <?php if ($config['picture']['allow_delete']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> deletebtn <?php if ($config['delete']['no_request']) {
                        echo 'rotaryfocus';
                    } ?> "><i class="<?php echo $config['icons']['delete']; ?>"></i> <span data-i18n="delete"></span></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($config['qr']['enabled']): ?>
            <div id="qrCode" class="modal">
                <div class="modal__body <?php echo $uiShape; ?>"></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($config['gallery']['enabled']): ?>
        <?php include($fileRoot . 'template/gallery.template.php'); ?>
    <?php endif; ?>
</div>

<?php include($fileRoot . 'template/send-mail.template.php'); ?>
<?php include($fileRoot . 'template/modal.template.php'); ?>

<div id="adminsettings">
    <div style="position:absolute; bottom:0; right:0;">
        <img src="<?=$fileRoot?>resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings('<?=$fileRoot?>')"/>
    </div>
</div>

<?php include($fileRoot . 'template/components/main.footer.php'); ?>

<script type="text/javascript" src="<?=$fileRoot?>resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
