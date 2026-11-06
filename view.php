<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\PathUtility;

require_once __DIR__ . '/lib/boot.php';

$imageParam = $_GET['image'] ?? '';
$image = basename((string) $imageParam);

if ($image === '') {
    http_response_code(400);
    echo 'No image specified.';
    exit();
}

$imagePath = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $image;
if (!is_file($imagePath)) {
    http_response_code(404);
    echo 'Image not found.';
    exit();
}

$extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
$isVideo = in_array($extension, ['mp4', 'mov', 'webm'], true);
$mime = match ($extension) {
    'png' => 'image/png',
    'gif' => 'image/gif',
    default => 'image/jpeg',
};
$imageUrl = PathUtility::getPublicPath('api/download.php?image=' . rawurlencode($image) . '&inline=1');
$downloadUrl = PathUtility::getPublicPath('api/download.php?image=' . rawurlencode($image));
$languageService = LanguageService::getInstance();
$appTitle = ApplicationService::getInstance()->getTitle();
$startScreenTitle = trim((string) ($config['start_screen']['title'] ?? ''));
$viewerFallbackTitle = $startScreenTitle !== '' ? $startScreenTitle : $appTitle;
$pageTitle = $appTitle . ' - ' . $languageService->translate('viewer_photo_title');
$downloadLabel = $languageService->translate('download');
$eventTitleLines = $config['event']['enabled']
    ? array_values(array_filter([
        $config['event']['textLeft'] ?? '',
        $config['event']['textRight'] ?? '',
    ], static fn ($line) => $line !== ''))
    : [$viewerFallbackTitle];

if ($eventTitleLines === []) {
    $eventTitleLines = [$viewerFallbackTitle];
}

// Public event hosts may expose this viewer and api/download.php, but still 404
// the usual Photobooth assets under /resources, /node_modules, /private and
// api/settings.php. Keep this page self-contained unless those asset paths are
// available again. As a consequence, shared JS/CSS, Font Awesome icons and the
// actual configured custom font files are not used here.
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($config['ui']['language'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="<?= htmlspecialchars($config['colors']['status_bar'] ?? '#000000') ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="16" fill="%23<?= ltrim(htmlspecialchars($config['colors']['primary'] ?? '222222'), '#') ?>"/><circle cx="32" cy="32" r="14" fill="white"/><circle cx="32" cy="32" r="6" fill="%23<?= ltrim(htmlspecialchars($config['colors']['secondary'] ?? '555555'), '#') ?>"/></svg>'>
    <style>
        :root {
            --viewer-bg: <?= htmlspecialchars($config['colors']['primary_light'] ?? '#f4f4f4') ?>;
            --viewer-surface: #ffffff;
            --viewer-primary: <?= htmlspecialchars($config['colors']['primary'] ?? '#222222') ?>;
            --viewer-secondary: #6f675f;
            --viewer-font: #1f1f1f;
            --viewer-border: color-mix(in srgb, var(--viewer-primary), white 82%);
            --viewer-button: <?= htmlspecialchars($config['colors']['primary'] ?? '#222222') ?>;
            --viewer-button-text: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, "Avenir Next", "Segoe UI", sans-serif;
            color: var(--viewer-font);
            background: var(--viewer-bg);
            display: grid;
            place-items: center;
            padding: 16px;
        }

        .viewer {
            width: min(920px, 100%);
            background: transparent;
        }

        .viewer__inner {
            background: var(--viewer-surface);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            border: 1px solid var(--viewer-border);
            box-shadow: 0 18px 40px color-mix(in srgb, var(--viewer-primary), transparent 88%);
        }

        .viewer__title {
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: var(--viewer-primary);
            font-family: "Baskerville", "Times New Roman", "Georgia", serif;
            font-style: italic;
            font-weight: 600;
            font-size: clamp(28px, 4.8vw, 40px);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .viewer__accent {
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--viewer-primary), color-mix(in srgb, var(--viewer-primary), white 28%));
        }

        .viewer__media {
            min-height: min(40vh, 320px);
            max-height: 70vh;
            border-radius: 16px;
            overflow: hidden;
            background: color-mix(in srgb, var(--viewer-bg), white 45%);
            border: 1px solid var(--viewer-border);
            display: grid;
            place-items: center;
            padding: 12px;
        }

        .viewer__media img,
        .viewer__media video {
            display: block;
            max-width: 100%;
            max-height: min(60vh, 720px);
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
        }

        .viewer__actions {
            display: flex;
            justify-content: center;
        }

        .viewer__download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 22px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            color: var(--viewer-button-text);
            background: var(--viewer-button);
            box-shadow: 0 10px 24px color-mix(in srgb, var(--viewer-primary), transparent 82%);
        }

        @media (max-width: 540px) {
            .viewer__inner {
                padding: 14px;
            }

            .viewer__media img,
            .viewer__media video {
                max-height: 52vh;
            }
        }
    </style>
</head>
<body>
    <main class="viewer">
        <div class="viewer__inner">
            <header class="viewer__title">
                <?php foreach ($eventTitleLines as $line): ?>
                    <span><?= htmlspecialchars($line) ?></span>
                <?php endforeach; ?>
            </header>
            <div class="viewer__accent"></div>

            <div class="viewer__media" aria-label="Captured media preview">
                <?php if ($isVideo): ?>
                    <video src="<?= htmlspecialchars($imageUrl) ?>" controls playsinline controlsList="nodownload">
                        <?= htmlspecialchars($languageService->translate('viewer_video_fallback')) ?>
                    </video>
                <?php else: ?>
                    <img id="viewer-image" src="<?= htmlspecialchars($imageUrl) ?>" alt="Captured photo">
                <?php endif; ?>
            </div>

            <div class="viewer__actions">
                <a class="viewer__download" href="<?= htmlspecialchars($downloadUrl) ?>" download="download">
                    <?= htmlspecialchars($downloadLabel) ?>
                </a>
            </div>
        </div>
    </main>
</body>
</html>
