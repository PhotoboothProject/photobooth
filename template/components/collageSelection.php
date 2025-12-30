<?php

use Photobooth\Enum\CollageLayoutEnum;
use Photobooth\Service\LanguageService;
use Photobooth\Collage;

function renderCollageOptionsFromEnumWithLimit(array $collageConfig): string
{
    $languageService = LanguageService::getInstance();

    $html = '<div id="collageSelector">';
    $html .= '<div class="modal hidden" id="collageSelectorModal" aria-hidden="true" role="dialog" aria-labelledby="collageSelectorTitle">';
    $html .= '<div class="modal-inner">';
    $html .= '<div class="modal-body">';
    $html .= '<h3 id="collageSelectorTitle">' . $languageService->translate('selectCollageLayout') . '</h3>';
    $html .= '<div class="collageSelector__options">';

    foreach (CollageLayoutEnum::cases() as $layout) {
        if (in_array($layout, $collageConfig['layouts_enabled'])) {
            $collageConfig['layout'] = $layout->value;
            $limitData = Collage::calculateLimit($collageConfig);
            $limit = $limitData['limit'];

            $html .= sprintf(
                '<button type="button" class="collageSelector__option cursor-pointer" data-layout="%s" data-limit="%d">' .
                '%s' .
                '<span class="collageSelector__limit">' .
                $languageService->translate('pictures') . ': %d' .
                '</span>' .
                '</button>',
                $layout->value,
                $limit,
                $layout->label(),
                $limit
            );
        }
    }

    $html .= '</div>'; // options
    $html .= '</div>'; // body
    $html .= '<div class="modal-buttonbar">';
    $html .= '<button type="button" class="modal-button" id="collageSelectorClose">' . htmlspecialchars($languageService->translate('close'), ENT_QUOTES, 'UTF-8') . '</button>';
    $html .= '</div></div></div>';
    $html .= '</div>';

    return $html;
}

echo renderCollageOptionsFromEnumWithLimit($config['collage']);
