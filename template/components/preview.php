<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

$languageService = LanguageService::getInstance();
$previewFlipClass = $config['preview']['flip'];
$previewStyleClass = $config['preview']['style'];
$previewShowPictureFrame = $config['preview']['showFrame'] && !empty($config['picture']['frame']);
$previewShowCollageFrame = $config['preview']['showFrame'] && !empty($config['collage']['frame']);
$extendPreviewAsPicture = $config['preview']['showFrame'] && $config['picture']['extend_by_frame'] && $config['preview']['extend_by_frame'];
$l = $t = $r = $b = 0;
$x = !empty($config['preview']['videoWidth']) ? $config['preview']['videoWidth'] : 1280;
$y = !empty($config['preview']['videoHeight']) ? $config['preview']['videoHeight'] : 720;
$comp = $prev_ratio = $x / $y;
$prev_l = $prev_t = 0;
$prev_h = $prev_w = 100;

if ($extendPreviewAsPicture) {
    $l = $config['picture']['frame_left_percentage'] / 100;
    $t = $config['picture']['frame_top_percentage'] / 100;
    $r = $config['picture']['frame_right_percentage'] / 100;
    $b = $config['picture']['frame_bottom_percentage'] / 100;

    $hori_ratio = 1 - ($l + $r);
    $vert_ratio = 1 - ($t + $b);

    $prev_ratio = ($hori_ratio * $x) / ($vert_ratio * $y);

    $prev_h = $vert_ratio * 100;
    $prev_w = $hori_ratio * 100;

    $prev_l = 100 * ($previewFlipClass === 'flip-vertical' ? $r : $l);
    $prev_t = 100 * ($previewFlipClass === 'flip-horizontal' ? $b : $t);
}

$composed_style = '
	top:' . $prev_t . '%;
	left:' . $prev_l . '%;
	height:' . $prev_h . '%;
	width:' . $prev_w . '%;
	aspect-ratio:' . $prev_ratio;

echo '<div class="preview">';
echo '<div id="preview-container" style="aspect-ratio: ' . $comp . '">';
echo '<div id="preview-wrapper" style="aspect-ratio:' . $comp . '">';
echo '<video id="preview--video" style="' . $composed_style . '" class="' . $previewFlipClass . ' ' . $previewStyleClass . '" autoplay playsinline></video>';
echo '<div id="preview--ipcam" style="' . $composed_style . '" class="' . $previewFlipClass . ' ' . $previewStyleClass . '"></div>';
echo '<div id="preview--none">' . $languageService->translate('no_preview') . '</div>';
echo '</div></div>';

if ($previewShowPictureFrame) {
    echo '<img id="previewframe--picture" class="' . $previewFlipClass . '" src="' . PathUtility::getPublicPath($config['picture']['frame']) . '" alt="pictureFrame" />';
}
if ($previewShowCollageFrame) {
    echo '<img id="previewframe--collage" class="' . $previewFlipClass . '" src="' . PathUtility::getPublicPath($config['collage']['frame']) . '" alt="collageFrame" />';
}

echo '</div>';
