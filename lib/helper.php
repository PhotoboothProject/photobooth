<?php
require_once __DIR__ . '/log.php';

/**
 * The Photobooth class holds information about the server and Photobooth installation.
 */
class Photobooth {
    /** @var string $server_ip The IP address of the server. */
    public $server_ip;
    /** @var string $os The operating system of the server. */
    public $os;
    /** @var string $webRoot The web root directory of the server. */
    public $webRoot;
    /** @var string $photoboothRoot The root directory of the Photobooth installation. */
    public $photoboothRoot;
    /** @var bool $isSubfolderInstall Whether the Photobooth installation is in a subfolder. */
    public $isSubfolderInstall;
    /** @var string $version The version of the Photobooth installation. */
    public $version;

    /**
     * Photobooth constructor.
     */
    function __construct() {
        $this->server_ip = $this->get_ip();
        $this->os = $this->server_os();
        $this->webRoot = $this->get_web_root();
        $this->photoboothRoot = Helper::get_rootpath();
        $this->isSubfolderInstall = $this->detect_subfolder_install();
        $this->version = $this->get_photobooth_version();
    }

    /**
     * Returns the operating system of the server.
     *
     * @return string The operating system of the server.
     */
    public static function server_os() {
        return DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
    }

    /**
     * Returns the IP address of the server.
     *
     * @return string The IP address of the server.
     */
    public static function get_ip() {
        return self::server_os() == 'linux' ? shell_exec('hostname -I | cut -d " " -f 1') : $_SERVER['HTTP_HOST'];
    }

    /**
     * Returns the web root directory of the server.
     *
     * @return string The web root directory of the server.
     */
    public static function get_web_root() {
        return self::server_os() == 'linux' ? $_SERVER['DOCUMENT_ROOT'] : str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

    /**
     * Get the version number of the installed photobooth software.
     *
     * @return string The version number of the installed photobooth software, or "unknown" if the version cannot be determined.
     * @throws Exception If the package.json file cannot be found or cannot be decoded.
     */
    public function get_photobooth_version() {
        $packageJsonPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'package.json';
        if (!is_file($packageJsonPath)) {
            throw new Exception('Package file not found.');
        }
        $packageContent = file_get_contents($packageJsonPath);
        $package = json_decode($packageContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding package file: ' . json_last_error_msg());
        }
        return $package['version'] ?? 'unknown';
    }

    /**
     * Get the version number of the latest release of the photobooth software on GitHub.
     *
     * @return string The version number of the latest release of the photobooth software.
     * @throws Exception If the latest release cannot be fetched from the GitHub API or the data returned is invalid.
     */
    public function getLatestRelease() {
        $gh = 'PhotoboothProject';
        $url = 'https://api.github.com/repos/' . $gh . '/photobooth/releases/latest';
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: $gh/photobooth\r\n",
            ],
        ];

        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new Exception('Failed to fetch latest release from GitHub API');
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['tag_name'])) {
            throw new Exception('Invalid data returned from GitHub API');
        }

        $remoteVersion = substr($data['tag_name'], 1);
        return $remoteVersion;
    }

    /**
     * Check whether an update to the photobooth software is available.
     *
     * @return bool Whether an update is available or not.
     */
    public function checkUpdate() {
        try {
            $remoteVersion = $this->getLatestRelease();
            $localVersion = $this->get_photobooth_version();
            $updateAvailable = $localVersion != $remoteVersion;

            return $updateAvailable;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Detects whether the Photobooth installation is in a subfolder.
     *
     * @return bool Whether the Photobooth installation is in a subfolder.
     */
    public static function detect_subfolder_install() {
        return empty(Helper::get_rootpath()) ? false : true;
    }

    /**
     * Returns the URL of the Photobooth installation.
     *
     * @return string The URL of the Photobooth installation.
     */
    public function get_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $url = $protocol . '://' . $this->server_ip;
        if ($this->isSubfolderInstall) {
            $url .= Helper::set_absolute_path(Helper::get_rootpath());
        }
        return Helper::fix_seperator($url);
    }
}

/**
 * A collection of helper functions used throughout the photobooth application.
 */
class Helper {
    /**
     * Get the relative path of a file or directory.
     *
     * @param string $relative_path The path to the file or directory relative to the application root.
     *
     * @return string The relative path of the file or directory.
     */
    public static function get_rootpath($relative_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) {
        return str_replace(Photobooth::get_web_root(), '', realpath($relative_path));
    }

    /**
     * Fix path separators to use forward slashes instead of backslashes.
     *
     * @param string $fix_path The path to be fixed.
     *
     * @return string The fixed path.
     */
    public static function fix_seperator($fix_path) {
        return str_replace('\\', '/', $fix_path);
    }

    /**
     * Set an absolute path by adding a leading slash if necessary.
     *
     * @param string $path The path to be set as absolute.
     *
     * @return string The absolute path.
     */
    public static function set_absolute_path($path) {
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        return $path;
    }
}

function testFile($file) {
    if (is_dir($file)) {
        $ErrorData = [
            'error' => $file . ' is a path! Frames need to be PNG, Fonts need to be ttf!',
        ];
        logError($ErrorData);
        return false;
    }

    if (!file_exists($file)) {
        $ErrorData = [
            'error' => $file . ' does not exist!',
        ];
        logError($ErrorData);
        return false;
    }
    return true;
}
