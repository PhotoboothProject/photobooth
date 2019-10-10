<?php
header('Content-Type: application/json');

require_once('../lib/config.php');
require_once('../lib/db.php');

if (empty($_GET['filename']) || !preg_match('/^[a-z0-9_]+\.jpg$/', $_GET['filename'])) {
    die(json_encode([
        'error' => 'No or invalid file provided',
    ]));
}

$filename = $_GET['filename'];
$filename_source = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR . $filename;
$filename_print = $config['foldersAbs']['print'] . DIRECTORY_SEPARATOR . $filename;
$filename_codes = $config['foldersAbs']['qrcodes'] . DIRECTORY_SEPARATOR . $filename;
$filename_thumb = $config['foldersAbs']['thumbs'] . DIRECTORY_SEPARATOR . $filename;
$status = false;

// text on print variables
$fontsize = $config['fontsize'];
$fontlocx = $config['locationx'];
$fontlocy = $config['locationy'];
$linespacing = $config['linespace'];
$fontrot = $config['rotation'];
$line1text = $config['textonprint']['line1'];
$line2text = $config['textonprint']['line2'];
$line3text = $config['textonprint']['line3'];

// exit with error
if (!file_exists($filename_source)) {
    die(json_encode([
        'error' => "File $filename not found",
    ]));
}

if (!file_exists($filename_print)) {
    if ($config['print_qrcode']) {
        // create qr code
        if (!file_exists($filename_codes)) {
            include('../vendor/phpqrcode/qrlib.php');
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/api/download.php?image=';
            QRcode::png($url.$filename, $filename_codes, QR_ECLEVEL_H, 10);
        }

        // merge source and code
        list($width, $height) = getimagesize($filename_source);
        $newwidth = $width + ($height / 2);
        $newheight = $height;

        $source = imagecreatefromjpeg($filename_source);
        $code = imagecreatefrompng($filename_codes);

        if ($config['print_frame'] == true) {
            $print = imagecreatefromjpeg($filename_source);
            $rahmen = @imagecreatefrompng('../resources/img/frames/frame.png');
            $rahmen = ResizePngImage($rahmen, imagesx($print), imagesy($print));
            $x = (imagesx($print)/2) - (imagesx($rahmen)/2);
            $y = (imagesy($print)/2) - (imagesy($rahmen)/2);
            imagecopy($print, $rahmen, $x, $y, 0, 0, imagesx($rahmen), imagesy($rahmen));
            imagejpeg($print, $filename_print);
            imagedestroy($print);
            // $source needs to be redefined, picture with frame now exists inside $filename_print
            imagedestroy($source);
            $source = imagecreatefromjpeg($filename_print);
        }

        $print = imagecreatetruecolor($newwidth, $newheight);

        imagefill($print, 0, 0, imagecolorallocate($print, 255, 255, 255));
        imagecopy($print, $source, 0, 0, 0, 0, $width, $height);
        imagecopyresized($print, $code, $width, 0, 0, 0, ($height / 2), ($height / 2), imagesx($code), imagesy($code));

        // text on image - start  - IMPORTANT  ensure you download Google Great Vibes font
        if ($config['is_textonprint'] == true) {
            $fontcolour = imagecolorallocate($print, 0, 0, 0);  // colour of font
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line1text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line2text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + ($linespacing *2), $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line3text);
        }
        // text on image - end

        imagejpeg($print, $filename_print);
        imagedestroy($code);
        imagedestroy($source);
    } else {
        $print = imagecreatefromjpeg($filename_source);
        if ($config['print_frame'] == true) {
            $rahmen = @imagecreatefrompng('../resources/img/frames/frame.png');
            $rahmen = ResizePngImage($rahmen, imagesx($print), imagesy($print));
            $x = (imagesx($print)/2) - (imagesx($rahmen)/2);
            $y = (imagesy($print)/2) - (imagesy($rahmen)/2);
            imagecopy($print, $rahmen, $x, $y, 0, 0, imagesx($rahmen), imagesy($rahmen));
        }
        // text on image - start  - IMPORTANT  ensure you download Google Great Vibes font
        if ($config['is_textonprint'] == true) {
            $fontcolour = imagecolorallocate($print, 0, 0, 0);  // colour of font
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line1text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + $linespacing, $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line2text);
            imagettftext($print, $fontsize, $fontrot, $fontlocx, $fontlocy + ($linespacing *2), $fontcolour, '../resources/fonts/GreatVibes-Regular.ttf', $line3text);
        }
        //text on image - end
        imagejpeg($print, $filename_print);
    }
    imagedestroy($print);

    if ($config['crop_onprint']) {
        $crop_width = $config['crop_width'];
        $crop_height = $config['crop_height'];
        ResizeCropImage($crop_width, $crop_height, $filename_print, $filename_print);
    }
}

// print image
// fixme: move the command to the config.inc.php
$printimage = shell_exec(
    sprintf(
        $config['print']['cmd'],
        $filename_print
    )
);

die(json_encode([
    'status' => 'ok',
    'msg' => $printimage || '',
]));

function ResizePngImage($image, $max_width, $max_height)
{
    $old_width  = imagesx($image);
    $old_height = imagesy($image);
    $scale      = min($max_width/$old_width, $max_height/$old_height);
    $new_width  = ceil($scale*$old_width);
    $new_height = ceil($scale*$old_height);
    $new = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($new, false);
    imagesavealpha($new, true);
    imagecopyresized($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);

    return $new;
}

// Resize and crop image by center
function ResizeCropImage($max_width, $max_height, $source_file, $dst_dir, $quality = 80)
{
    $imgsize = getimagesize($source_file);
    $width = $imgsize[0];
    $height = $imgsize[1];
    $mime = $imgsize['mime'];

    switch ($mime) {
        case 'image/gif':
            $image_create = "imagecreatefromgif";
            $image = "imagegif";
            break;

        case 'image/png':
            $image_create = "imagecreatefrompng";
            $image = "imagepng";
            $quality = 7;
            break;

        case 'image/jpeg':
            $image_create = "imagecreatefromjpeg";
            $image = "imagejpeg";
            $quality = 80;
            break;

        default:
            return false;
            break;
    }

    $dst_img = imagecreatetruecolor($max_width, $max_height);
    $src_img = $image_create($source_file);

    $width_new = $height * $max_width / $max_height;
    $height_new = $width * $max_height / $max_width;
    //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
    if ($width_new > $width) {
        //cut point by height
        $h_point = (($height - $height_new) / 2);
        //copy image
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        //cut point by width
        $w_point = (($width - $width_new) / 2);
        imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
    }

    $image($dst_img, $dst_dir, $quality);

    if ($dst_img) {
        imagedestroy($dst_img);
    }
    if ($src_img) {
        imagedestroy($src_img);
    }
}
