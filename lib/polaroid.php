<?php

/**
 * Function to apply the polaroid effect to an image.
 *
 * @param string $resource Image resource
 * @param float $rotation Image rotation angle
 * @param int $rbcc red background color component
 * @param int $gbcc green background color component
 * @param int $bbcc blue background color component
 * @return resource image with the polaroid effect applied
 */
function effectPolaroid($resource, $rotation, $rbcc, $gbcc, $bbcc) {
    try {
        // We create a new image
        $img = imagecreatetruecolor(imagesx($resource) + 25, imagesy($resource) + 80);
        $white = imagecolorallocate($img, 255, 255, 255);

        // We fill in the new white image
        imagefill($img, 0, 0, $white);

        // We copy the image to which we want to apply the polariod effect in our new image.
        imagecopy($img, $resource, 11, 11, 0, 0, imagesx($resource), imagesy($resource));

        // Clear cach
        imagedestroy($resource);

        // Border color
        $color = imagecolorallocate($img, 192, 192, 192);
        // We put a gray border to our image.
        imagerectangle($img, 0, 0, imagesx($img) - 4, imagesy($img) - 4, $color);

        // Shade Colors
        $gris1 = imagecolorallocate($img, 208, 208, 208);
        $gris2 = imagecolorallocate($img, 224, 224, 224);
        $gris3 = imagecolorallocate($img, 240, 240, 240);

        // We add a small shadow
        imageline($img, 2, imagesy($img) - 3, imagesx($img) - 1, imagesy($img) - 3, $gris1);
        imageline($img, 4, imagesy($img) - 2, imagesx($img) - 1, imagesy($img) - 2, $gris2);
        imageline($img, 6, imagesy($img) - 1, imagesx($img) - 1, imagesy($img) - 1, $gris3);
        imageline($img, imagesx($img) - 3, 2, imagesx($img) - 3, imagesy($img) - 4, $gris1);
        imageline($img, imagesx($img) - 2, 4, imagesx($img) - 2, imagesy($img) - 4, $gris2);
        imageline($img, imagesx($img) - 1, 6, imagesx($img) - 1, imagesy($img) - 4, $gris3);

        // We rotate the image
        $background = imagecolorallocate($img, $rbcc, $gbcc, $bbcc);
        $rotatedImg = imagerotate($img, $rotation, $background);
    } catch (Exception $e) {
        // Clear cache
        imagedestroy($img);
        // Return unmodified resource
        return $resource;
    }
    // We destroy the image we have been working with
    imagedestroy($img);

    // We return the rotated image
    return $rotatedImg;
}
