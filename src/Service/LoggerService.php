<?php

namespace Photobooth\Service;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LoggerService
{
    protected Logger $logger;
    protected Level $level;

    public function __construct(string $filename, int $loglevel)
    {
        switch ($loglevel) {
            case 1:
                $this->level = Level::Info;
                break;
            case 2:
                $this->level = Level::Debug;
                break;
            default:
                $this->level = Level::Error;
                break;
        }

        $dateFormat = 'Y-m-d H:i:s';
        $output = "[%datetime%][%level_name%] %message% %context%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler($filename, $this->level);
        $stream->setFormatter($formatter);

        $this->logger = new Logger('photobooth');
        $this->logger->pushHandler($stream);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}
