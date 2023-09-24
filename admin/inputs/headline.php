<?php

use Photobooth\Service\LanguageService;

function getHeadline($i18ntag)
{
    $languageService = LanguageService::getInstance();

    $tooltipClass = '
        absolute z-10 hidden flex-col px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm
        mt-3
        peer-hover:flex
    ';

    return '
        <div class="tooltip mb-3 relative">
            <label class="peer text-black text-md font-bold">' . $languageService->translate($i18ntag) . '</label>
            <span class="' . $tooltipClass . '">
                <div class="absolute left-5 -top-[10px] h-0 w-0 border-x-8 border-x-transparent border-b-[10px] border-gray-900"></div>
                ' . $languageService->translate('manual:' . $i18ntag) . '
            </span>
        </div>
    ';
}
