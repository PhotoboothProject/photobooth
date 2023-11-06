<?php

namespace Photobooth\Service;

use Photobooth\Dto\Process;
use Photobooth\Utility\PathUtility;

class ProcessService
{
    /**
     * @var Process[]
     */
    protected array $processes = [];

    /**
     * @param Process[] $processes
     */
    public function __construct(array $processes)
    {
        $this->processes = $processes;
    }

    public function boot(): void
    {
        foreach ($this->processes as $process) {
            $this->start($process);
        }
    }

    public function shutdown(): void
    {
        foreach ($this->processes as $process) {
            $this->stop($process);
        }
    }

    protected function processIsRunning(Process $process): bool
    {
        $processIdFile = PathUtility::getAbsolutePath('var/run/' . $process->name . '.pid');
        if (file_exists($processIdFile)) {
            exec('pgrep -F ' . $processIdFile, $output, $return);
            if ($return == 0) {
                // process is active
                return true;
            }
            // remove stale PID file
            unlink($processIdFile);
        }

        return false;
    }

    protected function start(Process $process): void
    {
        if ($process->enabled) {
            $logger = LoggerService::getInstance()->getLogger($process->name);
            if (!$this->processIsRunning($process)) {
                if ($logger->getLevel() > 0) {
                    $logfile = $logger->getFile();
                } else {
                    $logfile = '/dev/null';
                }

                $dir = PathUtility::getRootPath();
                $cmd = $process->command . ' >> ' . $logfile . ' &';
                $logger->debug('Starting Process', ['dir' => $dir, 'cmd' => $cmd]);
                chdir($dir);
                exec($cmd);
            } else {
                $logger->debug('Process already started');
            }
        }
    }

    protected function stop(Process $process): void
    {
        $logger = LoggerService::getInstance()->getLogger($process->name);
        $processIdFile = PathUtility::getAbsolutePath('var/run/' . $process->name . '.pid');
        if (file_exists($processIdFile)) {
            exec('pgrep -F ' . $processIdFile, $output, $return);
            if ($return == 0) {
                foreach ($output as $processId) {
                    $logger->debug('Config has changed, killed process ' . $process->name . ' -> PID ' . $processId);
                    posix_kill($processId, 9);
                }
                unlink($processIdFile);
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
