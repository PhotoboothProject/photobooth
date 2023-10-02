<?php
$full_images = glob('./{,*/,*/*/,*/*/*/}*.{jpg,JPG}', GLOB_BRACE);
$tmb_images = glob('./{,*/,*/*/,*/*/*/}tmb_*.{jpg,JPG}', GLOB_BRACE);
$first_img = $full_images[0];
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// meta params to evaluate
$og_locale = 'en_GB';
$og_description = 'Book the photobooth';
$og_sitename = 'Website';
$og_img_alt = 'Photobooth';
$whatsapp_msg = "Look at this Photobooth photo! \n\n %s \n\n\n\n Book the photobooth at 0123456789";
$seconds_to_cache = 60;

header("Cache-Control: max-age=$seconds_to_cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <link rel="canonical" href="<?=$actual_link?>">
    <!--  Essential META Tags -->
    <meta property="og:locale" content="<?=$og_locale?>">
    <meta property="og:title" content="{title}">
    <meta property="og:type" content="article" />
    <meta property="og:image" content="<?=$first_img?>">
    <meta property="og:image:secure_url" content="<?=$first_img?>">
    <meta property="og:image:width" content="500">
    <meta property="og:image:height" content="500">
    <meta property="og:url" content="<?=$actual_link?>">
    <meta name="twitter:card" content="summary_large_image">

    <!--  Non-Essential, But Recommended -->
    <meta property="og:description" content="<?=$og_description?>">
    <meta property="og:site_name" content="<?=$og_sitename?>">
    <meta name="twitter:image:alt" content="<?=$og_img_alt?>">
    <meta name="twitter:image" content="<?=$first_img?>">
    <title>{title}</title>
    <style>
        .modal-window {
            position: fixed;
            background-color: rgba(33, 33, 33, 0.90);
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 999;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s;
        }
        .modal-window:target {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }
        .modal-window > div {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: transparent;
        }
        .modal-window > div > img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .action-bar-outer {
            position: absolute;
            width: 100%;
            height: 10vh;
            background-color: rgba(33, 33, 33, 0.90);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .action-bar {
            width: clamp(200px, 40vw, 1000px);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .action-bar > a {
            margin: 0 1rem;
        }

        /* Demo Styles */
        html,
        body {
            margin: 0;
        }

        html {
            font-size: 18px;
            line-height: 1.4;
        }

        body {
            font-family: apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            font-weight: 600;
            background-image: linear-gradient(to right, #7f53ac 0, #657ced 100%);
            color: black;
        }

        .front-cover {
            background-image: linear-gradient(black, white), url(<?=$first_img?>);
            background-position: center;
            background-repeat: no-repeat;
            background-blend-mode: screen;
            background-size: cover;
            max-height: 30vh;
            height: 100vw;
            text-align: center;
            box-shadow: inset 0 -5px 20px 0 #000000;
            display: flex;
            align-content: center;
            justify-content: center;
            align-items: center;
            font-size: clamp(5vw, 70px, 10vw);
            text-transform: uppercase;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .img-container {
            display: grid;
            justify-content: center;
            align-items: center;
        }

        .img-container .interior {
            text-align: center;
        }

        .modal-window div:not(:last-of-type) {
            margin-bottom: 15px;
        }

        .btn {
            text-decoration: none;
        }

        .btn img {
            width: 100%;
            box-shadow: 0 0 10px 1px #000000;
            aspect-ratio: 1;
            object-fit: cover;
        }

        .container {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            align-content: center;
            align-items: end;
            justify-content: center;
            max-width: 1000px;
            margin: 0 auto;
            padding: 1rem 1rem 0 1rem;
        }
    </style>
</head>
<body>
<header class="front-cover">{title}</header>

<div class="container">
    <?php $index = 0; ?>
    <?php foreach ($tmb_images as $filename) { ?>
        <?php
        $index += 1;
        $this_full = str_replace('tmb_', '', $filename);
        $path_array = explode('/', $this_full);
        $download_name = end($path_array);
        ?>
        <div class="img-container">
            <div class="interior">
                <a id="opener<?=$index?>" class="btn" href="#open-modal<?=$index?>"><img src="<?=$filename?>"  alt="<?=$filename?>"/></a>
            </div>
        </div>
        <div id="open-modal<?=$index?>" class="modal-window">
            <div class="modal-content">
                <div class="action-bar-outer">
                    <div class="action-bar">
                        <a href='<?=$this_full?>' class="image-element" download='<?=$og_img_alt?>_<?=$download_name?>'><i class="fa-solid fa-download"></i></a>
                        <a href="whatsapp://send?text=<?=urldecode(sprintf($whatsapp_msg, $actual_link . substr($this_full, 2)))?>"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="#opener<?=$index?>" title="Close"><i class="fa-solid fa-xmark"></i></a>
                    </div>
                </div>
                <img src="<?=$this_full?>" loading="lazy" alt="<?=$filename?>" />
            </div>
        </div>
    <?php } ?>
</div>
</body>
</html>
