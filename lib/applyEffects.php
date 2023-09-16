<?php

function getCollageFiles($collage, $filename_tmp, $file, array $srcImages)
{
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
