<?php

require_once '../lib/boot.php';

use Photobooth\Utility\PathUtility;

header('Content-Type: application/json');

$layouts = [];
$seen = [];

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

foreach ($templateOrientationDirs as $orientationDir) {
    $readLayoutsFromDir($orientationDir, false);
}
$readLayoutsFromDir($templateLayoutsDir, false);

foreach ($privateOrientationDirs as $orientationDir) {
    $readLayoutsFromDir($orientationDir, true);
}
$readLayoutsFromDir($privateLayoutsDir, true);

usort($layouts, static function (array $left, array $right): int {
    return strnatcasecmp($left['label'], $right['label']);
});

echo json_encode($layouts);

exit();
