<?php
session_start();

require_once 'lib/config.php';

// Login / Authentication check
if (
    !$config['login']['enabled'] ||
    (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) ||
    ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || !$config['protect']['index'])
) {
    require_once 'lib/db.php';

    $database = new DatabaseManager();
    $database->db_file = DB_FILE;
    $database->file_dir = IMG_DIR;
    if ($config['database']['enabled']) {
        $images = $database->getContentFromDB();
    } else {
        $images = $database->getFilesFromDirectory();
    }
    $imagelist = $config['gallery']['newest_first'] === true ? array_reverse($images) : $images;

    $btnClass = 'btn btn--' . $config['ui']['button'] . ' chromaCapture-btn';
    $btnShape = 'shape--' . $config['ui']['button'];
    $uiShape = 'shape--' . $config['ui']['style'];

    $GALLERY_FOOTER = false;
    $fileRoot = '';
    $pageTitle = $config['ui']['branding'] . ' Chroma capture';
} else {
    header('location: ' . $config['protect']['index_redirect']);
    exit();
}
?>
<!doctype html>
<html>
<head>
    <?php include('template/components/mainHead.php'); ?>
    <link rel="stylesheet" href="node_modules/photoswipe/dist/photoswipe.css"/>
    <link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_chromacapture.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php if (is_file("private/overrides.css")): ?>
        <link rel="stylesheet" href="private/overrides.css?v=<?php echo $config['photobooth']['version']; ?>"/>
    <?php endif; ?>
</head>

<body>
<div id="blocker"></div>
<div id="aperture"></div>
<div class="chromawrapper">
    <div class="rotarygroup" id="start">
        <div class="top-bar">
            <?php if (!$config['chromaCapture']['enabled']): ?>
                <a href="index.php" class="<?php echo $btnClass; ?> chromaCapture-close-btn rotaryfocus"><i class="<?php echo $config['icons']['close']; ?>"></i></a>
            <?php endif; ?>

            <?php if ($config['gallery']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass ?> chromaCapture-gallery-btn rotaryfocus"><i class="<?php echo $config['icons']['gallery']; ?>"></i>
                    <span data-i18n="gallery"></span></a>
            <?php endif; ?>
        </div>

        <div class="canvasWrapper <?php echo $uiShape; ?> noborder initial">
            <canvas class="<?php echo $uiShape; ?> noborder" id="mainCanvas"></canvas>
        </div>

        <div class="chromaNote <?php echo $uiShape ?>">
            <span data-i18n="chromaInfoBefore"></span>
        </div>

        <div class="stages" id="loader">
            <div class="loaderInner">
                <div class="spinner">
                    <i class="<?php echo $config['icons']['spinner']; ?>"></i>
                </div>

                <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

                <video id="video--view"
                       class="<?php echo $config['preview']['flip']; ?> <?php echo $config['preview']['style']; ?>"
                       autoplay playsinline></video>

                <div id="counter">
                    <canvas id="video--sensor"></canvas>
                </div>
                <div class="cheese"></div>
                <div class="loading"></div>
            </div>
        </div>

        <!-- Result Page -->
        <div class="stages" id="result"></div>

        <div class="backgrounds <?php echo $uiShape ?>">
            <?php
            $dir = $config['keying']['background_path'] . DIRECTORY_SEPARATOR;
            $cdir = scandir($dir);
            foreach ($cdir as $key => $value) {
                if (!in_array($value, array(".", "..")) && !is_dir($dir . $value)) {
                    echo '<img src="' . $dir . $value . '" class="' . $uiShape . ' backgroundPreview rotaryfocus" onclick="setBackgroundImage(this.src)">';
                }
            }
            ?>
        </div>

        <div class="chroma-control-bar">
            <a href="#" class="<?php echo $btnClass; ?> takeChroma chromaCapture rotaryfocus"><i class="<?php echo $config['icons']['take_picture']; ?>"></i>
                <span data-i18n="takePhoto"></span></a>
            <?php if ($config['picture']['allow_delete']): ?>
                <a href="#" class="<?php echo $btnClass; ?> deletebtn chromaCapture"><i class="<?php echo $config['icons']['delete']; ?>"></i> <span
                            data-i18n="delete"></span></a>
            <?php endif; ?>
            <a href="#" class="<?php echo $btnClass; ?> reloadPage chromaCapture rotaryfocus"><i class="<?php echo $config['icons']['refresh']; ?>"></i>
                <span data-i18n="reload"></span></a>
        </div>
    </div>
    <div class="rotarygroup">

        <div id="wrapper">
            <?php include('template/gallery.template.php'); ?>
        </div>

        <?php include('template/send-mail.template.php'); ?>

        <div class="modal" id="print_mesg">
            <div class="modal__body"><span data-i18n="printing"></span></div>
        </div>
        <div class="modal" id="modal_mesg"></div>

        <script type="text/javascript">
            onCaptureChromaView = true;
        </script>

        <?php include('template/components/mainFooter.php'); ?>

        <script type="text/javascript" src="node_modules/photoswipe/dist/umd/photoswipe.umd.min.js"></script>
        <script type="text/javascript" src="node_modules/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js"></script>
        <script type="text/javascript" src="resources/js/remotebuzzer_client.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="resources/js/photoswipe.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="resources/js/preview.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <script type="text/javascript" src="resources/js/core.js?v=<?php echo $config['photobooth']['version']; ?>"></script>
        <?php if ($config['keying']['variant'] === 'marvinj'): ?>
            <script type="text/javascript" src="node_modules/marvinj/marvinj/release/marvinj-1.0.js"></script>
        <?php else: ?>
            <script type="text/javascript" src="vendor/Seriously/seriously.js"></script>
            <script type="text/javascript" src="vendor/Seriously/effects/seriously.chroma.js"></script>
        <?php endif; ?>
        <script type="text/javascript" src="resources/js/chromakeying.js?v=<?php echo $config['photobooth']['version']; ?>"></script>

        <?php require_once('lib/services_start.php'); ?>
    </div>
</div>
</body>
</html>
