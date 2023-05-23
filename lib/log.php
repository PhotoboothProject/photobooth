<?php
require_once __DIR__ . '/config.php';

class DataLogger {
    public $logFile;
    public $logData = [];

    public function __construct($logFile) {
        $this->logFile = $logFile;
    }

    public function addLogData($data) {
        $this->logData[] = $data;
    }

    public function getLogData() {
        return $this->logData;
    }

    public function logToFile($data = null) {
        if ($data === null) {
            $data = $this->getLogData();
        }

        $logFile = $this->logFile;
        $fileData = date('Y-m-d H:i:s') . ':' . PHP_EOL;

        foreach ($data as $entry) {
            if (is_array($entry)) {
                $formattedEntry = '';
                foreach ($entry as $key => $value) {
                    if (is_array($value)) {
                        $formattedValue = implode(', ', $value);
                    } elseif (is_bool($value)) {
                        $formattedValue = $value ? 'true' : 'false';
                    } else {
                        $formattedValue = $value;
                    }
                    $formattedEntry .= "'$key' : $formattedValue, ";
                }
                $formattedEntry = rtrim($formattedEntry, ', ');
                $fileData .= rtrim($formattedEntry) . PHP_EOL;
            } else {
                $fileData .= $entry . PHP_EOL;
            }
        }

        if (is_file($logFile)) {
            $fileData .= file_get_contents($logFile);
        }

        file_put_contents($logFile, $fileData);
        $this->logData = [];
    }

    public function logErrorAndDie($errormsg) {
        try {
            $errorData = [
                'error' => $errormsg,
            ];
            $errorString = json_encode($errorData);
            self::logToFile($errorData);
            die($errorString);
        } catch (Exception $e) {
            $errorData = [
                'error' => $e->getMessage(),
            ];
            $errorString = json_encode($errorData);
            die($errorString);
        }
    }
}
