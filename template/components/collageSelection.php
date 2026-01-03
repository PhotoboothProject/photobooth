<?php

/**
 * @var array{collage: array} $config
 */

use Photobooth\Enum\CollageLayoutEnum;
use Photobooth\Service\LanguageService;
use Photobooth\Collage;

/**
 * Evaluate layout expression (e.g., "x*0.5" → 900 if x=1800)
 */
function evaluateLayoutExpression(string $expr, float $x, float $y): float
{
    // Replace x and y with actual values
    $expr = str_replace(['x', 'y'], [(string)$x, (string)$y], $expr);

    // Safely evaluate simple math expressions
    try {
        // Only allow numbers, operators, parentheses, and dots
        if (preg_match('/^[\d\.\+\-\*\/\(\)\s]+$/', $expr)) {
            return (float)eval("return $expr;");
        }
    } catch (\Throwable $e) {
        // Fallback on error
        return 0.0;
    }

    return (float)$expr;
}

/**
 * Load collage layout from JSON file
 */
function loadCollageLayoutFromJson(CollageLayoutEnum $layout, string $orientation = 'landscape'): ?array
{
    // Get JSON path using Collage class method
    $jsonPath = Collage::getCollageConfigPath($layout->value, $orientation);

    if (!$jsonPath || !file_exists($jsonPath)) {
        return null;
    }

    $jsonContent = file_get_contents($jsonPath);
    if ($jsonContent === false) {
        return null;
    }

    $data = json_decode($jsonContent, true);
    if (!is_array($data) || !isset($data['layout'])) {
        return null;
    }

    return $data;
}

/**
 * Generate SVG preview for collage layout (dynamically from JSON)
 */
