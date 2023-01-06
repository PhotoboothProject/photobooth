<?php
session_start();

require_once 'lib/config.php';
if (!$config['ui']['skip_welcome']) {
    if (!is_file('.skip_welcome')) {
        header('location: welcome.php');
        exit();
    }
}

if ($config['live_keying']['enabled']) {
    header('location: livechroma.php');
    exit();
}

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['index'])
) {
    require_once 'lib/db.php';
    require_once 'lib/filter.php';

    if ($config['database']['enabled']) {
        $images = getImagesFromDB();
    } else {
        $images = getImagesFromDirectory($config['foldersAbs']['images']);
    }

    $imagelist = $config['gallery']['newest_first'] === true ? array_reverse($images) : $images;

    $btnClass = 'btn btn--' . $config['ui']['button'];
    $btnShape = 'shape--' . $config['ui']['button'];
    $uiShape = 'shape--' . $config['ui']['style'];
    $GALLERY_FOOTER = true;
} else {
    header('location: ' . $config['protect']['index_redirect']);
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
    <meta name="msapplication-TileColor" content="<?= $config['colors']['primary'] ?>">
    <meta name="theme-color" content="<?= $config['colors']['primary'] ?>">

    <title><?= $config['ui']['branding'] ?></title>

    <!-- Favicon + Android/iPhone Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="resources/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="resources/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="resources/img/favicon-16x16.png">
    <link rel="manifest" href="resources/img/site.webmanifest">
    <link rel="mask-icon" href="resources/img/safari-pinned-tab.svg" color="#5bbad5">

    <!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>

    <link rel="stylesheet" href="node_modules/normalize.css/normalize.css"/>
    <link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css"/>
    <link rel="stylesheet" href="node_modules/material-icons/iconfont/material-icons.css">
    <link rel="stylesheet" href="node_modules/material-icons/css/material-icons.css">
    <link rel="stylesheet" href="vendor/PhotoSwipe/dist/photoswipe.css"/>
    <link rel="stylesheet" href="vendor/PhotoSwipe/dist/default-skin/default-skin.css"/>
    <link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_style.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php if ($config['gallery']['bottom_bar']): ?>
        <link rel="stylesheet" href="resources/css/photoswipe-bottom.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
    <?php if (is_file("private/overrides.css")): ?>
        <link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
</head>

<body class="deselect">
<img id="picture--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['picture']['htmlframe']; ?>" alt="pictureFrame" />
<img id="collage--frame" class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>" src="<?php echo $config['collage']['htmlframe']; ?>" alt="collageFrame" />
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
    <?php include('template/' . $config['ui']['style'] . '.template.php'); ?>

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

            <?php if ($config['button']['homescreen']): ?>
                <a href="#" class="<?php echo $btnClass; ?> homebtn rotaryfocus"><i class="<?php echo $config['icons']['home']; ?>"></i> <span
                            data-i18n="home"></span></a>
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
                <?php if (!($config['collage']['enabled'] && $config['collage']['only'])): ?>
                    <a href="#" class="<?php echo $btnClass; ?> newpic rotaryfocus"><i class="<?php echo $config['icons']['take_picture']; ?>"></i> <span
                                data-i18n="newPhoto"></span></a>
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
        </div>

        <?php if ($config['qr']['enabled']): ?>
            <div id="qrCode" class="modal">
                <div class="modal__body <?php echo $uiShape; ?>"></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($config['gallery']['enabled']): ?>
        <?php include('template/gallery.template.php'); ?>
    <?php endif; ?>
</div>

<?php include('template/pswp.template.php'); ?>

<?php include('template/send-mail.template.php'); ?>

<div class="modal" id="print_mesg">
    <div class="modal__body"><span data-i18n="printing"></span></div>
</div>

<div id="adminsettings">
    <div style="position:absolute; bottom:0; right:0;">
        <img src="resources/img/spacer.png" alt="adminsettings" ondblclick="adminsettings()"/>
    </div>
</div>

<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
<script type="text/javascript" src="api/config.php?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/adminshortcut.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="vendor/PhotoSwipe/dist/photoswipe.min.js"></script>
<script type="text/javascript" src="vendor/PhotoSwipe/dist/photoswipe-ui-default.min.js"></script>
<script type="text/javascript" src="resources/js/tools.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/remotebuzzer_client.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/photoinit.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/theme.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script type="text/javascript" src="resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
<script type="text/javascript" src="resources/js/i18n.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

<?php require_once('lib/services_start.php'); ?>
</body>
</html>
