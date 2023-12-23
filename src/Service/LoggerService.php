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

    public function __construct(int $level)
    {
        $this->level = $level;
        $this->channels['default'] = new NamedLogger('default', $this->level);
    }

    public function getLogger(string $name = 'default'): NamedLogger
    {
        if (!array_key_exists($name, $this->channels)) {
            $this->channels[$name] = new NamedLogger($name, $this->level);
        }

        return $this->channels[$name];
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}
