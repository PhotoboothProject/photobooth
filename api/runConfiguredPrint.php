<?php

declare(strict_types=1);

/**
 * Photobooth Payment Print Script (CLI)
 * * Dieses Skript wird von den SumUp-Python-Skripten aufgerufen,
 * sobald eine Zahlung erfolgreich war. Es nutzt den in der
 * Photobooth-Konfiguration hinterlegten Druckbefehl.
 */

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LoggerService;
use Photobooth\Utility\PathUtility;

require_once dirname(__DIR__) . '/lib/boot.php';

// Sicherstellen, dass das Skript nur über die Konsole gestartet wird
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "ERROR: CLI only\n");
    exit(1);
}

// Argumente einlesen
$filename = $argv[1] ?? '';
$copies = (int)($argv[2] ?? 1);

if ($filename === '') {
    fwrite(STDERR, "ERROR: Missing filename\n");
    exit(1);
}

// Konfiguration laden
$config = ConfigurationService::getInstance()->getConfiguration();
$printCmd = (string)($config['commands']['print'] ?? '');

if ($printCmd === '') {
    fwrite(STDERR, "ERROR: commands.print is empty in Photobooth config\n");
    exit(1);
}

// Logger initialisieren (Schreibt in var/log/main.log)
$logger = LoggerService::getInstance()->getLogger('main');

// Pfad zum Bild auflösen
$resolvedFilename = $filename;
if (!str_starts_with($resolvedFilename, '/')) {
    $resolvedFilename = PathUtility::getAbsolutePath('data/print/' . ltrim($resolvedFilename, '/'));
}

// Druckbefehl vorbereiten (Platzhalter %s ersetzen)
$cmd = str_replace('%s', escapeshellarg($resolvedFilename), $printCmd);

$output = [];
$returnVar = 1;

// Logge den Start des Vorgangs
$logger->info('Payment Print: Starte System-Druckbefehl', [
    'file' => $resolvedFilename,
    'copies' => $copies
]);

// Befehl ausführen und Rückgabe sowie Fehler (2>&1) abfangen
exec($cmd . ' 2>&1', $output, $returnVar);

$outputString = implode(' ', $output);

// Ergebnis verarbeiten und loggen
if ($returnVar === 0) {
    // Erfolgreich (an das Betriebssystem übergeben)
    $logger->info('Payment Print: Druck erfolgreich ausgelöst', [
        'output' => $outputString
    ]);
} else {
    // Fehler beim Druckbefehl
    $logger->error('Payment Print: Fehler beim Ausführen des Druckbefehls', [
        'return_code' => $returnVar,
        'cmd' => $cmd,
        'output' => $outputString
    ]);
}

// Backup-Log in private/ (hilfreich für Standalone-Debugging)
$logFile = PathUtility::getAbsolutePath('private/payment-print.log');
$logEntry = sprintf(
    "[%s] File: %s | Return: %d | Output: %s\n",
    date('c'),
    $resolvedFilename,
    $returnVar,
    $outputString
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

exit($returnVar);
