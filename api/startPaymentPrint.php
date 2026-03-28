<?php

declare(strict_types=1);

use Photobooth\Service\ConfigurationService;
use Photobooth\Utility\PathUtility;

require_once dirname(__DIR__) . '/lib/boot.php';

header('Content-Type: application/json');

try {
    $config = ConfigurationService::getInstance()->getConfiguration();

    if (empty($config['payments']['enabled'])) {
        echo json_encode([
            'status' => 'disabled',
            'error' => 'Zahlungssystem ist deaktiviert',
        ]);
        exit;
    }

    $filename = trim((string)($_POST['filename'] ?? ''));
    $copies = (int)($_POST['copies'] ?? 1);

    if ($filename === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'error' => 'Missing filename',
        ]);
        exit;
    }

    $provider = trim((string)($config['payments']['provider'] ?? 'none'));
    $displayMode = trim((string)($config['payments']['display_mode'] ?? 'solo'));
    $webhookUrl = rtrim(trim((string)($config['payments']['webhook_url'] ?? '')), '/');

    $merchantCode = trim((string)($config['payments']['sumup']['merchant_code'] ?? ''));
    $readerId = trim((string)($config['payments']['sumup']['reader_id'] ?? ''));
    $affiliateKey = trim((string)($config['payments']['sumup']['affiliate_key'] ?? ''));

    $amountCentsRaw = $config['payments']['price_cents'] ?? 0;
    $amountCents = (int)$amountCentsRaw;

    $python = '/usr/bin/python3';
    $soloScript = PathUtility::getAbsolutePath('api/sumup_solo.py');
    $checkoutScript = PathUtility::getAbsolutePath('api/create_checkout.py');

    $logFile = PathUtility::getAbsolutePath('private/payment-print.log');
    $jobFile = PathUtility::getAbsolutePath('private/photobooth_current_print.json');
    $soloBgLog = '/tmp/sumup_solo_both.log';

    $logLines = [
        '[' . date('c') . '] startPaymentPrint',
        'filename=' . $filename,
        'copies=' . $copies,
        'provider=' . $provider,
        'display_mode=' . $displayMode,
        'merchant_code=' . $merchantCode,
        'reader_id=' . $readerId,
        'affiliate_key_present=' . ($affiliateKey !== '' ? 'yes' : 'no'),
        'amount_cents=' . $amountCents,
        'webhook_url=' . $webhookUrl,
    ];

    if ($provider !== 'sumup') {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'Anbieter ist nicht SumUp',
        ]);
        exit;
    }

    if ($merchantCode === '') {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'SumUp Merchant Code fehlt',
        ]);
        exit;
    }

    if ($amountCents <= 0) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'Preis (Cent) ist ungültig oder fehlt',
        ]);
        exit;
    }

    if ($displayMode === 'solo') {
        if ($readerId === '') {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'SumUp Reader ID fehlt',
            ]);
            exit;
        }

        if ($affiliateKey === '') {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'SumUp Affiliate Key fehlt',
            ]);
            exit;
        }

        if (!is_file($soloScript)) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'sumup_solo.py wurde nicht gefunden',
            ]);
            exit;
        }

        $cmd = escapeshellcmd($python) . ' ' .
            escapeshellarg($soloScript) . ' ' .
            escapeshellarg($merchantCode) . ' ' .
            escapeshellarg($readerId) . ' ' .
            escapeshellarg($affiliateKey) . ' ' .
            escapeshellarg((string)$amountCents) . ' 2>&1';

        $output = [];
        $returnVar = 1;
        exec($cmd, $output, $returnVar);

        $logLines[] = 'command=' . $cmd;
        $logLines[] = 'return_code=' . $returnVar;
        $logLines[] = 'output:';
        $logLines[] = implode(PHP_EOL, $output);
        $logLines[] = '';

        file_put_contents($logFile, implode(PHP_EOL, $logLines) . PHP_EOL, FILE_APPEND);

        if ($returnVar === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Zahlung erfolgreich - Druck startet...',
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'error',
            'error' => 'Zahlung fehlgeschlagen oder abgebrochen',
            'log' => implode("\n", $output),
        ]);
        exit;
    }

    if ($displayMode === 'qr' || $displayMode === 'both') {
        if ($webhookUrl === '') {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'ngrok URL / webhook_url fehlt',
            ]);
            exit;
        }

        if (!is_file($checkoutScript)) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'create_checkout.py wurde nicht gefunden',
            ]);
            exit;
        }

        $returnUrl = $webhookUrl . '/sumup/webhook';

        $checkoutCmd = escapeshellcmd($python) . ' ' .
            escapeshellarg($checkoutScript) . ' ' .
            escapeshellarg($merchantCode) . ' ' .
            escapeshellarg((string)$amountCents) . ' ' .
            escapeshellarg($returnUrl) . ' 2>&1';

        $checkoutOutput = [];
        $checkoutReturnVar = 1;
        exec($checkoutCmd, $checkoutOutput, $checkoutReturnVar);

        $paymentUrl = '';
        if (!empty($checkoutOutput)) {
            $paymentUrl = trim(end($checkoutOutput));
        }

        $logLines[] = 'checkout_command=' . $checkoutCmd;
        $logLines[] = 'checkout_return_code=' . $checkoutReturnVar;
        $logLines[] = 'checkout_output:';
        $logLines[] = implode(PHP_EOL, $checkoutOutput);

        if ($checkoutReturnVar !== 0 || $paymentUrl === '' || strpos($paymentUrl, 'https://') !== 0) {
            $logLines[] = '';
            file_put_contents($logFile, implode(PHP_EOL, $logLines) . PHP_EOL, FILE_APPEND);

            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'QR-Zahlungslink konnte nicht erstellt werden',
                'log' => implode("\n", $checkoutOutput),
            ]);
            exit;
        }

        $jobData = json_encode([
            'filename' => $filename,
            'copies' => $copies,
            'printed' => false,
            'paid' => false,
            'created_at' => date('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $jobWriteResult = file_put_contents($jobFile, $jobData);

        $logLines[] = 'job_file=' . $jobFile;
        $logLines[] = 'job_write_result=' . var_export($jobWriteResult, true);
        $logLines[] = 'payment_url=' . $paymentUrl;

        if ($displayMode === 'qr') {
            $logLines[] = '';
            file_put_contents($logFile, implode(PHP_EOL, $logLines) . PHP_EOL, FILE_APPEND);

            echo json_encode([
                'status' => 'qr',
                'payment_url' => $paymentUrl,
                'message' => 'QR-Zahlung bereit',
            ]);
            exit;
        }

        if ($readerId === '') {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'SumUp Reader ID fehlt',
            ]);
            exit;
        }

        if ($affiliateKey === '') {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'SumUp Affiliate Key fehlt',
            ]);
            exit;
        }

        if (!is_file($soloScript)) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'error' => 'sumup_solo.py wurde nicht gefunden',
            ]);
            exit;
        }

        $soloCmd = 'nohup ' .
            escapeshellcmd($python) . ' ' .
            escapeshellarg($soloScript) . ' ' .
            escapeshellarg($merchantCode) . ' ' .
            escapeshellarg($readerId) . ' ' .
            escapeshellarg($affiliateKey) . ' ' .
            escapeshellarg((string)$amountCents) .
            ' >> ' . escapeshellarg($soloBgLog) . ' 2>&1 &';

        exec($soloCmd);

        $logLines[] = 'solo_background_command=' . $soloCmd;
        $logLines[] = '';

        file_put_contents($logFile, implode(PHP_EOL, $logLines) . PHP_EOL, FILE_APPEND);

        echo json_encode([
            'status' => 'both',
            'payment_url' => $paymentUrl,
            'message' => 'QR-Zahlung bereit, Terminal wurde im Hintergrund gestartet',
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Ungültiger Zahlungsmodus',
    ]);
} catch (\Throwable $e) {
    http_response_code(500);

    $logFile = PathUtility::getAbsolutePath('private/payment-print.log');
    file_put_contents(
        $logFile,
        '[' . date('c') . '] EXCEPTION startPaymentPrint: ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );

    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
    ]);
}
