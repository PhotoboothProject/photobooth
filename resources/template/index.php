<?php

if (file_exists(__DIR__ . '/config.inc.php')) {
    $templateConfig = require __DIR__ . '/config.inc.php';
}

if (!isset($templateConfig)) {
    die('config.inc.php missing');
}

// Determine access mode: single image view or gallery
$requestedImage = isset($_GET['img']) ? basename((string) $_GET['img']) : '';
$galleryEnabled = (bool) ($templateConfig['gallery_enabled'] ?? false);
$mode = $requestedImage !== '' ? 'single' : ($galleryEnabled ? 'gallery' : 'single');

$images = [
    'images' => glob(__DIR__ . '/' . $templateConfig['paths']['images'] . '/*.{jpg,JPG,png,PNG}', GLOB_BRACE) ?: [],
    'thumbs' => glob(__DIR__ . '/' . $templateConfig['paths']['thumbs'] . '/*.{jpg,JPG,png,PNG}', GLOB_BRACE) ?: [],
];

usort($images['images'], fn ($a, $b) => filemtime($b) - filemtime($a));
usort($images['thumbs'], fn ($a, $b) => filemtime($b) - filemtime($a));

$totalImages = count($images['images']);

$requestUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlPrefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
if (substr($urlPrefix, -1) !== '/') {
    $urlPrefix .= '/';
}

// ZIP download handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'gallery') {
    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'zipped');
    $zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($images['images'] as $file) {
        $download_file = file_get_contents($file);
        if ($download_file === false) {
            continue;
        }
        $zip->addFromString(basename($file), $download_file);
    }
    $zip->close();

    header('Content-disposition: attachment; filename="' . $templateConfig['files']['download_prefix'] . '.zip"');
    header('Content-type: application/zip');
    header('Content-length: ' . filesize($tmp_file));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmp_file);
    ignore_user_abort(true);
    unlink($tmp_file);
    exit;
}

header('Cache-Control: max-age=' . $templateConfig['meta']['max-age']);

// Determine display content based on mode
if ($mode === 'single' && $requestedImage !== '') {
    $singleImageUrl = 'images/' . rawurlencode($requestedImage);
    $singleDownloadName = $templateConfig['files']['download_prefix'] . '_' . $requestedImage;
}

$styles = '';
$styles .= '<style>' . PHP_EOL;
$styles .= ':root {' . PHP_EOL;
foreach ($templateConfig['theme'] as $key => $value) {
    $value = trim($value);
    $styles .= '  ' . $key . ': ' . $value . ';' . PHP_EOL;
}
$styles .= '}' . PHP_EOL;
$styles .= '</style>' . PHP_EOL;

