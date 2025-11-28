<?php

require_once '../lib/boot.php';

use Photobooth\Service\ThemeService;

header('Content-Type: application/json');

$themeService = ThemeService::getInstance();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$query = $_GET;

if ($method === 'GET') {
    $action = $query['action'] ?? 'list';

    if ($action === 'list') {
        $all = $themeService->getAll();
        echo json_encode([
            'status' => 'success',
            'themes' => array_keys($all),
        ]);
        exit();
    }

    if ($action === 'get') {
        $name = (string)($query['name'] ?? '');
        $theme = $themeService->get($name);
        if ($theme === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Theme not found',
            ]);
            exit();
        }

        echo json_encode([
            'status' => 'success',
            'theme' => $theme,
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'error',
        'message' => 'Unknown action',
    ]);
    exit();
}

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody ?: '[]', true);

if (!is_array($body)) {
    $body = [];
}

$action = $body['action'] ?? null;

if ($action === 'save') {
    $name = isset($body['name']) ? (string)$body['name'] : '';
    $data = isset($body['theme']) && is_array($body['theme']) ? $body['theme'] : [];

    if ($name === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing theme name',
        ]);
        exit();
    }

    $themeService->save($name, $data);

    echo json_encode([
        'status' => 'success',
        'message' => 'Theme saved',
    ]);
    exit();
}

if ($action === 'delete') {
    $name = isset($body['name']) ? (string)$body['name'] : '';

    if ($name === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing theme name',
        ]);
        exit();
    }

    $themeService->delete($name);

    echo json_encode([
        'status' => 'success',
        'message' => 'Theme deleted',
    ]);
    exit();
}

echo json_encode([
    'status' => 'error',
    'message' => 'Unknown action',
]);
exit();
