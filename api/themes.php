<?php

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Service\ThemeService;

$themeService = ThemeService::getInstance();

$sendJson = static function (array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
};

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$query = $_GET;

if ($method === 'GET') {
    $action = $query['action'] ?? 'list';

    if ($action === 'list') {
        $all = $themeService->getAll();
        $sendJson([
            'status' => 'success',
            'themes' => array_keys($all),
        ]);
    }

    if ($action === 'get') {
        $name = (string)($query['name'] ?? '');
        $theme = $themeService->get($name);
        if ($theme === null) {
            $sendJson([
                'status' => 'error',
                'message' => 'Theme not found',
            ]);
        }

        $sendJson([
            'status' => 'success',
            'theme' => $theme,
        ]);
    }

    if ($action === 'export') {
        $name = (string)($query['name'] ?? '');
        $result = $themeService->exportTheme($name);
        if (!$result['success'] || !isset($result['file'])) {
            $sendJson([
                'status' => 'error',
                'message' => $result['message'] ?? 'Failed to export theme',
            ]);
        }

        $downloadName = isset($result['downloadName']) ? basename($result['downloadName']) : 'theme.zip';
        $filePath = $result['file'];
        if (!is_file($filePath) || !is_readable($filePath)) {
            $sendJson([
                'status' => 'error',
                'message' => 'Export file not found',
            ]);
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        @unlink($filePath);
        exit();
    }

    $sendJson([
        'status' => 'error',
        'message' => 'Unknown action',
    ]);
}

// Handle multipart/form-data import first
$postAction = $_POST['action'] ?? null;
if ($postAction !== null) {
    checkCsrfOrFail($_POST);
}
if ($postAction === 'import') {
    if (!isset($_FILES['theme_zip']) || !is_uploaded_file($_FILES['theme_zip']['tmp_name']) || $_FILES['theme_zip']['error'] !== UPLOAD_ERR_OK) {
        $sendJson([
            'status' => 'error',
            'message' => 'No theme zip provided',
        ]);
    }

    $targetName = isset($_POST['name']) ? (string)$_POST['name'] : null;
    $tmpFile = $_FILES['theme_zip']['tmp_name'];

    $result = $themeService->importTheme($tmpFile, $targetName);
    if (!$result['success']) {
        $sendJson([
            'status' => 'error',
            'message' => $result['message'] ?? 'Import failed',
        ]);
    }

    $sendJson([
        'status' => 'success',
        'name' => $result['name'] ?? '',
        'theme' => $result['theme'] ?? [],
    ]);
}

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody ?: '[]', true);

if (!is_array($body)) {
    $body = [];
}

$action = $body['action'] ?? null;

if ($action === 'save') {
    checkCsrfOrFail($body);
    $name = isset($body['name']) ? (string)$body['name'] : '';
    $data = isset($body['theme']) && is_array($body['theme']) ? $body['theme'] : [];

    if ($name === '') {
        $sendJson([
            'status' => 'error',
            'message' => 'Missing theme name',
        ]);
    }

    $themeService->save($name, $data);

    $sendJson([
        'status' => 'success',
        'message' => 'Theme saved',
    ]);
}

if ($action === 'delete') {
    checkCsrfOrFail($body);
    $name = isset($body['name']) ? (string)$body['name'] : '';

    if ($name === '') {
        $sendJson([
            'status' => 'error',
            'message' => 'Missing theme name',
        ]);
    }

    $themeService->delete($name);

    $sendJson([
        'status' => 'success',
        'message' => 'Theme deleted',
    ]);
}

$sendJson([
    'status' => 'error',
    'message' => 'Unknown action',
]);
