<?php

function getCollageFiles($collage, $filename_tmp, $file, array $srcImages) {
    $collageBasename = substr($filename_tmp, 0, -4);
    $singleImageBase = substr($file, 0, -4);

    $collageSrcImagePaths = [];

    for ($i = 0; $i < $collage['limit']; $i++) {
        $collageSrcImagePaths[] = $collageBasename . '-' . $i . '.jpg';
        if ($collage['keep_single_images']) {
            $srcImages[] = $singleImageBase . '-' . $i . '.jpg';
        }
    }
    return [$collageSrcImagePaths, $srcImages];
}

function editSingleImage($config, $imageResource, $image_filter, $editSingleCollage, $picture_frame, $isCollage) {
    $imageModified = false;
    // Only jpg/jpeg are supported
    if (!$imageResource) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': Could not read jpeg file. Are you taking raws?';
        logErrorAndDie($errormsg);
    }

    if ($config['picture']['flip'] !== 'off') {
        if ($config['picture']['flip'] === 'horizontal') {
            imageflip($imageResource, IMG_FLIP_HORIZONTAL);
        } elseif ($config['picture']['flip'] === 'vertical') {
            imageflip($imageResource, IMG_FLIP_VERTICAL);
        } elseif ($config['picture']['flip'] === 'both') {
            imageflip($imageResource, IMG_FLIP_BOTH);
        }
        $imageModified = true;
    }

    // apply filter
    if ($image_filter) {
        applyFilter($image_filter, $imageResource);
        $imageModified = true;
    }

    if ($config['picture']['polaroid_effect'] && !$isCollage) {
        $polaroid_rotation = $config['picture']['polaroid_rotation'];
        $imageResource = effectPolaroid($imageResource, $polaroid_rotation, 200, 200, 200);
        $imageModified = true;
    }

    if (
        ($config['picture']['take_frame'] && !$isCollage && testFile($config['picture']['frame'])) ||
        ($editSingleCollage && $config['collage']['take_frame'] === 'always' && testFile($config['collage']['frame']))
    ) {
        $imageResource = applyFrame($imageResource, $picture_frame);
        $imageModified = true;
    }

    if ($config['picture']['rotation'] !== '0') {
        $imageResource = rotateResizeImage($imageResource, $config['picture']['rotation']);
        $imageModified = true;
    }
    return [$imageResource, $imageModified];
}

function addTextToImage($configText, $imageResource, $imageModified, $isCollage) {
    $fontpath = $configText['font'];
    if ($configText['enabled'] && testFile($fontpath) && !$isCollage) {
        $fontcolor = $configText['font_color'];
        $fontsize = $configText['font_size'];
        $fontlocx = $configText['locationx'];
        $fontlocy = $configText['locationy'];
        $linespacing = $configText['linespace'];
        $fontrot = $configText['rotation'];
        $line1text = $configText['line1'];
        $line2text = $configText['line2'];
        $line3text = $configText['line3'];
        $imageResource = applyText($imageResource, $fontsize, $fontrot, $fontlocx, $fontlocy, $fontcolor, $fontpath, $line1text, $line2text, $line3text, $linespacing);
        $imageModified = true;
    }
    return [$imageResource, $imageModified];
}

function compressImage($config, $imageModified, $imageResource, $filename_tmp, $filename_photo) {
    if ($imageModified || ($config['jpeg_quality']['image'] >= 0 && $config['jpeg_quality']['image'] < 100)) {
        imagejpeg($imageResource, $filename_photo, $config['jpeg_quality']['image']);
        // preserve jpeg meta data
        if ($config['picture']['preserve_exif_data'] && $config['exiftool']['cmd']) {
            $cmd = sprintf($config['exiftool']['cmd'], $filename_tmp, $filename_photo);
            $cmd .= ' 2>&1'; //Redirect stderr to stdout, otherwise error messages get lost.

            exec($cmd, $output, $returnValue);

            if ($returnValue) {
                $ErrorData = [
                    'error' => 'exiftool returned with an error code',
                    'cmd' => $cmd,
                    'returnValue' => $returnValue,
                    'output' => $output,
                ];
                $ErrorString = json_encode($ErrorData);
                logError($ErrorData);
                die($ErrorString);
            }
        }
    } else {
        copy($filename_tmp, $filename_photo);
    }
}
