<?php
require_once __DIR__ . '/config.php';

function addToPrintDB($filename, $uniquename) {
    $csvData = [];
    $csvData[] = date('Y-m-d');
    $csvData[] = date('H:i:s');
    $csvData[] = $filename;
    $csvData[] = $uniquename;
    $handle = fopen(PRINT_DB, 'a');
    if (!$handle) {
        return false;
    }
    fputcsv($handle, $csvData);
    fclose($handle);
    return true;
}

function getPrintCountFromDB() {
    if (file_exists(PRINT_DB) && is_readable(PRINT_DB)) {
        $handle = fopen(PRINT_DB, 'r');
        if (!$handle) {
            return false;
        }
        $linecount = 0;
        while (!feof($handle)) {
            $line = fgets($handle, 4096);
            $linecount = $linecount + substr_count($line, PHP_EOL);
        }
        fclose($handle);
        return $linecount;
    }
    return false;
}

function getPrintCountFromCounter() {
    if (file_exists(PRINT_COUNTER)) {
        return file_get_contents(PRINT_COUNTER);
    }
    return getPrintCountFromDB();
}

function isPrintLocked() {
    if (file_exists(PRINT_LOCKFILE)) {
        return true;
    }
    return false;
}

function lockPrint() {
    $handle = fopen(PRINT_LOCKFILE, 'w');
    if (!$handle) {
        return false;
    }
    fclose($handle);
    return true;
}

function unlockPrint() {
    if (file_exists(PRINT_LOCKFILE) && unlink(PRINT_LOCKFILE)) {
        return true;
    }
    return false;
}

function removePrintDB() {
    if (file_exists(PRINT_DB) && unlink(PRINT_DB)) {
        return true;
    }
    return false;
}

function removePrintCounter() {
    if (file_exists(PRINT_COUNTER) && unlink(PRINT_COUNTER)) {
        return true;
    }
    return false;
}

function resetPrint() {
    removePrintDB();
    unlock_print();
    removePrintCounter();
}
