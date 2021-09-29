<?php

function getrootpath($relative_path) {
    $realpath = realpath($relative_path);
    $rootpath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $realpath);

    return $rootpath;
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
