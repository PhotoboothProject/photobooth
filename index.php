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

include($fileRoot . 'template/components/helper/index.php');
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
<div id="aperture" class="relative z-50"></div>
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

    <?php
        include($fileRoot . 'template/' . $config['ui']['style'] . '.template.php');

        if ($config['filters']['enabled']) {
            include($fileRoot . 'template/components/start.filter.php');
        }

        include($fileRoot . 'template/components/start.loader.php');
        include($fileRoot . 'template/components/start.results.php');

        if ($config['gallery']['enabled']) {
            include($fileRoot . 'template/gallery.template.php');
        }
    ?>


</div>

<?php
    include($fileRoot . 'template/components/modal.qr.php');
    include($fileRoot . 'template/send-mail.template.php');
    include($fileRoot . 'template/modal.template.php');
    include($fileRoot . 'template/components/adminShortcut.php');
    include($fileRoot . 'template/components/main.footer.php');
?>

<script type="text/javascript" src="<?=$fileRoot?>resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=$fileRoot?>resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=$fileRoot?>resources/js/qrcode.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="<?=$fileRoot?>resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

<?php require_once($fileRoot . 'lib/services_start.php'); ?>
</body>
</html>