function getLayoutPreviewSvg(CollageLayoutEnum $layout, string $orientation = 'landscape'): string
{
    // Try to load layout from JSON
    $layoutData = loadCollageLayoutFromJson($layout, $orientation);

    if (!$layoutData) {
        // Fallback to simple 2x2 grid if JSON can't be loaded
        $width = 1800;
        $height = 1200;
        $positions = [
            ['x' => 0, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 1],
            ['x' => 90, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 2],
            ['x' => 0, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 3],
            ['x' => 90, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 4],
        ];
    } else {
        // Extract dimensions from JSON
        $width = (float)($layoutData['width'] ?? 1800);
        $height = (float)($layoutData['height'] ?? 1200);

        // Scale factor for SVG preview (typically /10 for nice numbers)
        $scale = 0.1;

        // Check if this is a photostrip layout (2x4 or 2x3) where photos are duplicated
        $isPhotostrip = in_array($layout, [
            CollageLayoutEnum::TWO_X_FOUR_1,
            CollageLayoutEnum::TWO_X_FOUR_2,
            CollageLayoutEnum::TWO_X_FOUR_3,
            CollageLayoutEnum::TWO_X_FOUR_4,
            CollageLayoutEnum::TWO_X_THREE_1,
            CollageLayoutEnum::TWO_X_THREE_2,
        ]);

        // Calculate how many unique photos (half of total for photostrips)
        $layoutCount = count($layoutData['layout']);
        $uniquePhotoCount = $isPhotostrip ? (int)($layoutCount / 2) : $layoutCount;

        // Process each photo position from layout array
        $positions = [];
        $photoNum = 1;

        foreach ($layoutData['layout'] as $index => $photoLayout) {
            // photoLayout format: [x, y, width, height, rotation, ?frame]
            if (count($photoLayout) < 4) {
                continue;
            }

            $x = evaluateLayoutExpression($photoLayout[0], $width, $height);
            $y = evaluateLayoutExpression($photoLayout[1], $width, $height);
            $w = evaluateLayoutExpression($photoLayout[2], $width, $height);
            $h = evaluateLayoutExpression($photoLayout[3], $width, $height);

            // For photostrips: reset numbering after first half
            $displayNum = $isPhotostrip && $index >= $uniquePhotoCount
                ? ($index - $uniquePhotoCount + 1)
                : $photoNum;

            // Scale to SVG coordinates
            $positions[] = [
                'x' => $x * $scale,
                'y' => $y * $scale,
                'w' => $w * $scale,
                'h' => $h * $scale,
                'num' => $displayNum,
            ];

            // Only increment if not in second half of photostrip
            if (!$isPhotostrip || $index < $uniquePhotoCount - 1) {
                $photoNum++;
            } elseif ($index === $uniquePhotoCount - 1) {
                $photoNum = 1; // Reset for second half
            }
        }
    }

    // Calculate viewBox from actual dimensions
    $viewBoxWidth = $width * 0.1;
    $viewBoxHeight = $height * 0.1;

    // Start SVG with dynamic viewBox
    $svg = sprintf(
        '<svg class="collageSelector__preview" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">',
        (int)round($viewBoxWidth),
        (int)round($viewBoxHeight)
    );

    // Draw each position
    foreach ($positions as $pos) {
        // Rectangle with border
        $svg .= sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="#4A90E2" stroke="#FFFFFF" stroke-width="2" rx="2"/>',
            number_format($pos['x'] + 2, 1, '.', ''),
            number_format($pos['y'] + 2, 1, '.', ''),
            number_format($pos['w'] - 4, 1, '.', ''),
            number_format($pos['h'] - 4, 1, '.', '')
        );

        // Number text centered
        $centerX = $pos['x'] + $pos['w'] / 2;
        $centerY = $pos['y'] + $pos['h'] / 2;
        $svg .= sprintf(
            '<text x="%s" y="%s" text-anchor="middle" dominant-baseline="middle" fill="#FFFFFF" font-size="28" font-weight="bold" font-family="Arial, sans-serif">%d</text>',
            number_format($centerX, 1, '.', ''),
            number_format($centerY + 2, 1, '.', ''),
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
        if ($width > $height) {
            // Landscape layout (e.g. 1800x1200) -> Horizontal cut
            $middleY = $viewBoxHeight / 2;
            $svg .= sprintf(
                '<line x1="0" y1="%s" x2="%s" y2="%s" stroke="#FF0000" stroke-width="2" stroke-dasharray="5,5" opacity="0.8"/>',
                number_format($middleY, 1, '.', ''),
                number_format($viewBoxWidth, 1, '.', ''),
                number_format($middleY, 1, '.', '')
            );
            // Scissors icon or text
            $svg .= sprintf(
                '<text x="6" y="%s" text-anchor="middle" dominant-baseline="middle" fill="#FF0000" font-size="8" font-weight="bold">✂</text>',
                number_format($middleY, 1, '.', '')
            );
        } else {
            // Portrait layout (e.g. 1200x1800) -> Vertical cut
            $middleX = $viewBoxWidth / 2;
            $svg .= sprintf(
                '<line x1="%s" y1="0" x2="%s" y2="%s" stroke="#FF0000" stroke-width="2" stroke-dasharray="5,5" opacity="0.8"/>',
                number_format($middleX, 1, '.', ''),
                number_format($middleX, 1, '.', ''),
                number_format($viewBoxHeight, 1, '.', '')
            );
            // Scissors icon or text at middle
            $svg .= sprintf(
                '<text x="%s" y="6" text-anchor="middle" fill="#FF0000" font-size="8" font-weight="bold">✂</text>',
                number_format($middleX, 1, '.', '')
            );
        }
    }

    // Add outer border to show final photo format
    $svg .= sprintf(
        '<rect x="0" y="0" width="%s" height="%s" fill="none" stroke="#666666" stroke-width="1" rx="2"/>',
        number_format($viewBoxWidth, 1, '.', ''),
        number_format($viewBoxHeight, 1, '.', '')
    );
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

    // Get orientation from config (landscape or portrait)
    $orientation = $collageConfig['orientation'] ?? 'landscape';

    foreach (CollageLayoutEnum::cases() as $layout) {
        if (in_array($layout, $collageConfig['layouts_enabled'])) {
            $collageConfig['layout'] = $layout->value;
            $limitData = Collage::calculateLimit($collageConfig);
            $limit = $limitData['limit'];

            $html .= sprintf(
                '<button type="button" class="collageSelector__option cursor-pointer" data-layout="%s" data-limit="%d">' .
                '<div class="collageSelector__preview-container">%s</div>' .
                '<div class="collageSelector__label">%s</div>' .
                '</button>',
                htmlspecialchars($layout->value),
                $limit,
                getLayoutPreviewSvg($layout, $orientation),
                htmlspecialchars($layout->label())
            );
        }
    }

    $html .= '</div>'; // .collageSelector__options
    $html .= '</div>'; // .modal-body
    $html .= '<div class="modal-buttonbar">';
    $html .= '<button type="button" class="modal-button" id="collageSelectorClose" data-modal-close>';
    $html .= '<span class="modal-button--icon"><i class="fa fa-times"></i></span>';
    $html .= '<span class="modal-button--label">' . $languageService->translate('close') . '</span>';
    $html .= '</button>';
    $html .= '</div>'; // .modal-buttonbar
    $html .= '</div>'; // .modal-inner
    $html .= '</div>'; // .modal
    $html .= '</div>'; // #collageSelector

    return $html;
}

echo renderCollageOptionsFromEnumWithLimit($config['collage']);
