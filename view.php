<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\ComponentUtility;
use Photobooth\Utility\PathUtility;

require_once __DIR__ . '/lib/boot.php';

$languageService = LanguageService::getInstance();

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

$imageUrl = PathUtility::getPublicPath(FolderEnum::IMAGES->value . '/' . rawurlencode($image));
$downloadUrl = PathUtility::getPublicPath('api/download.php?image=' . rawurlencode($image));
$pageTitle = ApplicationService::getInstance()->getTitle() . ' - ' . $languageService->translate('viewer_photo_title');
$photoswipe = false;
$remoteBuzzer = false;

include PathUtility::getAbsolutePath('template/components/main.head.php');
?>
<body class="viewer-page">
    <main class="viewer">
        <div class="viewer__inner">
            <header class="viewer__header">
                <div class="viewer__title">
                    <?php if ($config['event']['enabled']): ?>
                        <span class="viewer__title-line"><?= htmlspecialchars($config['event']['textLeft']) ?></span>
                        <?php if (!empty($config['event']['symbol'])): ?>
                            <span class="viewer__title-line">
                                <i class="fa <?= htmlspecialchars($config['event']['symbol']) ?>" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                        <span class="viewer__title-line"><?= htmlspecialchars($config['event']['textRight']) ?></span>
                    <?php else: ?>
                        <span class="viewer__title-line"><?= htmlspecialchars(ApplicationService::getInstance()->getTitle()) ?></span>
                    <?php endif; ?>
                </div>
            </header>
            <div class="viewer__accent"></div>

            <div class="viewer__media" aria-label="Captured media preview">
                <?php if ($isVideo): ?>
                    <video src="<?=$imageUrl?>" controls playsinline controlsList="nodownload">
                        <?=htmlspecialchars($languageService->translate('viewer_video_fallback'))?>
                    </video>
                <?php else: ?>
                    <img id="viewer-image" src="<?=$imageUrl?>" alt="Captured photo">
                <?php endif; ?>
            </div>

            <div class="viewer__actions buttonbar">
                <?= ComponentUtility::renderButtonLink('download', $config['icons']['download'], $downloadUrl, true, ['download' => 'download']) ?>
            </div>

        </div>
    </main>

    <?php include PathUtility::getAbsolutePath('template/components/main.footer.php'); ?>
</body>
</html>
