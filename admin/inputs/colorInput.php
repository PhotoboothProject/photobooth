<?php

use Photobooth\Service\LanguageService;

function getColorInput($setting, $i18ntag)
{
    $languageService = LanguageService::getInstance();

    return '
        <label class="mb-3">' . $languageService->translate($i18ntag) . '</label>
        <input
            class="w-full h-10 border-2 border-gray-300 border-solid rounded-lg overflow-hidden p-1 mt-auto"
            type="color"
            name="' . $setting['name'] . '"
            value="' . $setting['value'] . '"
            placeholder="' . $setting['placeholder'] . '"
        />
    ';
}
