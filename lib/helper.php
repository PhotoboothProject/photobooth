<?php
require_once __DIR__ . '/log.php';

function getrootpath($relative_path) {
    $realpath = realpath($relative_path);
    $rootpath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $realpath);

    return $rootpath;
}

function getPhotoboothIp() {
    $os = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
    if ($os == 'linux') {
        $get_ip = shell_exec('hostname -I | cut -d " " -f 1');

        if (!$get_ip) {
            $ip = $_SERVER['HTTP_HOST'];
        } else {
            $ip = $get_ip;
        }
    } else {
        $ip = $_SERVER['HTTP_HOST'];
    }

    return $ip;
}

function getPhotoboothFolder() {
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    if ($path == $_SERVER['DOCUMENT_ROOT']) {
        return false;
    } else {
        $folder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
        return $folder;
    }
}

function getPhotoboothUrl() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    $ip = getPhotoboothIp();
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    if ($path == $_SERVER['DOCUMENT_ROOT']) {
        $url = $protocol . '://' . $ip;
    } else {
        $folder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
        $url = $protocol . '://' . $ip . $folder;
    }

    return $url;
}

function testFile($file) {
    $realPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $file);
    if (is_dir($realPath)) {
        $ErrorData = [
            'error' => $file . ' is a path! Frames need to be PNG, Fonts need to be ttf!',
        ];
        logError($ErrorData);
        return false;
    }

    if (!file_exists($realPath)) {
        $ErrorData = [
            'error' => $file . ' does not exist!',
        ];
        logError($ErrorData);
        return false;
    }
    return true;
}
