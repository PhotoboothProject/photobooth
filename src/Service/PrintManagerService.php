<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;

class PrintManagerService
{
    /**
     * Path to the print database file.
     */
    public string $printDb = '';

    /**
     * Path to the print counter file.
     */
    public string $printCounter = '';

    /**
     * Path to the print lock file.
     */
    public string $printLockFile = '';

    public function __construct()
    {
        $this->printDb = FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . 'printed.csv';
        $this->printCounter = FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . 'print.count';
        $this->printLockFile = FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . 'print.lock';
    }

    /**
     * Add a new entry to the print database.
     *
     * @param string $filename The filename associated with the print.
     * @param string $uniquename The unique name associated with the print.
     */
    public function addToPrintDb(string $filename, string $uniquename): bool
    {
        try {
            $csvData = [];
            $csvData[] = date('Y-m-d');
            $csvData[] = date('H:i:s');
            $csvData[] = $filename;
            $csvData[] = $uniquename;
            $handle = fopen($this->printDb, 'a');
            if (!$handle) {
                throw new \Exception('Failed to open print database.');
            }
            if (fputcsv($handle, $csvData) === false) {
                throw new \Exception('Failed to write to print database.');
            }
            fclose($handle);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the total count of prints from the print database.
     */
    public function getPrintCountFromDB(): ?int
    {
        try {
            if (file_exists($this->printDb) && is_readable($this->printDb)) {
                $handle = fopen($this->printDb, 'r');
                if (!$handle) {
                    throw new \Exception('Failed to open print database.');
                }
                $linecount = 0;
                while (!feof($handle)) {
                    $line = (string) fgets($handle, 4096);
                    $linecount += substr_count($line, PHP_EOL);
                }
                fclose($handle);
                return $linecount;
            }
            return intval(0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the total count of prints from either the print counter file or the print database.
     */
    public function getPrintCountFromCounter(): ?int
    {
        try {
            if (file_exists($this->printCounter)) {
                $counterContent = file_get_contents($this->printCounter);
                if ($counterContent === false) {
                    throw new \Exception('Failed to read print counter.');
                }
                return (int) $counterContent;
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return $this->getPrintCountFromDB();
    }

    /**
     * Check if printing is currently locked.
     */
    public function isPrintLocked(): bool
    {
        return file_exists($this->printLockFile);
    }

    /**
     * Lock the printing system.
     */
    public function lockPrint(): bool
    {
        try {
            $handle = fopen($this->printLockFile, 'w');
            if (!$handle) {
                throw new \Exception('Failed to lock print.');
            }
            fclose($handle);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Unlock the printing system.
     */
    public function unlockPrint(): bool
    {
        try {
            if (file_exists($this->printLockFile) && unlink($this->printLockFile)) {
                return true;
            }
            throw new \Exception('Failed to unlock printing.');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove the print database file.
     */
    public function removePrintDb(): bool
    {
        try {
            if (file_exists($this->printDb) && unlink($this->printDb)) {
                return true;
            }
            throw new \Exception('Failed to remove print database.');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove the print counter file.
     */
    public function removePrintCounter(): bool
    {
        try {
            if (file_exists($this->printCounter) && unlink($this->printCounter)) {
                return true;
            }
            throw new \Exception('Failed to remove print counter.');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reset the print system by removing the print database, unlocking print, and removing the print counter.
     */
    public function resetPrint(): void
    {
        try {
            $this->removePrintDb();
            $this->unlockPrint();
            $this->removePrintCounter();
        } catch (\Exception $e) {
            return;
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
