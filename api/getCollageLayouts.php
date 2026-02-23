<?php

require_once '../lib/boot.php';

use Photobooth\Collage;
use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$allowedOrientations = ['landscape', 'portrait'];
$requestedOrientation = isset($_GET['orientation']) ? (string) $_GET['orientation'] : 'landscape';
$orientation = in_array($requestedOrientation, $allowedOrientations, true) ? $requestedOrientation : 'landscape';
$fallbackOrientation = $orientation === 'landscape' ? 'portrait' : 'landscape';

function evaluateCollageExpression(string $expr, float $x, float $y): float
{
    $expr = str_replace(['x', 'y'], [(string) $x, (string) $y], $expr);

    try {
        if (preg_match('/^[\d\.\+\-\*\/\(\)\s]+$/', $expr)) {
            return (float) eval("return $expr;");
        }
    } catch (\Throwable $e) {
        return 0.0;
    }

    return (float) $expr;
}

function loadCollageLayoutData(string $layoutId, string $orientation): ?array
{
    $jsonPath = Collage::getCollageConfigPath($layoutId, $orientation);

    if ($jsonPath === null || !is_file($jsonPath)) {
        return null;
    }

    $jsonContent = file_get_contents($jsonPath);
    if ($jsonContent === false) {
        return null;
    }

    $data = json_decode($jsonContent, true);
    if (!is_array($data)) {
        return null;
    }

    if (isset($data['layout']) && is_array($data['layout'])) {
        return $data;
    }

    if (isset($data[0]) && is_array($data[0])) {
        return [
            'layout' => $data,
        ];
    }

    return null;
}

function ensurePrivateLayoutDirectories(): void
{
    $directories = [
        'private/collage/layouts',
        'private/collage/layouts/landscape',
        'private/collage/layouts/portrait',
    ];

    foreach ($directories as $directory) {
        $absolutePath = PathUtility::getAbsolutePath($directory);
        if (is_dir($absolutePath)) {
            continue;
        }

        @mkdir($absolutePath, 0775, true);
    }
}

function buildCollageLayoutPreviewSvg(string $layoutId, ?array $layoutData): string
{
    if (!is_array($layoutData) || empty($layoutData['layout']) || !is_array($layoutData['layout'])) {
        $width = 1800;
        $height = 1200;
        $positions = [
            ['x' => 0, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 1],
            ['x' => 90, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 2],
            ['x' => 0, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 3],
            ['x' => 90, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 4],
        ];
    } else {
        $width = (float) ($layoutData['width'] ?? 1800);
        $height = (float) ($layoutData['height'] ?? 1200);
        $scale = 0.1;

        $layoutName = str_ends_with($layoutId, '.json') ? substr($layoutId, 0, -5) : $layoutId;
        $isPhotostrip = str_starts_with($layoutName, '2x');
        $layoutCount = count($layoutData['layout']);
        $uniquePhotoCount = $isPhotostrip ? (int) ($layoutCount / 2) : $layoutCount;

        $positions = [];
        $photoNum = 1;

        foreach ($layoutData['layout'] as $index => $photoLayout) {
            if (!is_array($photoLayout) || count($photoLayout) < 4) {
                continue;
            }

            $x = evaluateCollageExpression((string) $photoLayout[0], $width, $height);
            $y = evaluateCollageExpression((string) $photoLayout[1], $width, $height);
            $w = evaluateCollageExpression((string) $photoLayout[2], $width, $height);
            $h = evaluateCollageExpression((string) $photoLayout[3], $width, $height);

            $displayNum = $isPhotostrip && $index >= $uniquePhotoCount
                ? ($index - $uniquePhotoCount + 1)
                : $photoNum;

            $positions[] = [
                'x' => $x * $scale,
                'y' => $y * $scale,
                'w' => $w * $scale,
                'h' => $h * $scale,
                'num' => $displayNum,
            ];

            if (!$isPhotostrip || $index < $uniquePhotoCount - 1) {
                $photoNum++;
            } elseif ($index === $uniquePhotoCount - 1) {
                $photoNum = 1;
            }
        }

        if (empty($positions)) {
            $positions = [
                ['x' => 0, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 1],
                ['x' => 90, 'y' => 0, 'w' => 90, 'h' => 60, 'num' => 2],
                ['x' => 0, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 3],
                ['x' => 90, 'y' => 60, 'w' => 90, 'h' => 60, 'num' => 4],
            ];
        }
    }

    $viewBoxWidth = $width * 0.1;
    $viewBoxHeight = $height * 0.1;

    $svg = sprintf(
        '<svg class="collageSelector__preview" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">',
        (int) round($viewBoxWidth),
        (int) round($viewBoxHeight)
    );

    foreach ($positions as $pos) {
        $svg .= sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="#4A90E2" stroke="#FFFFFF" stroke-width="2" rx="2"/>',
            number_format($pos['x'] + 2, 1, '.', ''),
            number_format($pos['y'] + 2, 1, '.', ''),
            number_format($pos['w'] - 4, 1, '.', ''),
            number_format($pos['h'] - 4, 1, '.', '')
        );

        $centerX = $pos['x'] + $pos['w'] / 2;
        $centerY = $pos['y'] + $pos['h'] / 2;
        $svg .= sprintf(
            '<text x="%s" y="%s" text-anchor="middle" dominant-baseline="middle" fill="#FFFFFF" font-size="28" font-weight="bold" font-family="Arial, sans-serif">%d</text>',
            number_format($centerX, 1, '.', ''),
            number_format($centerY + 2, 1, '.', ''),
            $pos['num']
        );
    }

    $layoutName = str_ends_with($layoutId, '.json') ? substr($layoutId, 0, -5) : $layoutId;
    $isPhotostrip = str_starts_with($layoutName, '2x');
    if ($isPhotostrip) {
        if ($width > $height) {
            $middleY = $viewBoxHeight / 2;
            $svg .= sprintf(
                '<line x1="0" y1="%s" x2="%s" y2="%s" stroke="#FF0000" stroke-width="2" stroke-dasharray="5,5" opacity="0.8"/>',
                number_format($middleY, 1, '.', ''),
                number_format($viewBoxWidth, 1, '.', ''),
                number_format($middleY, 1, '.', '')
            );
        } else {
            $middleX = $viewBoxWidth / 2;
            $svg .= sprintf(
                '<line x1="%s" y1="0" x2="%s" y2="%s" stroke="#FF0000" stroke-width="2" stroke-dasharray="5,5" opacity="0.8"/>',
                number_format($middleX, 1, '.', ''),
                number_format($middleX, 1, '.', ''),
                number_format($viewBoxHeight, 1, '.', '')
            );
        }
    }

    $svg .= sprintf(
        '<rect x="0" y="0" width="%s" height="%s" fill="none" stroke="#666666" stroke-width="1" rx="2"/>',
        number_format($viewBoxWidth, 1, '.', ''),
        number_format($viewBoxHeight, 1, '.', '')
    );
    $svg .= '</svg>';

    return $svg;
}

