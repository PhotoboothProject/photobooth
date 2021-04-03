<?php

function resizeImage($image, $max_width, $max_height) {
    if (!$image) {
        return false;
    }

    $old_width = imagesx($image);
    $old_height = imagesy($image);

    $scale = min($max_width / $old_width, $max_height / $old_height);

    $new_width = ceil($scale * $old_width);
    $new_height = ceil($scale * $old_height);

    return imagescale($image, $new_width, $new_height, IMG_TRIANGLE);
}

function resizePngImage($image, $max_width, $max_height) {
    if (!$image) {
        return false;
    }

    $old_width = imagesx($image);
    $old_height = imagesy($image);
    $scale = min($max_width / $old_width, $max_height / $old_height);
    $new_width = ceil($scale * $old_width);
    $new_height = ceil($scale * $old_height);
    $new = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($new, false);
    imagesavealpha($new, true);
    imagecopyresized($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);

    return $new;
}

// Resize and crop image by center
function ResizeCropImage($max_width, $max_height, $source_file, $dst_dir, $quality = 100) {
    $imgsize = getimagesize($source_file);
    $width = $imgsize[0];
    $height = $imgsize[1];
    $mime = $imgsize['mime'];

    switch ($mime) {
        case 'image/gif':
            $image_create = 'imagecreatefromgif';
            $image = 'imagegif';
            break;

        case 'image/png':
            $image_create = 'imagecreatefrompng';
            $image = 'imagepng';
            $quality = 7;
            break;

        case 'image/jpeg':
            $image_create = 'imagecreatefromjpeg';
            $image = 'imagejpeg';
            $quality = 100;
            break;

        default:
            return false;
            break;
    }

    $dst_img = imagecreatetruecolor($max_width, $max_height);
    $src_img = $image_create($source_file);

    $width_new = ($height * $max_width) / $max_height;
    $height_new = ($width * $max_height) / $max_width;
    //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
    if ($width_new > $width) {
        //cut point by height
        $h_point = ($height - $height_new) / 2;
        //copy image
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        //cut point by width
        $w_point = ($width - $width_new) / 2;
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
