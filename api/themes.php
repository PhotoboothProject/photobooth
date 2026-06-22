<?php

require_once __DIR__ . '/../admin/admin_boot.php';

use Photobooth\Service\ThemeService;

$themeService = ThemeService::getInstance();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$query = $_GET;

$sendJson = static function (array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
};

$getThemeUploadErrorMessage = static function (?int $uploadError = null) use ($method): string {
    $uploadLimit = (string)ini_get('upload_max_filesize');
    $postLimit = (string)ini_get('post_max_size');

    if ($method === 'POST') {
        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if (str_contains($contentType, 'multipart/form-data') && $contentLength > 0 && empty($_POST) && empty($_FILES)) {
            return sprintf(
                'Theme import exceeded the server upload limit. Increase upload_max_filesize and post_max_size. Current limits: upload_max_filesize=%s, post_max_size=%s.',
                $uploadLimit,
                $postLimit
            );
        }
    }

    return match ($uploadError) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => sprintf(
            'Theme import exceeded the server upload limit. Increase upload_max_filesize and post_max_size. Current limits: upload_max_filesize=%s, post_max_size=%s.',
            $uploadLimit,
            $postLimit
        ),
        UPLOAD_ERR_PARTIAL => 'Theme zip was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE => 'No theme zip provided.',
        default => 'Theme import failed while uploading the zip file.',
    };
};

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
if (
    $method === 'POST' &&
    str_contains(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'multipart/form-data') &&
    $postAction === null &&
    empty($_FILES) &&
    ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
) {
    $sendJson([
        'status' => 'error',
        'message' => $getThemeUploadErrorMessage(),
    ]);
}

if ($postAction !== null) {
    checkCsrfOrFail($_POST);
}
if ($postAction === 'import') {
    $uploadError = $_FILES['theme_zip']['error'] ?? UPLOAD_ERR_NO_FILE;
    $tmpFile = $_FILES['theme_zip']['tmp_name'] ?? '';

    if (!isset($_FILES['theme_zip']) || !is_uploaded_file($tmpFile) || $uploadError !== UPLOAD_ERR_OK) {
        $sendJson([
            'status' => 'error',
            'message' => $getThemeUploadErrorMessage($uploadError),
        ]);
    }

    $targetName = isset($_POST['name']) ? (string)$_POST['name'] : null;

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
