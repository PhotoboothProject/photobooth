<?php

namespace Photobooth;

/**
 * The Photobooth class holds information about the server and Photobooth installation.
 */
class Photobooth
{
    /** @var string $serverIp The IP address of the server. */
    public $serverIp;
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
    public function __construct()
    {
        $this->serverIp = $this->getIp();
        $this->os = $this->serverOs();
        $this->webRoot = $this->getWebRoot();
        $this->photoboothRoot = Helper::getRootpath();
        $this->isSubfolderInstall = $this->detectSubfolderInstall();
        $this->version = $this->getPhotoboothVersion();
    }

    /**
     * Returns the operating system of the server.
     *
     * @return string The operating system of the server.
     */
    public static function serverOs()
    {
        return DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
    }

    /**
     * Returns the IP address of the server.
     *
     * @return string The IP address of the server.
     */
    public static function getIp()
    {
        return self::serverOs() == 'linux' ? shell_exec('hostname -I | cut -d " " -f 1') : $_SERVER['HTTP_HOST'];
    }

    /**
     * Returns the web root directory of the server.
     *
     * @return string The web root directory of the server.
     */
    public static function getWebRoot()
    {
        return self::serverOs() == 'linux' ? $_SERVER['DOCUMENT_ROOT'] : str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

    /**
     * Get the version number of the installed photobooth software.
     *
     * @return string The version number of the installed photobooth software, or "unknown" if the version cannot be determined.
     * @throws Exception If the package.json file cannot be found or cannot be decoded.
     */
    public function getPhotoboothVersion()
    {
        $packageJsonPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '/../package.json';
        if (!is_file($packageJsonPath)) {
            throw new \Exception('Package file not found.');
        }
        $packageContent = file_get_contents($packageJsonPath);
        $package = json_decode($packageContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding package file: ' . json_last_error_msg());
        }
        return $package['version'] ?? 'unknown';
    }

    /**
     * Get the version number of the latest release of the photobooth software on GitHub.
     *
     * @return string The version number of the latest release of the photobooth software.
     * @throws Exception If the latest release cannot be fetched from the GitHub API or the data returned is invalid.
     */
    public function getLatestRelease()
    {
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
            throw new \Exception('Failed to fetch latest release from GitHub API');
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['tag_name'])) {
            throw new \Exception('Invalid data returned from GitHub API');
        }

        $remoteVersion = substr($data['tag_name'], 1);
        return $remoteVersion;
    }

    /**
     * Check whether an update to the photobooth software is available.
     *
     * @return bool Whether an update is available or not.
     */
    public function checkUpdate()
    {
        try {
            $remoteVersion = $this->getLatestRelease();
            $localVersion = $this->getPhotoboothVersion();
            $updateAvailable = $localVersion != $remoteVersion;

            return $updateAvailable;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Detects whether the Photobooth installation is in a subfolder.
     *
     * @return bool Whether the Photobooth installation is in a subfolder.
     */
    public static function detectSubfolderInstall()
    {
        return empty(Helper::getRootpath()) ? false : true;
    }

    /**
     * Returns the URL of the Photobooth installation.
     *
     * @return string The URL of the Photobooth installation.
     */
    public function getUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $url = $protocol . '://' . $this->serverIp;
        if ($this->isSubfolderInstall) {
            $url .= Helper::setAbsolutePath(Helper::getRootpath());
        }
        return Helper::fixSeperator($url);
    }
}
