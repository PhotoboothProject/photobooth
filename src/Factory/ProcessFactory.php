<?php

namespace Photobooth\Factory;

use Photobooth\Dto\Process;

class ProcessFactory
{
    public static function fromConfig(array $config): Process
    {
        $process = new Process();
        $process->name = (string) $config['name'];
        $process->command = (string) $config['command'];
        $process->enabled = (bool) $config['enabled'];

        return $process;
    }
}
