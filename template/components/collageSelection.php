<?php

/**
 * @var array{collage: array} $config
 */

use Photobooth\Enum\CollageLayoutEnum;
use Photobooth\Service\LanguageService;
use Photobooth\Collage;

/**
 * Generate SVG preview for collage layout
 */
function getLayoutPreviewSvg(CollageLayoutEnum $layout): string
{
    $svg = '<svg class="collageSelector__preview" viewBox="0 0 180 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';

    // All layouts use 120x180 viewBox (2:3 aspect ratio)
    switch ($layout) {
        case CollageLayoutEnum::TWO_PLUS_TWO_1:
            // 2+2 Layout Option 1: 4 equal squares, no padding
            $positions = [
                ['x' => 0, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 90, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 2],
                ['x' => 0, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 3],
                ['x' => 90, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::TWO_PLUS_TWO_2:
            // 2+2 Layout Option 2: 4 equal squares with padding
            $positions = [
                ['x' => 14, 'y' => 10, 'w' => 72, 'h' => 48, 'num' => 1],
                ['x' => 94, 'y' => 10, 'w' => 72, 'h' => 48, 'num' => 2],
                ['x' => 14, 'y' => 62, 'w' => 72, 'h' => 48, 'num' => 3],
                ['x' => 94, 'y' => 62, 'w' => 72, 'h' => 48, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::ONE_PLUS_THREE_1:
            // 1+3 Layout Option 1: 1 large top-right, 3 small on bottom
            $positions = [
                ['x' => 85, 'y' => 10, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 5, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 2],
                ['x' => 63, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 3],
                ['x' => 120, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::ONE_PLUS_THREE_2:
            // 1+3 Layout Option 2: 1 large top-left, 3 small on bottom
            $positions = [
                ['x' => 5, 'y' => 10, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 5, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 2],
                ['x' => 63, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 3],
                ['x' => 120, 'y' => 74, 'w' => 55, 'h' => 37, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::THREE_PLUS_ONE_1:
            // 3+1 Layout: 3 small on top, 1 large bottom-left
            $positions = [
                ['x' => 5, 'y' => 51, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 5, 'y' => 10, 'w' => 55, 'h' => 37, 'num' => 2],
                ['x' => 63, 'y' => 10, 'w' => 55, 'h' => 37, 'num' => 3],
                ['x' => 120, 'y' => 10, 'w' => 55, 'h' => 37, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::ONE_PLUS_TWO_1:
            // 1+2 Layout: 1 large left (55.5% square), 2 small right stacked
            $positions = [
                ['x' => 0, 'y' => 7, 'w' => 100, 'h' => 67, 'num' => 1],
                ['x' => 100, 'y' => 7, 'w' => 73, 'h' => 49, 'num' => 2],
                ['x' => 100, 'y' => 64, 'w' => 73, 'h' => 49, 'num' => 3],
            ];
            break;

        case CollageLayoutEnum::TWO_PLUS_ONE_1:
            // 2+1 Layout: 2 on top, 1 bottom left
            $positions = [
                ['x' => 18, 'y' => 12, 'w' => 68, 'h' => 45, 'num' => 1],
                ['x' => 95, 'y' => 12, 'w' => 68, 'h' => 45, 'num' => 2],
                ['x' => 18, 'y' => 63, 'w' => 68, 'h' => 45, 'num' => 3],
            ];
            break;

        case CollageLayoutEnum::TWO_X_FOUR_1:
        case CollageLayoutEnum::TWO_X_FOUR_2:
        case CollageLayoutEnum::TWO_X_FOUR_3:
        case CollageLayoutEnum::TWO_X_FOUR_4:
            // 2x4 Layout: 4 photos duplicated (printed as strip, cut in middle)
            $positions = [
                // Left half
                ['x' => 6, 'y' => 8, 'w' => 82, 'h' => 23, 'num' => 1],
                ['x' => 6, 'y' => 34, 'w' => 82, 'h' => 23, 'num' => 2],
                ['x' => 6, 'y' => 60, 'w' => 82, 'h' => 23, 'num' => 3],
                ['x' => 6, 'y' => 86, 'w' => 82, 'h' => 23, 'num' => 4],
                // Right half (duplicates)
                ['x' => 92, 'y' => 8, 'w' => 82, 'h' => 23, 'num' => 1],
                ['x' => 92, 'y' => 34, 'w' => 82, 'h' => 23, 'num' => 2],
                ['x' => 92, 'y' => 60, 'w' => 82, 'h' => 23, 'num' => 3],
                ['x' => 92, 'y' => 86, 'w' => 82, 'h' => 23, 'num' => 4],
            ];
            break;

        case CollageLayoutEnum::TWO_X_THREE_1:
        case CollageLayoutEnum::TWO_X_THREE_2:
            // 2x3 Layout: 3 photos duplicated (printed as strip, cut in middle)
            $positions = [
                // Left half
                ['x' => 6, 'y' => 10, 'w' => 82, 'h' => 30, 'num' => 1],
                ['x' => 6, 'y' => 45, 'w' => 82, 'h' => 30, 'num' => 2],
                ['x' => 6, 'y' => 80, 'w' => 82, 'h' => 30, 'num' => 3],
                // Right half (duplicates)
                ['x' => 92, 'y' => 10, 'w' => 82, 'h' => 30, 'num' => 1],
                ['x' => 92, 'y' => 45, 'w' => 82, 'h' => 30, 'num' => 2],
                ['x' => 92, 'y' => 80, 'w' => 82, 'h' => 30, 'num' => 3],
            ];
            break;
        default:
            // Fallback for other layouts
            $positions = [
                ['x' => 0, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 90, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 2],
                ['x' => 0, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 3],
                ['x' => 90, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 4],
            ];
            break;
    }

    // Draw each position
    foreach ($positions as $pos) {
        // Rectangle with border
        $svg .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="#4A90E2" stroke="#FFFFFF" stroke-width="2" rx="2"/>',
            $pos['x'] + 2,
            $pos['y'] + 2,
            $pos['w'] - 4,
            $pos['h'] - 4
        );

        // Number text centered
        $centerX = $pos['x'] + $pos['w'] / 2;
        $centerY = $pos['y'] + $pos['h'] / 2;
        $svg .= sprintf(
            '<text x="%d" y="%d" text-anchor="middle" dominant-baseline="middle" fill="#FFFFFF" font-size="28" font-weight="bold" font-family="Arial, sans-serif">%d</text>',
            $centerX,
            $centerY + 2,
            $pos['num']
        );
    }

    // Add cut line for 2x4 and 2x3 layouts (shows where strip is cut)
    if (
        $layout === CollageLayoutEnum::TWO_X_FOUR_1 ||
        $layout === CollageLayoutEnum::TWO_X_FOUR_2 ||
        $layout === CollageLayoutEnum::TWO_X_FOUR_3 ||
        $layout === CollageLayoutEnum::TWO_X_FOUR_4 ||
        $layout === CollageLayoutEnum::TWO_X_THREE_1 ||
        $layout === CollageLayoutEnum::TWO_X_THREE_2
    ) {
        // Dashed line in middle showing where it will be cut
        $svg .= '<line x1="90" y1="0" x2="90" y2="120" stroke="#FF0000" stroke-width="2" stroke-dasharray="5,5" opacity="0.8"/>';
        // Scissors icon or text at middle
        $svg .= '<text x="90" y="6" text-anchor="middle" fill="#FF0000" font-size="8" font-weight="bold">✂</text>';
    }

    // Add outer border to show final photo format
    $svg .= '<rect x="0" y="0" width="180" height="120" fill="none" stroke="#666666" stroke-width="1" rx="2"/>';

    $svg .= '</svg>';
    return $svg;
}

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
                '<div class="collageSelector__preview-container">%s</div>' .
                '<div class="collageSelector__label">%s</div>' .
                '<span class="collageSelector__limit">' .
                $languageService->translate('pictures') . ': %d' .
                '</span>' .
                '</button>',
                $layout->value,
                $limit,
                getLayoutPreviewSvg($layout),
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
