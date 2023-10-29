<?php

use Photobooth\Environment;
use Photobooth\Photobooth;
use Photobooth\Helper;
use Photobooth\Utility\ArrayUtility;
use Photobooth\Utility\PathUtility;

$photobooth = new Photobooth();

$cmds = [
    'windows' => [
        'take_picture' => [
            'cmd' => 'digicamcontrol\CameraControlCmd.exe /capture /filename %s',
        ],
        'take_video' => [
            'cmd' => '',
        ],
        'take_custom' => [
            'cmd' => '',
        ],
        'print' => [
            'cmd' => 'rundll32 C:\WINDOWS\system32\shimgvw.dll,ImageView_PrintTo %s Printer_Name',
        ],
        'exiftool' => [
            'cmd' => '',
        ],
        'nodebin' => [
            'cmd' => '',
        ],
        'reboot' => [
            'cmd' => '',
        ],
        'shutdown' => [
            'cmd' => '',
        ],
    ],
    'linux' => [
        'take_picture' => [
            'cmd' => 'gphoto2 --capture-image-and-download --filename=%s',
        ],
        'take_video' => [
            'cmd' => 'python3 cameracontrol.py -v %s --vlen 3 --vframes 4',
        ],
        'take_custom' => [
            'cmd' =>
                'python3 cameracontrol.py --chromaImage=/var/www/html/resources/img/bg.jpg --chromaColor 00ff00 --chromaSensitivity 0.4 --chromaBlend 0.1 --capture-image-and-download --filename=%s',
        ],
        'print' => [
            'cmd' => 'lp -o landscape -o fit-to-page %s',
        ],
        'exiftool' => [
            'cmd' => 'exiftool -overwrite_original -TagsFromFile %s %s',
        ],
        'nodebin' => [
            'cmd' => '/usr/bin/node',
        ],
        'reboot' => [
            'cmd' => '/sbin/shutdown -r now',
        ],
        'shutdown' => [
            'cmd' => '/sbin/shutdown -h now',
        ],
    ],
];

$mailTemplates = [
    'de' => [
        'mail' => [
            'subject' => 'Hier ist dein Bild',
            'text' => 'Hey, dein Bild ist angehangen.',
        ],
    ],
    'en' => [
        'mail' => [
            'subject' => 'Here is your picture',
            'text' => 'Hey, your picture is attached.',
        ],
    ],
    'es' => [
        'mail' => [
            'subject' => 'Aquí está tu foto',
            'text' => 'Hola, tu foto está adjunta.',
        ],
    ],
    'fr' => [
        'mail' => [
            'subject' => 'Voici votre photo',
            'text' => 'Hé, ta photo est attachée.',
        ],
    ],
];

require_once PathUtility::getAbsolutePath('config/config.inc.php');

$operatingSystem = Environment::getOperatingSystem();
$config['take_picture']['cmd'] = $cmds[$operatingSystem]['take_picture']['cmd'];
$config['take_video']['cmd'] = $cmds[$operatingSystem]['take_video']['cmd'];
$config['print']['cmd'] = $cmds[$operatingSystem]['print']['cmd'];
$config['exiftool']['cmd'] = $cmds[$operatingSystem]['exiftool']['cmd'];
$config['nodebin']['cmd'] = $cmds[$operatingSystem]['nodebin']['cmd'];
$config['reboot']['cmd'] = $cmds[$operatingSystem]['reboot']['cmd'];
$config['shutdown']['cmd'] = $cmds[$operatingSystem]['shutdown']['cmd'];

$config['adminpanel']['view_default'] = 'expert';

$config['remotebuzzer']['logfile'] = 'remotebuzzer_server.log';
$config['synctodrive']['logfile'] = 'synctodrive_server.log';
$config['dev']['logfile'] = 'error.log';

$config['ui']['branding'] = 'Photobooth';

$defaultConfig = $config;

if (file_exists(PathUtility::getAbsolutePath('config/my.config.inc.php'))) {
    require_once PathUtility::getAbsolutePath('config/my.config.inc.php');

    if (empty($config['mail']['subject'])) {
        if (!empty($config['ui']['language'])) {
            $config['mail']['subject'] = $mailTemplates[$config['ui']['language']]['mail']['subject'];
        } else {
            $config['mail']['subject'] = $mailTemplates[$defaultConfig['ui']['language']]['mail']['subject'];
        }
    }
    if (empty($config['mail']['text'])) {
        if (!empty($config['ui']['language'])) {
            $config['mail']['text'] = $mailTemplates[$config['ui']['language']]['mail']['text'];
        } else {
            $config['mail']['text'] = $mailTemplates[$defaultConfig['ui']['language']]['mail']['text'];
        }
    }

    $config = ArrayUtility::mergeRecursive($defaultConfig, $config);
}

if ($config['dev']['loglevel'] > 0) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (file_exists(PathUtility::getAbsolutePath('config/my.config.inc.php')) && !is_writable(PathUtility::getAbsolutePath('config/my.config.inc.php'))) {
    die('Abort. Can not write config/my.config.inc.php.');
} elseif (!file_exists(PathUtility::getAbsolutePath('config/my.config.inc.php')) && !is_writable(__DIR__ . '/../config/')) {
    die('Abort. Can not create config/my.config.inc.php. Config folder is not writable.');
}

