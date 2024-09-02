<?php

namespace Photobooth\Service;

use Photobooth\Logger\NamedLogger;

class LoggerService
{
    /**
     * @var array<string, NamedLogger> $channels
     */
    protected array $channels = [];
    protected int $level;

    public function __construct()
    {
        $config = ConfigurationService::getInstance()->getConfiguration();
        $this->level = $config['dev']['loglevel'] ?? 0;
        $this->channels['default'] = new NamedLogger('default', $this->level);
    }

    public function addLogger(string $name = 'default'): self
    {
        if (!array_key_exists($name, $this->channels)) {
            $this->channels[$name] = new NamedLogger($name, $this->level);
        }

        return $this;
    }

    public function getLogger(string $name = 'default'): NamedLogger
    {
        if (!array_key_exists($name, $this->channels)) {
            $this->channels[$name] = new NamedLogger($name, $this->level);
        }

        return $this->channels[$name];
    }

    public function reset(): void
    {
        foreach ($this->channels as $logger) {
            $logger->close();
            $fileName = $logger->getFile();
            if (file_exists($fileName)) {
                unlink($logger->getFile());
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