$layouts = [];
$seen = [];

ensurePrivateLayoutDirectories();

$readLayoutsFromDir = static function (string $dirPath, bool $isPrivateSource) use (&$layouts, &$seen): void {
    if (!is_dir($dirPath)) {
        return;
    }

    $iterator = new DirectoryIterator($dirPath);
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        if (strtolower($fileInfo->getExtension()) !== 'json') {
            continue;
        }

        $layoutId = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
        if ($layoutId === '') {
            continue;
        }

        $label = $layoutId;
        $isValidJson = true;
        $contents = file_get_contents($fileInfo->getPathname());
        if ($contents !== false) {
            $decoded = json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $isValidJson = false;
            } elseif (isset($decoded['name']) && is_string($decoded['name']) && $decoded['name'] !== '') {
                $label = $decoded['name'];
            }
        } else {
            $isValidJson = false;
        }

        if ($isPrivateSource && !$isValidJson) {
            continue;
        }

        if (isset($seen[$layoutId])) {
            $layouts[$seen[$layoutId]] = [
                'id' => $layoutId,
                'label' => $label,
            ];
            continue;
        }

        $layouts[] = [
            'id' => $layoutId,
            'label' => $label,
        ];
        $seen[$layoutId] = count($layouts) - 1;
    }
};

$templateLayoutsDir = PathUtility::getAbsolutePath('template/collage');
$templateOrientationDirs = [
    $templateLayoutsDir . DIRECTORY_SEPARATOR . 'landscape',
    $templateLayoutsDir . DIRECTORY_SEPARATOR . 'portrait',
];

$privateLayoutsDir = PathUtility::getAbsolutePath('private/collage/layouts');
$privateOrientationDirs = [
    $privateLayoutsDir . DIRECTORY_SEPARATOR . 'landscape',
    $privateLayoutsDir . DIRECTORY_SEPARATOR . 'portrait',
];

$legacyPrivateCollageDir = PathUtility::getAbsolutePath('private/collage');
$legacyPrivateOrientationDirs = [
    $legacyPrivateCollageDir . DIRECTORY_SEPARATOR . 'landscape',
    $legacyPrivateCollageDir . DIRECTORY_SEPARATOR . 'portrait',
];

foreach ($templateOrientationDirs as $orientationDir) {
    $readLayoutsFromDir($orientationDir, false);
}
$readLayoutsFromDir($templateLayoutsDir, false);

foreach ($privateOrientationDirs as $orientationDir) {
    $readLayoutsFromDir($orientationDir, true);
}
$readLayoutsFromDir($privateLayoutsDir, true);

foreach ($legacyPrivateOrientationDirs as $orientationDir) {
    $readLayoutsFromDir($orientationDir, true);
}
$readLayoutsFromDir($legacyPrivateCollageDir, true);

foreach ($layouts as &$layout) {
    $layoutId = (string) $layout['id'];
    $layoutData = loadCollageLayoutData($layoutId, $orientation);
    if ($layoutData === null && $fallbackOrientation !== $orientation) {
        $layoutData = loadCollageLayoutData($layoutId, $fallbackOrientation);
    }
    if (is_array($layoutData) && isset($layoutData['name']) && is_string($layoutData['name']) && $layoutData['name'] !== '') {
        $layout['label'] = $layoutData['name'];
    }
    $layout['preview'] = buildCollageLayoutPreviewSvg($layoutId, $layoutData);
}
unset($layout);

usort($layouts, static function (array $left, array $right): int {
    return strnatcasecmp($left['label'], $right['label']);
});

echo json_encode($layouts);

exit();
