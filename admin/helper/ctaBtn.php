<?php

use Photobooth\Service\LanguageService;

function getCtaBtn($label, $btnId = '', $config = false)
{
    $languageService = LanguageService::getInstance();

    $labels = '';
    if ($config) {
        $labels = '
            <span class="hidden success"><i class="' . $config['icons']['admin_save_success'] . '"></i> ' . $languageService->translate('success') . '</span>
            <span class="hidden error"><i class="' . $config['icons']['admin_save_error'] . '"></i> ' . $languageService->translate('saveerror') . '</span>
        ';
    }

    return '
        <button class="save-admin-btn w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-content-1 hover:text-brand-1 transition font-bold" id="' . $btnId . '">
            <span class="save">
                ' . $languageService->translate($label) . '
            </span>
            ' . $labels . '
        </button>
    ';
}
