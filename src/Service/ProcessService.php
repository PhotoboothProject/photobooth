<?php

namespace Photobooth\Service;

use Photobooth\Dto\Process;
use Photobooth\Factory\ProcessFactory;
use Photobooth\Utility\PathUtility;

class ProcessService
{
    /**
     * @var Process[]
     */
    protected array $processes = [];

    public function __construct()
    {
        $config = ConfigurationService::getInstance()->getConfiguration();
        $this->processes = [
            ProcessFactory::fromConfig([
                'name' => 'remotebuzzer',
                'command' => $config['commands']['nodebin'] . ' ' . PathUtility::getAbsolutePath('resources/js/remotebuzzer-server.js'),
                'enabled' => ($config['remotebuzzer']['startserver'] && ($config['remotebuzzer']['usebuttons'] || $config['remotebuzzer']['userotary'])),
                'killSignal' => 9,
            ]),
            ProcessFactory::fromConfig([
                'name' => 'synctodrive',
                'command' => $config['commands']['nodebin'] . ' ' . PathUtility::getAbsolutePath('resources/js/sync-to-drive.js'),
                'enabled' => ($config['synctodrive']['enabled']),
                'killSignal' => 15,
            ])
        ];
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
                    posix_kill((int) $processId, $process->killSignal);
                }
                unlink($processIdFile);
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
