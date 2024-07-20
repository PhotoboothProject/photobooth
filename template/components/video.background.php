<?php

use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();
$isVideoBackground = $config['background']['type'] === 'video';
$videoPath = $config['background']['video'];

if ($isVideoBackground) {
    echo '<div id="video-background">';
    echo '<video autoplay muted loop playsinline src="' . $videoPath . '"></video>';
    echo '</div>';
}
