<?php

namespace Photobooth\Utility;

use Photobooth\Service\LoggerService;

class ProcessUtility
{
    public static function startProcess(string $processName, string $command): void
    {
        $logger = LoggerService::getInstance()->getLogger($processName);
        if (!self::processIsRunning($processName)) {
            if ($logger->getLevel() > 0) {
                $logfile = $logger->getFile();
            } else {
                $logfile = '/dev/null';
            }

            $dir = PathUtility::getRootPath();
            $cmd = $command . ' >> ' . $logfile . ' &';
            $logger->debug('Starting Process', ['dir' => $dir, 'cmd' => $cmd]);
            chdir($dir);
            exec($cmd);
        } else {
            $logger->debug('Process already started');
        }
    }

    public static function processIsRunning(string $processName): bool
    {
        $processIdFile = PathUtility::getAbsolutePath('var/run/' . $processName . '.pid');
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

    /**
     * Check PID file and if found, kill process and delete PID file
     */
    public static function killProcess(string $processName, int $signal): void
    {
        $logger = LoggerService::getInstance()->getLogger($processName);
        $processIdFile = PathUtility::getAbsolutePath('var/run/' . $processName . '.pid');
        if (file_exists($processIdFile)) {
            exec('pgrep -F ' . $processIdFile, $output, $return);
            if ($return == 0) {
                foreach ($output as $processId) {
                    $logger->debug('Config has changed, killed process ' . $processName . ' -> PID ' . $processId);
                    posix_kill((int) $processId, $signal);
                }
                unlink($processIdFile);
            }
        }
    }
}
