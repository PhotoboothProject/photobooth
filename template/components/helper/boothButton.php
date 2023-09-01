<?php
function getBoothButton($label, $icon, $command, $type = 'md') {
    global $config;
    $btnClass = 'btn btn--' . $config['ui']['button'];
    $btnClass .= ' ' . $command;

    if (($command != 'deletebtn' && $label != 'cups-button') || ($command == 'deletebtn' && $config['delete']['no_request'])) {
        $btnClass .= ' rotaryfocus';
    }

    if ($config['ui']['style'] == 'evolution' && $command == 'homebtn') {
        $btnClass .= ' !absolute !top-4 !left-4';
    }

    if ($config['ui']['style'] == 'evolution' && $type == 'gallery') {
        $btnClass .=
            ' !w-full !h-full absolute rounded-none border-opacity-30 border-dashed m-0 !mb-2 p-0 flex items-center justify-center flex-col !bg-transparent hover:text-white';
    }

    if (($config['ui']['style'] == 'classic' || $config['ui']['style'] == 'classic_rounded') && $type == 'xs') {
        $btnClass .= ' btn--small';
    }

    if ($label == 'cups-button') {
        $btnClass .= '" target="newwin';
    }

    if ($command == 'reload') {
        $btnClass .= '" onclick="window.location.reload();';
    }

    return '<a href="#" class="' .
        $btnClass .
        '">
            <i class="' .
        $icon .
        ' mb-2"></i>
            <span class="text-sm whitespace-nowrap" data-i18n="' .
        $label .
        '">
                ' .
        $label .
        '
            </span>
        </a>';
} ?>

