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
        if (!empty($data)) {
            $logFile = $this->logFile;
            $fileData = date('c') . ":\n" . print_r($data, true) . "\n";
            if (is_file($logFile)) {
                $fileData .= file_get_contents($logFile);
            }
            file_put_contents($logFile, $fileData);
            $this->logData = [];
        }
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

function logError($data) {
    global $config;
    $logfile = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['dev']['logfile'];

    $file_data = date('c') . ":\n" . print_r($data, true) . "\n";
    if (is_file($logfile)) {
        $file_data .= file_get_contents($logfile);
    }
    file_put_contents($logfile, $file_data);
}

function logErrorAndDie($errormsg) {
    $ErrorData = [
        'error' => $errormsg,
    ];
    $ErrorString = json_encode($ErrorData);
    logError($ErrorData);
    die($ErrorString);
}
