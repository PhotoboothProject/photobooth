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
        try {
            $csvData = [];
            $csvData[] = date('Y-m-d');
            $csvData[] = date('H:i:s');
            $csvData[] = $filename;
            $csvData[] = $uniquename;
            $handle = fopen($this->printDb, 'a');
            if (!$handle) {
                throw new Exception('Failed to open print database.');
            }
            if (fputcsv($handle, $csvData) === false) {
                throw new Exception('Failed to write to print database.');
            }
            fclose($handle);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the total count of prints from the print database.
     *
     * @return int|bool The total count of prints on success, false on failure.
     */
    public function getPrintCountFromDB() {
        try {
            if (file_exists($this->printDb) && is_readable($this->printDb)) {
                $handle = fopen($this->printDb, 'r');
                if (!$handle) {
                    throw new Exception('Failed to open print database.');
                }
                $linecount = 0;
                while (!feof($handle)) {
                    $line = fgets($handle, 4096);
                    $linecount += substr_count($line, PHP_EOL);
                }
                fclose($handle);
                return $linecount;
            }
            return intval(0);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the total count of prints from either the print counter file or the print database.
     *
     * @return string|bool The total count of prints on success, false on failure.
     */
    public function getPrintCountFromCounter() {
        try {
            if (file_exists($this->printCounter)) {
                $counterContent = file_get_contents($this->printCounter);
                if ($counterContent === false) {
                    throw new Exception('Failed to read print counter.');
                }
                return $counterContent;
            }
            return $this->getPrintCountFromDB();
        } catch (Exception $e) {
            return $this->getPrintCountFromDB();
        }
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
        try {
            $handle = fopen($this->printLockFile, 'w');
            if (!$handle) {
                throw new Exception('Failed to lock print.');
            }
            fclose($handle);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Unlock the printing system.
     *
     * @return bool True on success, false on failure.
     */
    public function unlockPrint() {
        try {
            if (file_exists($this->printLockFile) && unlink($this->printLockFile)) {
                return true;
            }
            throw new Exception('Failed to unlock printing.');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove the print database file.
     *
     * @return bool True on success, false on failure.
     */
    public function removePrintDb() {
        try {
            if (file_exists($this->printDb) && unlink($this->printDb)) {
                return true;
            }
            throw new Exception('Failed to remove print database.');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove the print counter file.
     *
     * @return bool True on success, false on failure.
     */
    public function removePrintCounter() {
        try {
            if (file_exists($this->printCounter) && unlink($this->printCounter)) {
                return true;
            }
            throw new Exception('Failed to remove print counter.');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Reset the print system by removing the print database, unlocking print, and removing the print counter.
     *
     * @return void
     */
    public function resetPrint() {
        try {
            $this->removePrintDb();
            $this->unlockPrint();
            $this->removePrintCounter();
        } catch (Exception $e) {
            return;
        }
    }
}
