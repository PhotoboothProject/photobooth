<?php
require_once __DIR__ . '/log.php';
$os = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';

function isSubfolderInstall() {
    global $os;
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

    if ($os == 'linux') {
        $server_path = $_SERVER['DOCUMENT_ROOT'];
    } else {
        $server_path = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

    if ($path == $server_path) {
        return false;
    } else {
        return true;
    }
}

function getrootpath($relative_path) {
    global $os;
    $realpath = realpath($relative_path);

    if ($os == 'linux') {
        $server_path = $_SERVER['DOCUMENT_ROOT'];
    } else {
        $server_path = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }
    $rootpath = str_replace($server_path, '', $realpath);

    return $rootpath;
}

function fixSeperator($fix_path) {
    $fixedPath = str_replace('\\', '/', $fix_path);
    return $fixedPath;
}

function getPhotoboothIp() {
    global $os;

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
    global $os;
    $path = getrootpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

    if ($os == 'linux') {
        $server_path = $_SERVER['DOCUMENT_ROOT'];
    } else {
        $server_path = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

    if ($path == $server_path) {
        return false;
    } else {
        $folder = str_replace($server_path, '', $path);
        return $folder;
    }
}

function getPhotoboothUrl() {
    global $os;

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    if ($os == 'linux') {
        $server_path = $_SERVER['DOCUMENT_ROOT'];
    } else {
        $server_path = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

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
