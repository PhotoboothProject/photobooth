<?php

if (file_exists('config.inc.php')) {
    $templateConfig = require 'config.inc.php';
}

if (!isset($templateConfig)) {
    die('config.inc.php missing');
}

$images = [
    'images' => glob($templateConfig['paths']['images'] . '/*.{jpg,JPG}', GLOB_BRACE) ?: [],
    'thumbs' => glob($templateConfig['paths']['thumbs'] . '/*.{jpg,JPG}', GLOB_BRACE) ?: [],
];
$firstImage = $images['images'][0] ?? null;
$totalImages = count($images['images']);

$requestUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlPrefix = $requestUrl;
if (substr($urlPrefix, -4) === '.php') {
    $baseName = basename($urlPrefix);
    $urlPrefix = rtrim($urlPrefix, $baseName);
}
if (substr($urlPrefix, -1) !== '/') {
    $urlPrefix .= '/';
}

$ogImage = $urlPrefix . $firstImage;

header('Cache-Control: max-age=' . $templateConfig['meta']['max-age']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    zipFilesAndDownload($images['images'], $templateConfig);
}

function zipFilesAndDownload($files, $templateConfig)
{
    // create new zip opbject
    $zip = new ZipArchive();

    // create a temp file & open it
    $tmp_file = tempnam('.', 'zipped');
    $zip->open($tmp_file, ZipArchive::CREATE);

    // loop through each file
    foreach ($files as $file) {
        if (str_contains($file, 'tmb_')) {
            continue;
        }

        // download file
        $download_file = file_get_contents($file);
        //add it to the zip
        $zip->addFromString(basename($file), $download_file);
    }
    // close zip
    $zip->close();

    // send the file to the browser as a download
    header('Content-disposition: attachment; filename="' . $templateConfig['files']['download_prefix'] . '.zip"');
    header('Content-type: application/zip');
    header('Content-length: ' . filesize($tmp_file));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmp_file);
    ignore_user_abort(true);
    unlink($tmp_file);
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
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:url" content="<?= $requestUrl ?>">
    <meta name="twitter:card" content="summary_large_image">

    <!--  Non-Essential, But Recommended -->
    <meta property="og:site_name" content="<?= $templateConfig['meta']['sitename'] ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">
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
            color: var(--primary-color);
            min-height: 100dvh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .front-cover {
            background-image: linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.85)), url(<?= $urlPrefix . $firstImage ?>);
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
    </style>
</head>
<body>
    <header class="front-cover">
        <div class="container">
            <?= $templateConfig['meta']['title'] ?>
        </div>
    </header>
    <div class="container">
        <div class="gallery-list">
            <?php foreach ($images['thumbs'] as $key => $filename) { ?>
                <?php $fullImage = $urlPrefix . $images['images'][$key]; ?>
                <a class="gallery-list-item" id="gallery-list-item-<?= $key ?>" href="#lightbox-uid-<?= $key ?>">
                    <figure>
                        <img src="<?= $urlPrefix . $filename ?>" alt="<?= basename($filename) ?>"/>
                    </figure>
                </a>
                <div class="lightbox" id="lightbox-uid-<?= $key ?>">
                    <div class="lightbox-content">
                        <div class="lightbox-action-bar-outer">
                            <div class="lightbox-action-bar">
                                <a href="<?= $fullImage ?>" download="<?= $templateConfig['files']['download_prefix'] ?>_<?= basename($filename) ?>">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <a href="whatsapp://send?text=<?= urlencode(sprintf($templateConfig['labels']['share'], $fullImage))?>">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="#gallery-list-item-<?= $key ?>" title="<?= $templateConfig['labels']['close'] ?>">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        </div>
                        <img src="<?= $fullImage ?>" loading="lazy" alt="<?= $filename ?>" />
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
</body>
</html>
