<?php

namespace Photobooth;

use Photobooth\Enum\FolderEnum;

/**
 * Class PhotoboothCaptureTest
 */
class PhotoboothCaptureTest
{
    public string $fileName;
    public string $tmpFolder;
    public string $tmpFile;
    public array $logData = [];
    public array $captureCmds = [
        'gphoto2 --capture-image-and-download --filename=%s',
        'gphoto2 --set-config output=Off --capture-image-and-download --filename=%s',
        'gphoto2 --trigger-capture --wait-event-and-download=FILEADDED --filename=%s',
        'gphoto2 --set-config output=Off --trigger-capture --wait-event-and-download=FILEADDED --filename=%s',
        'gphoto2 --wait-event=300ms --capture-image-and-download --filename=%s',
        'gphoto2 --set-config output=Off --wait-event=300ms --capture-image-and-download --filename=%s',
    ];

    /**
     * PhotoboothCaptureTest constructor.
     */
    public function __construct()
    {
        $this->tmpFolder = FolderEnum::TEMP->absolute();
    }

    public function addLog(string $level, string $message, array $context = []): void
    {
        $this->logData[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }

    /**
     * Function to iterate through capture commands and execute them.
     */
    public function executeCaptureTests(): void
    {
        foreach ($this->captureCmds as $index => $command) {
            // Set filename for each test command
            $this->fileName = sprintf('test-%d.jpg', $index + 1);
            $this->tmpFile = $this->tmpFolder . DIRECTORY_SEPARATOR . $this->fileName;

            $this->addLog('debug', 'Executing Command #' . ($index + 1), ['command' => $command]);

            // Execute the command
            $this->executeCmd($command);
        }
    }

    /**
     * Function to execute a single command.
     *
     * @param string $command The command to execute
     */
    public function executeCmd(string $command): void
    {
        // Change directory if using gphoto command
        if (substr($command, 0, strlen('gphoto')) === 'gphoto') {
            chdir(dirname($this->tmpFile));
        }

        // Prepare the command and redirect stderr to stdout
        $cmd = sprintf($command, $this->tmpFile);
        $cmd .= ' 2>&1';
        $start_time = hrtime(true);
        exec($cmd, $output, $returnValue);

        // Handle command errors
        if ($returnValue) {
            $this->addLog('error', 'Command failed', [
                'command' => $command,
                'output' => $output,
                'returnValue' => $returnValue
            ]);
            return;
        } else {
            $this->addLog('info', 'Command executed successfully', [
                'command' => $command,
                'output' => $output
            ]);
        }

        // Wait for the file to be created, if necessary
        $i = 0;
        $processingTime = 300; // 30 seconds (300 * 100ms)
        while ($i < $processingTime) {
            if (file_exists($this->tmpFile)) {
                break;
            } else {
                $i++;
                usleep(100000); // Wait 100ms
            }
        }

        // If the file does not exist, print the error and proceed
        if (!file_exists($this->tmpFile)) {
            $this->addLog('error', 'File was not created', [
                'command' => $command,
                'output' => $output,
                'returnValue' => $returnValue
            ]);
        }
        $end_time = hrtime(true);
        $execution_time = $end_time - $start_time;
        $execution_time_in_seconds = $execution_time / 1e9;
        $this->addLog('info', 'Execution time', [
            'output' => $execution_time_in_seconds . ' seconds'
        ]);
    }
}
