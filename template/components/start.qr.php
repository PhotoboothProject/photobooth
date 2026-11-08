<?php

use Photobooth\Service\LoggerService;
use Photobooth\Utility\QrCodeUtility;

$startQrUrl = trim((string) ($config['start_screen']['qr_url'] ?? ''));

if (($config['start_screen']['qr_visible'] ?? false) && $startQrUrl !== '') {
    try {
        $startQrImage = QrCodeUtility::create($startQrUrl, '', 400, 16)->getDataUri();
    } catch (\Throwable $e) {
        $startQrImage = null;
        LoggerService::getInstance()->getLogger('main')->error('Start screen QR code: ' . $e->getMessage());
    }

    if ($startQrImage !== null) {
        $startQrText = trim((string) ($config['start_screen']['qr_text'] ?? ''));
        echo '<div class="start-qr start-qr--' . htmlspecialchars($config['start_screen']['qr_position'] ?? 'top_right') . '">';
        echo '<img class="start-qr__code" src="' . $startQrImage . '" alt="' . htmlspecialchars($startQrUrl) . '">';
        if ($startQrText !== '') {
            echo '<div class="start-qr__text">' . htmlspecialchars($startQrText) . '</div>';
        }
        echo '</div>';
    }
}
