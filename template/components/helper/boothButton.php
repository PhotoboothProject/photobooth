<?php

use Photobooth\Service\LanguageService;

function getBoothButton($label, $icon, $command, $type = 'md')
{
    $languageService = LanguageService::getInstance();

    global $config;
    $btnClass = 'btn btn--' . $config['ui']['button'];
    $btnClass .= ' ' . $command;

    if (($command !== 'deletebtn' && $command !== 'cups-button') || ($command === 'deletebtn' && $config['delete']['no_request'])) {
        $btnClass .= ' rotaryfocus';
    }

    if (($config['ui']['style'] == 'classic' || $config['ui']['style'] == 'classic_rounded') && $type == 'xs') {
        $btnClass .= ' btn--small';
    }

    return '
        <a href="#" class="' . $btnClass . '" data-command="' . $command . '">
            <i class="' . $icon . '"></i>
            <span class="text-sm whitespace-nowrap">
            ' . $languageService->translate($label) . '
            </span>
        </a>';
}
