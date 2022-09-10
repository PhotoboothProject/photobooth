<?php
require_once __DIR__ . '/arrayDeepMerge.php';
require_once __DIR__ . '/helper.php';

$default_config_file = __DIR__ . '/../config/config.inc.php';
$my_config_file = __DIR__ . '/../config/my.config.inc.php';
$basepath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

$cmds = [
    'windows' => [
        'take_picture' => [
            'cmd' => 'digicamcontrol\CameraControlCmd.exe /capture /filename %s',
            'msg' => 'Photo transfer done.',
        ],
        'print' => [
            'cmd' => 'mspaint /pt %s',
            'msg' => '',
        ],
        'exiftool' => [
            'cmd' => '',
            'msg' => '',
        ],
        'preview' => [
            'cmd' => '',
            'killcmd' => '',
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
            'msg' => 'New file is in location',
        ],
        'print' => [
            'cmd' => 'lp -o landscape -o fit-to-page %s',
            'msg' => '',
        ],
        'exiftool' => [
            'cmd' => 'exiftool -overwrite_original -TagsFromFile %s %s',
            'msg' => '',
        ],
        'preview' => [
            'cmd' => 'gphoto2 --stdout --capture-movie | ffmpeg -i - -vcodec rawvideo -pix_fmt yuv420p -threads 0 -f v4l2 /dev/video0 > /dev/null 2>&1 & echo $!',
            'killcmd' => 'killall gphoto2 && sleep 1',
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

require_once $default_config_file;

$config['take_picture']['cmd'] = $cmds[$os]['take_picture']['cmd'];
$config['take_picture']['msg'] = $cmds[$os]['take_picture']['msg'];
$config['print']['cmd'] = $cmds[$os]['print']['cmd'];
$config['print']['msg'] = $cmds[$os]['print']['msg'];
$config['exiftool']['cmd'] = $cmds[$os]['exiftool']['cmd'];
$config['exiftool']['msg'] = $cmds[$os]['exiftool']['msg'];
$config['preview']['cmd'] = $cmds[$os]['preview']['cmd'];
$config['preview']['killcmd'] = $cmds[$os]['preview']['killcmd'];
$config['nodebin']['cmd'] = $cmds[$os]['nodebin']['cmd'];
$config['reboot']['cmd'] = $cmds[$os]['reboot']['cmd'];
$config['shutdown']['cmd'] = $cmds[$os]['shutdown']['cmd'];

$config['adminpanel']['view_default'] = 'expert';

$config['remotebuzzer']['logfile'] = 'remotebuzzer_server.log';
$config['synctodrive']['logfile'] = 'synctodrive_server.log';
$config['dev']['logfile'] = 'error.log';

$config['ui']['github'] = 'PhotoboothProject';
$config['ui']['branding'] = 'Photobooth';

$defaultConfig = $config;

if (file_exists($my_config_file)) {
    require_once $my_config_file;

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

    $config = array_deep_merge($defaultConfig, $config);
}

if ($config['dev']['loglevel'] > 0) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (file_exists($my_config_file) && !is_writable($my_config_file)) {
    die('Abort. Can not write config/my.config.inc.php.');
} elseif (!file_exists($my_config_file) && !is_writable(__DIR__ . '/../config/')) {
    die('Abort. Can not create config/my.config.inc.php. Config folder is not writable.');
}

foreach ($config['folders'] as $key => $folder) {
    if ($folder === 'data' || $folder === 'archives' || $folder === 'config') {
        $path = $basepath . DIRECTORY_SEPARATOR . $folder;
    } else {
        $path = $basepath . DIRECTORY_SEPARATOR . $config['folders']['data'] . DIRECTORY_SEPARATOR . $folder;
        $config['foldersRoot'][$key] = $config['folders']['data'] . DIRECTORY_SEPARATOR . $folder;

        $config['foldersJS'][$key] = str_replace('\\', '/', $config['foldersRoot'][$key]);
    }

    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            die("Abort. Could not create $folder.");
        }
    } elseif (!is_writable($path)) {
        die("Abort. The folder $folder is not writable.");
    }

    $path = realpath($path);
    $config['foldersAbs'][$key] = $path;
}

if (empty($config['picture']['frame']) || !testFile($config['picture']['frame'])) {
    $config['picture']['frame'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/img/frames/frame.png');
}

if (empty($config['textonpicture']['font']) || !testFile($config['textonpicture']['font'])) {
    $config['textonpicture']['font'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/fonts/GreatVibes-Regular.ttf');
}

if (empty($config['collage']['frame']) || !testFile($config['collage']['frame'])) {
    $config['collage']['frame'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/img/frames/frame.png');
}

if (empty($config['collage']['placeholderpath']) || !testFile($config['collage']['placeholderpath'])) {
    $config['collage']['placeholderpath'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/img/background/01.jpg');
}

if (empty($config['textoncollage']['font']) || !testFile($config['textoncollage']['font'])) {
    $config['textoncollage']['font'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/fonts/GreatVibes-Regular.ttf');
}

if (empty($config['print']['frame']) || !testFile($config['print']['frame'])) {
    $config['print']['frame'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/img/frames/frame.png');
}

if (empty($config['textonprint']['font']) || !testFile($config['textonprint']['font'])) {
    $config['textonprint']['font'] = realpath($basepath . DIRECTORY_SEPARATOR . 'resources/fonts/GreatVibes-Regular.ttf');
}

if (empty($config['collage']['limit'])) {
    $config['collage']['limit'] = 4;
}

if (isSubfolderInstall()) {
    $bg_url = getrootpath('../resources/img/bg_stone.jpg');
    if ($os == 'windows') {
        $bg_url = fixSeperator($bg_url);
    }
} else {
    $bg_url = '/resources/img/bg_stone.jpg';
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
    $config['webserver']['ip'] = getPhotoboothIp();
}

if (empty($config['qr']['url'])) {
    $config['qr']['url'] = getPhotoboothUrl() . '/api/download.php?image=';
}

$config['folders']['lang'] = getrootpath('../resources/lang');