foreach ($config['folders'] as $key => $folder) {
    if ($folder === 'data' || $folder === 'archives' || $folder === 'config' || $folder === 'private') {
        $path = PathUtility::getAbsolutePath($folder);
    } else {
        $path = PathUtility::getAbsolutePath($config['folders']['data'] . DIRECTORY_SEPARATOR . $folder);
        $config['foldersPublic'][$key] = PathUtility::getPublicPath($path);
    }

    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            die("Abort. Could not create $folder.");
        }
    } elseif (!is_writable($path)) {
        die("Abort. The folder $folder is not writable.");
    }

    $config['foldersAbs'][$key] = PathUtility::getAbsolutePath($path);
}

$config['foldersPublic']['api'] = PathUtility::getPublicPath('api');
$config['foldersPublic']['chroma'] = PathUtility::getPublicPath('chroma');

define('PRINT_DB', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'printed.csv');
define('PRINT_LOCKFILE', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'print.lock');
define('PRINT_COUNTER', $config['foldersAbs']['data'] . DIRECTORY_SEPARATOR . 'print.count');
define('PHOTOBOOTH_LOG', $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $config['dev']['logfile']);

if ($config['preview']['mode'] === 'gphoto') {
    $config['preview']['mode'] = 'device_cam';
}

// Preview need to be stopped before we can take an image
if (!empty($config['preview']['killcmd']) && $config['preview']['stop_time'] < $config['picture']['cntdwn_offset']) {
    $config['preview']['stop_time'] = $config['picture']['cntdwn_offset'] + 1;
}

$default_font = PathUtility::getPublicPath('resources/fonts/GreatVibes-Regular.ttf');
$default_frame = PathUtility::getPublicPath('resources/img/frames/frame.png');
$random_frame = PathUtility::getPublicPath('api/randomImg.php?dir=demoframes');
$default_template = PathUtility::getAbsolutePath('resources/template/index.php');

if (empty($config['picture']['frame'])) {
    $config['picture']['frame'] = $random_frame;
}

if (empty($config['textonpicture']['font'])) {
    $config['textonpicture']['font'] = $default_font;
}

if (empty($config['collage']['frame'])) {
    $config['collage']['frame'] = $default_frame;
}

if (empty($config['collage']['placeholderpath'])) {
    $config['collage']['placeholderpath'] = PathUtility::getPublicPath('resources/img/background/01.jpg');
}

if (empty($config['textoncollage']['font'])) {
    $config['textoncollage']['font'] = $default_font;
}

if (empty($config['print']['frame'])) {
    $config['print']['frame'] = $default_frame;
}

if (empty($config['textonprint']['font'])) {
    $config['textonprint']['font'] = $default_font;
}

if (empty($config['collage']['limit'])) {
    $config['collage']['limit'] = 4;
}

$bg_url = PathUtility::getPublicPath('resources/img/background.png');
$logo_url = PathUtility::getPublicPath('resources/img/logo/logo-qrcode-text.png');

if (empty($config['logo']['path'])) {
    $config['logo']['path'] = $logo_url;
}

if (empty($config['background']['defaults'])) {
    $config['background']['defaults'] = 'url(' . $bg_url . ')';
}

if (empty($config['background']['admin'])) {
    $config['background']['admin'] = 'url(' . $bg_url . ')';
}

if (empty($config['background']['chroma'])) {
    $config['background']['chroma'] = 'url(' . $bg_url . ')';
}

if (empty($config['webserver']['ip'])) {
    $config['webserver']['ip'] = $photobooth->getIp();
}

if (empty($config['remotebuzzer']['serverip'])) {
    $config['remotebuzzer']['serverip'] = $photobooth->getIp();
}

if (empty($config['qr']['url'])) {
    $config['qr']['url'] = PathUtility::getPublicPath('api/download.php?image=');
}

if (empty($config['ftp']['template_location']) || !Helper::testFile($config['ftp']['template_location'])) {
    $config['ftp']['template_location'] = $default_template;
}

if (!empty($config['ftp']['urlTemplate'])) {
    try {
        $parameters = [
            '%website' => $config['ftp']['website'],
            '%baseFolder' => $config['ftp']['baseFolder'],
            '%folder' => $config['ftp']['folder'],
            '%title' => Helper::slugify($config['ftp']['title']),
            '%date' => date('Y/m/d'),
        ];
    } catch (\Exception $e) {
        $parameters = [
            '%website' => $config['ftp']['website'],
            '%baseFolder' => $config['ftp']['baseFolder'],
            '%folder' => $config['ftp']['folder'],
            '%title' => 'Example',
            '%date' => date('Y/m/d'),
        ];
    }

    $config['ftp']['processedTemplate'] = str_replace(array_keys($parameters), array_values($parameters), $config['ftp']['urlTemplate']);
}

$config['photobooth']['version'] = $photobooth->getVersion();
$config['photobooth']['basePath'] = PathUtility::getPublicPath();
