<?php
$default_config = __DIR__ . '/../config/config.inc.php';
$my_config = __DIR__ . '/../config/my.config.inc.php';
$os = (DIRECTORY_SEPARATOR == '\\') || (strtolower(substr(PHP_OS, 0, 3)) === 'win') ? 'windows' : 'linux';

$cmds = [
    'windows' => [
        'take_picture' => [
            'cmd' => 'digicamcontrol\CameraControlCmd.exe /capture /filename %s',
            'msg' => 'Photo transfer done.'
        ],
        'print' => [
            'cmd' => 'mspaint /pt "%s"',
            'msg' => '',
        ]
    ],
    'linux' => [
        'take_picture' => [
            'cmd' => 'gphoto2 --capture-image-and-download --filename=%s images',
            'msg' => 'New file is in location'
        ],
        'print' => [
            'cmd' => 'lp -o landscape -o fit-to-page %s',
            'msg' => '',
        ]
    ],
];

$mailTemplates = [
    'de' => [
        'mail_subject' => 'Hier ist dein Bild',
        'mail_text' => 'Hey, dein Bild ist angehangen.',
    ],
    'fr' => [
        'mail_subject' => 'Voici votre photo',
        'mail_text' => 'Hé, ta photo est attachée.',
    ],
    'en' => [
        'mail_subject' => 'Here is your picture',
        'mail_text' => 'Hey, your picture is attached.',
    ],
];

if (file_exists($my_config)) {
    require_once($my_config);
} else {
    require_once($default_config);
}

if ($config['dev']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (empty($config['mail_subject'])) {
    $config['mail_subject'] = $mailTemplates[$config['language']]['mail_subject'];
}

if (empty($config['mail_text'])) {
    $config['mail_text'] = $mailTemplates[$config['language']]['mail_text'];
}

if (empty($config['take_picture']['cmd'])) {
    $config['take_picture']['cmd'] = $cmds[$os]['take_picture']['cmd'];
}

if (empty($config['take_picture']['msg'])) {
    $config['take_picture']['msg'] = $cmds[$os]['take_picture']['msg'];
}

if (empty($config['print']['cmd'])) {
    $config['print']['cmd'] = $cmds[$os]['print']['cmd'];
}

if (empty($config['print']['msg'])) {
    $config['print']['msg'] = $cmds[$os]['print']['msg'];
}

foreach ($config['folders'] as $key => $folder) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $folder;

    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            die("Abort. Could not create $folder");
        }
    }

    $path = realpath($path);
    $config['foldersAbs'][$key] = $path;
}
