<?php

if ($config['logo']['enabled']) {
    echo '
        <div class="logo logo--' . ($config['logo']['position'] ?? 'center') . '">
            <img src="' . $config['logo']['path'] . '">
        </div>
    ';
}
