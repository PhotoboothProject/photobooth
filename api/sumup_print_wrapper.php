<?php

/**
 * Photobooth SumUp Print Wrapper
 * Erlaubt den Druckaufruf über die Kommandozeile unter Umgehung der Browser-Sicherheitschecks.
 */

// Sicherheitscheck: Script darf NUR über die Konsole (CLI) ausgeführt werden
if (php_sapi_name() !== 'cli') {
    die('Fehler: Dieser Wrapper darf nur vom System (CLI) gestartet werden.' . PHP_EOL);
}

// Parameter aus der Kommandozeile auslesen ($argv[0] ist der Scriptname)
$filename = $argv[1] ?? '';
$copies   = $argv[2] ?? 1;

if (empty($filename)) {
    die('Fehler: Kein Dateiname angegeben. Nutzung: php sumup_print_wrapper.php DATEINAME KOPIEN' . PHP_EOL);
}

// 1. Umgebungsvariablen für print.php simulieren
// Wir setzen diese, BEVOR irgendwelche Header oder Sessions geladen werden.
$_GET['filename'] = $filename;
$_GET['copies']   = (int)$copies;
$_GET['csrf']     = 'sumup_internal_bypass';

// 2. Session manuell starten und den Bypass-Token injizieren
// Das verhindert "Invalid CSRF Token" Fehler in der print.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['csrf'] = 'sumup_internal_bypass';

/**
 * 3. Die originale print.php laden.
 * Durch das 'require_once' wird der gesamte Workflow der Photobooth ausgeführt:
 * - Laden der Konfiguration und Bild-Bibliotheken
 * - Rotation (falls nötig)
 * - Anwendung von Rahmen (Frame)
 * - Generierung und Einbetten des QR-Codes
 * - Speichern der neuen Datei im /data/print/ Ordner mit Unique-Hash
 * - Ausführung des Druckbefehls (lp)
 */
require_once 'print.php';

echo PHP_EOL . "Druckauftrag für $filename erfolgreich an print.php übergeben." . PHP_EOL;
