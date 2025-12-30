<?php

namespace Photobooth\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Photobooth\Utility\PathUtility;
use Psr\Log\LoggerInterface;

class NamedLogger implements LoggerInterface
{
    protected int $level;
    protected string $file;
    protected Logger $logger;

    public function __construct(string $name, int $level)
    {
        $this->level = $level;
        $this->file = PathUtility::getAbsolutePath('var/log/' . $name . '.log');

        switch ($this->level) {
            case 1:
                $logLevel = Level::Info;
                break;
            case 2:
                $logLevel = Level::Debug;
                break;
            default:
                $logLevel = Level::Error;
                break;
        }

        $dateFormat = 'Y-m-d H:i:s';
        $output = "[%datetime%][%channel%][%level_name%] %message% %context%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler($this->file, $logLevel);
        $stream->setFormatter($formatter);

        $this->logger = new Logger($name);
        $this->logger->pushHandler($stream);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function close(): void
    {
        $this->logger->close();
    }
}
