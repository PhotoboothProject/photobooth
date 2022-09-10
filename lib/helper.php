<?php
require_once __DIR__ . '/log.php';

function getPhotoboothPath() {
    if (SERVER_OS == 'linux') {
        $server_path = $_SERVER['DOCUMENT_ROOT'];
    } else {
        $server_path = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

    return $server_path;
}

function isSubfolderInstall() {
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    $server_path = getPhotoboothPath();

    if ($path == $server_path) {
        return false;
    } else {
        return true;
    }
}

function getrootpath($relative_path) {
    $realpath = realpath($relative_path);
    $server_path = getPhotoboothPath();
    $rootpath = str_replace($server_path, '', $realpath);

    return $rootpath;
}

function fixSeperator($fix_path) {
    $fixedPath = str_replace('\\', '/', $fix_path);
    return $fixedPath;
}

function getPhotoboothIp() {
    if (SERVER_OS == 'linux') {
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
    $server_path = getPhotoboothPath();

    if ($path == $server_path) {
        return false;
    } else {
        $folder = str_replace($server_path, '', $path);
        return $folder;
    }
}

function getPhotoboothUrl() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    $server_path = getPhotoboothPath();
    $ip = getPhotoboothIp();
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    if ($path == $server_path) {
        $url = $protocol . '://' . $ip;
    } else {
        $folder = str_replace($server_path, '', $path);
        $url = $protocol . '://' . $ip . $folder;
    }

    $url = str_replace('\\', '/', $url);
    return $url;
}

function testFile($file) {
    if (is_dir($file)) {
        $ErrorData = [
            'error' => $file . ' is a path! Frames need to be PNG, Fonts need to be ttf!',
        ];
        logError($ErrorData);
        return false;
    }

    if (!file_exists($file)) {
        $ErrorData = [
            'error' => $file . ' does not exist!',
        ];
        logError($ErrorData);
        return false;
    }
    return true;
}
