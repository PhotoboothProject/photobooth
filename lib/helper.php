<?php
require_once __DIR__ . '/log.php';

class Photobooth {
    public $server_ip;
    public $os;
    public $webRoot;
    public $photoboothRoot;
    public $isSubfolderInstall;
    public $version;

    function __construct() {
        $this->server_ip = $this->get_ip();
        $this->os = $this->server_os();
        $this->webRoot = $this->get_web_root();
        $this->photoboothRoot = Helper::get_rootpath();
        $this->isSubfolderInstall = $this->detect_subfolder_install();
        $this->version = $this->get_photobooth_version();
    }

    public static function server_os() {
        return DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
    }

    public static function get_ip() {
        return self::server_os() == 'linux' ? shell_exec('hostname -I | cut -d " " -f 1') : $_SERVER['HTTP_HOST'];
    }

    public static function get_web_root() {
        return self::server_os() == 'linux' ? $_SERVER['DOCUMENT_ROOT'] : str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    }

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

    public static function detect_subfolder_install() {
        return empty(Helper::get_rootpath()) ? false : true;
    }

    public static function get_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $url = $protocol . '://' . $this->serverIp;
        if ($this->isSubfolderInstall) {
            $url .= Helper::set_absolute_path(Helper::get_rootpath());
        }
        return Helper::fix_seperator($url);
    }
}

class Helper {
    public static function get_rootpath($relative_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) {
        return str_replace(Photobooth::get_web_root(), '', realpath($relative_path));
    }

    public static function fix_seperator($fix_path) {
        return str_replace('\\', '/', $fix_path);
    }

    public static function set_absolute_path($path) {
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        return $path;
    }
}

class Image {
    public $newFilename;

    public static function create_new_filename($naming = 'random', $ext = '.jpg') {
        if ($naming === 'dateformatted') {
            $name = date('Ymd_His') . $ext;
        } else {
            $name = md5(microtime()) . $ext;
        }
        return $name;
    }

    function set_new_filename($naming) {
        $this->newFilename = $this->create_new_filename($naming);
    }

    function get_new_filename() {
        return $this->newFilename;
    }

    function set_and_get_new_filename($naming) {
        $this->set_new_filename($naming);
        return $this->newFilename;
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
