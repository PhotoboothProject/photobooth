<?php

namespace Photobooth\Service;

use Photobooth\Enum\FolderEnum;

class MailService
{
    public string $databaseFile = '';

    public function __construct()
    {
        $config = ConfigurationService::getInstance()->getConfiguration();
        $databaseFile = FolderEnum::DATA->absolute() . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt';

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
            if ($data === false) {
                throw new \Exception('Failed to read the database ' . $this->databaseFile);
            }
            $addresses = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode the database ' . $this->databaseFile . ': ' . json_last_error_msg());
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
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
