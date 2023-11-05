<?php

namespace Photobooth\Service;

class MailService
{
    public string $databaseFile = '';

    public function __construct(string $databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    public function addRecipientToDatabase(string $recipient): void
    {
        if (!file_exists($this->databaseFile)) {
            $addresses = [];
        } else {
            $addresses = json_decode(file_get_contents($this->databaseFile));
        }
        if (!in_array($recipient, $addresses)) {
            $addresses[] = $recipient;
        }
        file_put_contents($this->databaseFile, json_encode($addresses));
    }

    public function resetDatabase(): void
    {
        if (is_file($this->databaseFile)) {
            unlink($this->databaseFile);
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
