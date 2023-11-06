<?php

namespace Photobooth\Service;

class MailService
{
    public string $databaseFile = '';

    public function __construct(string $databaseFile)
    {
        if (!$this->isValidDatabasePath($databaseFile)) {
            throw new \Exception('Database or path is not writable: ' . $databaseFile);
        }
        $this->databaseFile = $databaseFile;
    }

    private function isValidDatabasePath(string $path): bool
    {
        if (is_file($path)) {
            return is_writable($path);
        } else {
            return is_writable(dirname($path));
        }
    }

    private function loadDatabase(): array
    {
        if (is_file($this->databaseFile)) {
            $data = file_get_contents($this->databaseFile);
            $addresses = json_decode($data, true);
            if ($addresses === null) {
                throw new \Exception('Failed to decode the database ' . $this->databaseFile);
            }
        } else {
            $addresses = [];
        }

        return $addresses;
    }

    public function addRecipientToDatabase(string $recipient): void
    {
        $addresses = $this->loadDatabase();

        if (!in_array($recipient, $addresses)) {
            $addresses[] = $recipient;
            $this->saveDatabase($addresses);
        }
    }

    private function saveDatabase(array $addresses): void
    {
        $data = json_encode($addresses);
        if (file_put_contents($this->databaseFile, $data) === false) {
            throw new \Exception('Failed to save the database ' . $this->databaseFile);
        }
    }

    public function resetDatabase(): void
    {
        if (is_file($this->databaseFile)) {
            if (!unlink($this->databaseFile)) {
                throw new \Exception('Failed to reset the database ' . $this->databaseFile);
            }
        }
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}
