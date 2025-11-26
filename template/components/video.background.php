<?php

use Photobooth\Service\LanguageService;

$languageService = LanguageService::getInstance();
$isVideoBackground = $config['background']['type'] === 'video';
$videoPath = $config['background']['video'];

if ($isVideoBackground) {
    echo '<div id="video-background">';
    if (str_starts_with($videoPath, 'https://www.youtube.com/embed/')) {
        echo '<iframe width="100%" height="100%" src="' . $videoPath . '?autoplay=1&mute=1&controls=0&modestbranding=1&rel=0" frameborder="0" allow="autoplay" allowfullscreen></iframe>';
    } else {
        echo '<video autoplay muted loop playsinline src="' . $videoPath . '"></video>';
    }
    echo '</div>';
}
