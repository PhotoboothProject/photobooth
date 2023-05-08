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

function addExifData($command, $filename_tmp, $filename_photo) {
    $cmd = sprintf($command, $filename_tmp, $filename_photo);
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
