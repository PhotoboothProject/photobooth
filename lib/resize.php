<?php

function rotateResizeImage($image, $rotation, $bg_color = '#ffffff') {
    try {
        if (!$image) {
            throw new Exception('Invalid image resource');
        }

        // simple rotate if possible and ignore changed dimensions (doesn't need to care about background color)
        $simple_rotate = [-180, -90, 0, 180, 90, 360];
        if (in_array($rotation, $simple_rotate)) {
            $new = imagerotate($image, $rotation, 0);
            if (!$new) {
                throw new Exception('Cannot rotate image.');
            }
        } else {
            if (strlen($bg_color) === 7) {
                $bg_color .= '00';
            }
            list($bg_r, $bg_g, $bg_b, $bg_a) = sscanf($bg_color, '#%02x%02x%02x%02x');

            // get old dimensions
            $old_width = intval(imagesx($image));
            $old_height = intval(imagesy($image));

            // create new image with old dimensions
            $new = imagecreatetruecolor($old_width, $old_height);
            if (!$new) {
                throw new Exception('Cannot create new image.');
            }

            // color background as defined
            $background = imagecolorallocatealpha($new, $bg_r, $bg_g, $bg_b, $bg_a);
            if (!imagefill($new, 0, 0, $background)) {
                throw new Exception('Cannot fill image.');
            }

            // rotate the image
            $background = imagecolorallocatealpha($image, $bg_r, $bg_g, $bg_b, $bg_a);
            $image = imagerotate($image, $rotation, $background);
            if (!$image) {
                throw new Exception('Cannot rotate image.');
            }

            // make sure width and/or height fits into old dimensions
            $image = resizeImage($image, $old_width, $old_height);
            if (!$image) {
                throw new Exception('Cannot resize image.');
            }

            // get new dimensions after rotate and resize
            $new_width = intval(imagesx($image));
            $new_height = intval(imagesy($image));

            // center rotated image
            $x = intval(($old_width - $new_width) / 2);
            $y = intval(($old_height - $new_height) / 2);

            // copy rotated image to new image with old dimensions
            if (imagecopy($new, $image, $x, $y, 0, 0, $new_width, $new_height)) {
                throw new Exception('Cannot copy rotated image to new image.');
            }
        }
    } catch (Exception $e) {
        // Try to clear cache
        if (is_resource($new)) {
            imagedestroy($new);
        }
        // Return unmodified resource
        return $image;
    }

    return $new;
}

function resizeImage($image, $max_width, $max_height) {
    try {
        if (!$image) {
            throw new Exception('Invalid image resource.');
        }

        $old_width = imagesx($image);
        $old_height = imagesy($image);

        if ($old_width <= 0 || $old_height <= 0 || $max_width <= 0 || $max_height <= 0) {
            throw new Exception('Invalid image dimensions or maximum dimensions.');
        }

        $scale = min($max_width / $old_width, $max_height / $old_height);

        $new_width = ceil($scale * $old_width);
        $new_height = ceil($scale * $old_height);

        $new_image = imagescale($image, $new_width, $new_height, IMG_TRIANGLE);
        if (!$new_image) {
            throw new Exception('Cannot resize image.');
        }
    } catch (Exception $e) {
        // Return unmodified resource
        return $image;
    }

    return $new_image;
}

function resizePngImage($image, $new_width, $new_height) {
    try {
        if (!$image) {
            throw new Exception('Invalid image resource.');
        }

        $old_width = imagesx($image);
        $old_height = imagesy($image);
        if ($old_width <= 0 || $old_height <= 0 || $new_width <= 0 || $new_height <= 0) {
            throw new Exception('Invalid image dimensions or maximum dimensions.');
        }
        $new = imagecreatetruecolor($new_width, $new_height);
        if (!$new) {
            throw new Exception('Cannot create new image.');
        }

        imagealphablending($new, false);
        imagesavealpha($new, true);
        if (!imagecopyresampled($new, $image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
            throw new Exception('Cannot resize image.');
        }
    } catch (Exception $e) {
        // Try to clear cache
        if (is_resource($new)) {
            imagedestroy($new);
        }
        // Return unmodified resource
        return $image;
    }
    return $new;
}

// Resize and crop image by center
function resizeCropImage($max_width, $max_height, $source_file, $quality = 100) {
    try {
        $old_width = intval(imagesx($source_file));
        $old_height = intval(imagesy($source_file));
        if ($old_width <= 0 || $old_height <= 0 || $max_width <= 0 || $max_height <= 0) {
            throw new Exception('Invalid image dimensions or maximum dimensions.');
        }
        $new_width = intval(($old_height * $max_width) / $max_height);
        $new_height = intval(($old_width * $max_height) / $max_width);
        settype($max_width, 'integer');
        settype($max_height, 'integer');
        $new = imagecreatetruecolor(intval($max_width), intval($max_height));
        if (!$new) {
            throw new Exception('Cannot create new image.');
        }

        //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
        if ($new_width > $old_width) {
            //cut point by height
            $h_point = intval(($old_height - $new_height) / 2);
            //copy image
            if (!imagecopyresampled($new, $source_file, 0, 0, 0, $h_point, $max_width, $max_height, $old_width, $new_height)) {
                throw new Exception('Cannot resize and crop image by height.');
            }
        } else {
            //cut point by width
            $w_point = intval(($old_width - $new_width) / 2);
            if (!imagecopyresampled($new, $source_file, 0, 0, $w_point, 0, $max_width, $max_height, $new_width, $old_height)) {
                throw new Exception('Cannot resize and crop image by width.');
            }
        }
    } catch (Exception $e) {
        // Try to clear cache
        if (is_resource($new)) {
            imagedestroy($new);
        }
        // Return unmodified resource
        return $source_file;
    }
    return $new;
}