?>
<!DOCTYPE html>
<html lang="<?= $templateConfig['meta']['lang'] ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />

    <!--  Essential META Tags -->
    <meta property="og:title" content="<?= $templateConfig['meta']['title'] ?>">
    <meta property="og:type" content="article" />
    <?php if ($mode === 'single' && $requestedImage !== ''): ?>
        <meta property="og:image" content="<?= $urlPrefix . $singleImageUrl ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= htmlspecialchars($requestUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <!--  Non-Essential, But Recommended -->
    <meta property="og:site_name" content="<?= $templateConfig['meta']['sitename'] ?>">
    <title><?= $templateConfig['meta']['title'] ?></title>

    <?= $styles ?>
    <style>
        *, ::after, ::before {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
        }

        html {
            font-size: 18px;
            line-height: 1.4;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-weight: 600;
            background-image: linear-gradient(135deg, var(--primary-color) 30%, var(--secondary-color));
            color: var(--font-color);
            min-height: 100dvh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .front-cover {
            background-image: linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.85));
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            max-height: 30vh;
            height: 100vw;
            text-align: center;
            box-shadow: inset 0 -5px 20px 0 #000000;
            display: flex;
            align-content: center;
            justify-content: center;
            align-items: center;
            font-size: clamp(1rem, 8vw, 4rem);
            line-height: 1em;
            font-weight: 600;
            text-wrap: balance;
            color: #fff;
        }

        .container {
            display: flex;
            align-content: center;
            justify-content: center;
            margin: clamp(2rem, 4vw, 4rem) auto;
            padding: 0 2rem;
            max-width: 1000px;
        }

        .gallery-list {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            width: 100%;
        }

        .gallery-list-item {
            display: block;
            overflow: hidden;
            text-decoration: none;
            border-radius: .25rem;
            box-shadow: 0 10px 30px 5px rgba(0, 0, 0, 0.2);
        }

        .gallery-list-item figure {
            display: flex;
            justify-content: space-between;
            flex-direction: column;
            height: 100%;
            margin: 0;
        }

        .gallery-list-item img {
            border: none;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .big-button {
            padding: clamp(8px, 2vw, 16px) clamp(24px, 5vw, 40px);
            border-radius: clamp(40px, 5vw, 80px);
            border-color: transparent;
            color: var(--button-font-color);
            background: var(--primary-color);
            font-size: clamp(16px, 4vw, 32px);
            box-shadow: 0 0 10px 5px rgba(0, 0, 0, 0.35);
            cursor: pointer;
            transition: 0.5s ease-in;
        }

        .big-button:hover {
            background: color-mix(in srgb, var(--primary-color), var(--button-font-color) 20%);
            transition: 0.5s ease-out;
        }

        .lightbox {
            display: none;
            position: fixed;
            background-color: rgba(33, 33, 33, 0.90);
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 999;
        }

        .lightbox:target {
            display: block;
        }

        .lightbox-content {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: transparent;
        }

        .lightbox-content > img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .lightbox div:not(:last-of-type) {
            margin-bottom: 15px;
        }

        .lightbox-action-bar-outer {
            position: absolute;
            width: 100%;
            height: 10vh;
            background-color: rgba(33, 33, 33, 0.90);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .lightbox-action-bar {
            width: clamp(200px, 40vw, 1000px);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lightbox-action-bar > a {
            margin: 0 1rem;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            padding: 1rem 1.25rem;
            background: rgba(0,0,0,.35);
            border-radius: .5rem;
            line-height: 1;
            transition: background .2s;
            z-index: 1;
        }

        .lightbox-nav:hover {
            background: rgba(0,0,0,.6);
        }

        .lightbox-nav-prev { left: .75rem; }
        .lightbox-nav-next { right: .75rem; }

        /* Single image viewer */
        .viewer-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            min-height: 80vh;
            justify-content: center;
        }

        .viewer-container img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: .5rem;
            box-shadow: 0 10px 30px 5px rgba(0, 0, 0, 0.3);
        }

        .viewer-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .viewer-actions a {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.5rem;
            border-radius: 2rem;
            background: var(--primary-color);
            color: var(--button-font-color);
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,.25);
            transition: background .3s;
        }

        .viewer-actions a:hover {
            background: color-mix(in srgb, var(--primary-color), var(--button-font-color) 20%);
        }
    </style>
    <script>
        var pbTotal = <?= $totalImages ?>;
        document.addEventListener('keydown', function (e) {
            var hash = window.location.hash;
            var m = hash.match(/^#lightbox-uid-(\d+)$/);
            if (!m) return;
            var cur = parseInt(m[1], 10);
            if (e.key === 'ArrowLeft')  { window.location.hash = '#lightbox-uid-' + (cur > 0 ? cur - 1 : pbTotal - 1); }
            if (e.key === 'ArrowRight') { window.location.hash = '#lightbox-uid-' + (cur < pbTotal - 1 ? cur + 1 : 0); }
            if (e.key === 'Escape')     { window.location.hash = '#gallery-list-item-' + cur; }
        });

        function pbShare(imageUrl, fallbackUrl) {
            if (navigator.share && navigator.canShare) {
                fetch(imageUrl)
                    .then(function (r) { return r.blob(); })
                    .then(function (blob) {
                        var file = new File([blob], 'photo.jpg', { type: blob.type });
                        if (navigator.canShare({ files: [file] })) {
                            navigator.share({ files: [file] }).catch(function () {});
                            return;
                        }
                        window.open(fallbackUrl);
                    })
                    .catch(function () { window.open(fallbackUrl); });
            } else {
                window.open(fallbackUrl);
            }
        }
    </script>
</head>
<body>
    <header class="front-cover">
        <div class="container">
            <?= $templateConfig['meta']['title'] ?>
        </div>
    </header>

    <?php if ($mode === 'single' && $requestedImage !== ''): ?>
        <!-- Single Image Viewer: JS checks if image is accessible, retries on error -->
        <div class="viewer-container">
            <div id="pb-uploading" style="text-align:center;color:var(--font-color,#fff);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size:3rem;margin-bottom:1.5rem;display:block;opacity:.6;"></i>
                <p style="font-size:1.2rem;margin:0;"><?= $templateConfig['labels']['image_uploading'] ?? 'Your photo is being uploaded...' ?></p>
            </div>
            <img id="pb-photo" src="<?= $singleImageUrl ?>" alt="Photo" style="display:none" />
            <div id="pb-actions" class="viewer-actions" style="display:none">
                <a href="<?= $singleImageUrl ?>" download="<?= htmlspecialchars($singleDownloadName) ?>">
                    <i class="fa-solid fa-download"></i> <?= $templateConfig['labels']['download'] ?>
                </a>
                <a href="#" onclick="pbShare('<?= $singleImageUrl ?>', 'https://wa.me/?text=<?= urlencode(sprintf($templateConfig['labels']['share'], $requestUrl)) ?>'); return false;">
                    <i class="fa-solid fa-share-nodes"></i>
                </a>
            </div>
        </div>
        <script>
            (function () {
                var img = document.getElementById('pb-photo');
                var uploading = document.getElementById('pb-uploading');
                var actions = document.getElementById('pb-actions');
                img.onload = function () {
                    uploading.style.display = 'none';
                    img.style.display = 'block';
                    actions.style.display = 'flex';
                };
                img.onerror = function () {
                    setTimeout(function () { window.location.reload(); }, 3000);
                };
            }());
        </script>
    <?php elseif ($mode === 'gallery'): ?>
        <!-- Gallery View -->
        <div class="container">
            <div class="gallery-list">
                <?php foreach ($images['images'] as $key => $filename) { ?>
                    <?php
                        $filename = basename($filename);
                    $imageUrl = 'images/' . rawurlencode($filename);
                    $thumbnailUrl = $imageUrl;
                    $possibleThumbnail = __DIR__ . '/' . $templateConfig['paths']['thumbs'] . '/' . $filename;
                    if (file_exists($possibleThumbnail)) {
                        $thumbnailUrl = 'thumbs/' . rawurlencode($filename);
                    }
                    ?>
                    <a class="gallery-list-item" id="gallery-list-item-<?= $key ?>" href="#lightbox-uid-<?= $key ?>">
                        <figure>
                            <img src="<?= $thumbnailUrl ?>" alt="<?= htmlspecialchars(basename($filename)) ?>" loading="lazy" />
                        </figure>
                    </a>
                    <?php
                        $prevKey = $key > 0 ? $key - 1 : $totalImages - 1;
                    $nextKey = $key < $totalImages - 1 ? $key + 1 : 0;
                    ?>
                    <div class="lightbox" id="lightbox-uid-<?= $key ?>">
                        <div class="lightbox-content">
                            <div class="lightbox-action-bar-outer">
                                <div class="lightbox-action-bar">
                                    <a href="<?= $imageUrl ?>" download="<?= $templateConfig['files']['download_prefix'] ?>_<?= htmlspecialchars(basename($filename)) ?>">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <a href="#" onclick="pbShare('<?= $imageUrl ?>', 'https://wa.me/?text=<?= urlencode(sprintf($templateConfig['labels']['share'], $urlPrefix . '?img=' . rawurlencode($filename))) ?>'); return false;">
                                        <i class="fa-solid fa-share-nodes"></i>
                                    </a>
                                    <a href="#gallery-list-item-<?= $key ?>" title="<?= $templateConfig['labels']['close'] ?>">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                </div>
                            </div>
                            <a class="lightbox-nav lightbox-nav-prev" href="#lightbox-uid-<?= $prevKey ?>"><i class="fa-solid fa-chevron-left"></i></a>
                            <a class="lightbox-nav lightbox-nav-next" href="#lightbox-uid-<?= $nextKey ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars(basename($filename)) ?>" loading="lazy" />
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="container">
            <form target="_blank" action="" method="post" onsubmit="return confirm('<?= sprintf($templateConfig['labels']['download_confirmation_images'], $totalImages) ?>')">
                <button type="submit" class="big-button"><?= $templateConfig['labels']['download'] ?></button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
