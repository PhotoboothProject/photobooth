<?php
/**
 * Class HealthCheck
 *
 * Performs a health check on the PHP environment.
 */
class HealthCheck {
    /**
     * @var bool Indicates the health status of the PHP environment.
     */
    public $healthStatus = false;

    /**
     * @var int The major version number of PHP.
     */
    public $phpMajor = 0;

    /**
     * @var int The minor version number of PHP.
     */
    public $phpMinor = 0;

    /**
     * @var bool Indicates whether the GD extension is enabled.
     */
    public $gdEnabled = false;

    /**
     * @var bool Indicates whether the ZIP extension is enabled.
     */
    public $zipEnabled = false;

    /**
     * HealthCheck constructor.
     *
     * Initializes the HealthCheck object and performs the health check.
     */
    function __construct() {
        list($this->phpMajor, $this->phpMinor) = $this->phpVersion();
        $this->gdEnabled = extension_loaded('gd');
        $this->zipEnabled = extension_loaded('zip');
        if (
            ($this->phpMajor >= 8 || (function_exists('str_contains') && function_exists('str_ends_with') && function_exists('str_starts_with'))) &&
            $this->gdEnabled &&
            $this->zipEnabled
        ) {
            $this->healthStatus = true;
        }
    }

    /**
     * Retrieves the major and minor versions of PHP.
     *
     * @return int[] An array containing the major and minor versions of PHP.
     */
    public static function phpVersion() {
        try {
            return [\PHP_MAJOR_VERSION, \PHP_MINOR_VERSION];
        } catch (Exception $e) {
            return [0, 0];
        }
    }
}
