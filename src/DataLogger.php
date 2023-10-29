<?php

namespace Photobooth;

class DataLogger
{
    /** @var string The path to the log file. */
    public $logFile;

    /** @var array The data to be logged. */
    public $logData = [];

    /**
     * DataLogger constructor.
     *
     * @param string $logFile The path to the log file.
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Adds data to the log data array.
     *
     * @param mixed $data The data to be added to the log.
     * @return void
     */
    public function addLogData($data)
    {
        $this->logData[] = $data;
    }

    /**
     * Retrieves the log data.
     *
     * @return array The log data.
     */
    public function getLogData()
    {
        return $this->logData;
    }

    /**
     * Logs data to a file.
     *
     * @param array|null $data The data to log. If null, retrieves the log data from the instance.
     * @return void
     */
    public function logToFile($data = null)
    {
        if ($data === null) {
            $data = $this->getLogData();
        }

        $logFile = $this->logFile;
        $fileData = date('Y-m-d H:i:s') . ':' . PHP_EOL;

        $fileData .= self::formatLogData($data);

        if (is_file($logFile)) {
            $fileData .= file_get_contents($logFile);
        }

        file_put_contents($logFile, $fileData);
        $this->logData = [];
    }

    /**
     * Format the log data.
     *
     * @param array $data The log data to format.
     * @param int $indentLevel The current indentation level.
     * @return string The formatted log data.
     */
    private static function formatLogData($data, $indentLevel = 0)
    {
        $fileData = '';

        foreach ($data as $entry) {
            $formattedEntry = self::formatArrayEntry($entry, $indentLevel);
            $fileData .= self::indent($formattedEntry, $indentLevel) . PHP_EOL;
        }

        return $fileData;
    }

    /**
     * Format an array entry.
     *
     * @param array $entry The array entry to format.
     * @param int $indentLevel The current indentation level.
     * @return string The formatted array entry.
     */
    private static function formatArrayEntry($entry, $indentLevel)
    {
        $formattedEntry = '';

        foreach ($entry as $key => $value) {
            if (is_array($value)) {
                $formattedValue = self::formatArrayEntry($value, $indentLevel + 1);
                $formattedEntry .= self::indent($formattedValue, $indentLevel + 1) . PHP_EOL;
            } else {
                $formattedEntry .= self::indent("'{$key}' : {$value}", $indentLevel + 1) . PHP_EOL;
            }
        }

        return $formattedEntry;
    }

    /**
     * Indent a string with spaces based on the indentation level.
     *
     * @param string $string The string to indent.
     * @param int $indentLevel The indentation level.
     * @return string The indented string.
     */
    private static function indent($string, $indentLevel)
    {
        $indentation = str_repeat('    ', $indentLevel);
        return $indentation . $string;
    }

    /**
     * Logs an error message and terminates the script execution.
     *
     * @param string $errormsg The error message to log.
     * @return void
     */
    public function logErrorAndDie($errormsg)
    {
        try {
            $errorData = [
                'error' => $errormsg,
            ];
            $errorString = json_encode($errorData);
            self::logToFile($errorData);
            die($errorString);
        } catch (\Exception $e) {
            $errorData = [
                'error' => $e->getMessage(),
            ];
            $errorString = json_encode($errorData);
            die($errorString);
        }
    }
}
