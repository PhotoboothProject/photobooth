<?php
require_once __DIR__ . '/config.php';

class PrintManager {
    /**
     * @var string Path to the print database file.
     */
    public $printDb = '';

    /**
     * @var string Path to the print counter file.
     */
    public $printCounter = '';

    /**
     * @var string Path to the print lock file.
     */
    public $printLockFile = '';

    /**
     * Add a new entry to the print database.
     *
     * @param string $filename The filename associated with the print.
     * @param string $uniquename The unique name associated with the print.
     * @return bool True on success, false on failure.
     */
    public function addToPrintDb($filename, $uniquename) {
        $csvData = [];
        $csvData[] = date('Y-m-d');
        $csvData[] = date('H:i:s');
        $csvData[] = $filename;
        $csvData[] = $uniquename;
        $handle = fopen($this->printDb, 'a');
        if (!$handle) {
            return false;
        }
        fputcsv($handle, $csvData);
        fclose($handle);
        return true;
    }

    /**
     * Get the total count of prints from the print database.
     *
     * @return int|bool The total count of prints on success, false on failure.
     */
    public function getPrintCountFromDB() {
        if (file_exists($this->printDb) && is_readable($this->printDb)) {
            $handle = fopen($this->printDb, 'r');
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

    /**
     * Get the total count of prints from either the print counter file or the print database.
     *
     * @return string|bool The total count of prints on success, false on failure.
     */
    public function getPrintCountFromCounter() {
        if (file_exists($this->printCounter)) {
            return file_get_contents($this->printCounter);
        }
        return $this->getPrintCountFromDB();
    }

    /**
     * Check if printing is currently locked.
     *
     * @return bool True if printing is locked, false otherwise.
     */
    public function isPrintLocked() {
        return file_exists($this->printLockFile);
    }

    /**
     * Lock the printing system.
     *
     * @return bool True on success, false on failure.
     */
    public function lockPrint() {
        $handle = fopen($this->printLockFile, 'w');
        if (!$handle) {
            return false;
        }
        fclose($handle);
        return true;
    }

    /**
     * Unlock the printing system.
     *
     * @return bool True on success, false on failure.
     */
    public function unlockPrint() {
        if (file_exists($this->printLockFile) && unlink($this->printLockFile)) {
            return true;
        }
        return false;
    }

    /**
     * Remove the print database file.
     *
     * @return bool True on success, false on failure.
     */
    public function removePrintDb() {
        if (file_exists($this->printDb) && unlink($this->printDb)) {
            return true;
        }
        return false;
    }

    /**
     * Remove the print counter file.
     *
     * @return bool True on success, false on failure.
     */
    public function removePrintCounter() {
        if (file_exists($this->printCounter) && unlink($this->printCounter)) {
            return true;
        }
        return false;
    }

    /**
     * Reset the print system by removing the print database, unlocking print, and removing the print counter.
     *
     * @return void
     */
    public function resetPrint() {
        $this->removePrintDb();
        $this->unlockPrint();
        $this->removePrintCounter();
    }
}
