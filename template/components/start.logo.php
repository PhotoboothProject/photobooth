<?php

use Photobooth\Utility\PathUtility;

if ($config['logo']['enabled']) {
    echo '
        <div class="logo logo--' . ($config['logo']['position'] ?? 'center') . '">
            <img src="' . PathUtility::getPublicPath($config['logo']['path']) . '">
        </div>
    ';
}
