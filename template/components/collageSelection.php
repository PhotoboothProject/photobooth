<?php

use Photobooth\Enum\CollageLayoutEnum;
use Photobooth\Service\LanguageService;

function renderCollageOptionsFromEnumWithLimit(array $collageConfig): string
{
    $languageService = LanguageService::getInstance();

    $html = '<div id="collageDiv">';
    $html .= '<p>' . $languageService->translate('selectCollageLayout') . '</p>';
    $html .= '<select id="collageSelect">';

    foreach (CollageLayoutEnum::cases() as $layout) {
        $limit = $layout->getLimitByValue($layout->value);

        $selected = ($layout->value === $collageConfig['layout']) ? ' selected' : '';

        $html .= sprintf(
            '<option value="%s" data-limit="%d"%s>%s</option>',
            htmlspecialchars($layout->value, ENT_QUOTES, 'UTF-8'),
            $limit,
            $selected,
            htmlspecialchars($layout->label(), ENT_QUOTES, 'UTF-8')
        );
    }

    $html .= '</select>';
    $html .= '</div>';

    return $html;
}

echo renderCollageOptionsFromEnumWithLimit($config['collage']);
