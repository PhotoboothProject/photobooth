<?php
header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/db.php';

function takePicture($filename) {
    global $config;

    if ($config['dev']['enabled']) {
        $demoFolder = __DIR__ . '/../resources/img/demo/';
        $devImg = array_diff(scandir($demoFolder), ['.', '..']);
        copy($demoFolder . $devImg[array_rand($devImg)], $filename);
    } elseif ($config['preview']['mode'] === 'device_cam' && $config['preview']['camTakesPic']) {
        $data = $_POST['canvasimg'];
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);

        file_put_contents($filename, $data);

        if ($config['preview']['flipHorizontal']) {
            $im = imagecreatefromjpeg($filename);
            imageflip($im, IMG_FLIP_HORIZONTAL);
            imagejpeg($im, $filename);
            imagedestroy($im);
        }
    } else {
        $dir = dirname($filename);
        chdir($dir); //gphoto must be executed in a dir with write permission
        $cmd = sprintf($config['take_picture']['cmd'], $filename);

        exec($cmd, $output, $returnValue);

        if ($returnValue) {
            die(
                json_encode([
                    'error' => 'Gphoto returned with an error code',
                    'cmd' => $cmd,
                    'returnValue' => $returnValue,
                    'output' => $output,
                ])
            );
        } elseif (!file_exists($filename)) {
            die(
                json_encode([
                    'error' => 'File was not created',
                    'cmd' => $cmd,
                    'returnValue' => $returnValue,
                    'output' => $output,
                ])
            );
        }
    }
}

if (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file'])) {
    $name = $_POST['file'];
} elseif ($config['picture']['naming'] === 'numbered') {
    if ($config['database']['enabled']) {
        $images = getImagesFromDB();
    } else {
        $images = getImagesFromDirectory($config['foldersAbs']['images']);
    }
    $img_number = count($images);
    $files = str_pad(++$img_number, 4, '0', STR_PAD_LEFT);
    $name = $files . '.jpg';
} elseif ($config['picture']['naming'] === 'dateformatted') {
    $name = date('Ymd_His') . '.jpg';
} else {
    $name = md5(time()) . '.jpg';
}

if ($config['database']['file'] === 'db' || (!empty($_POST['file']) && preg_match('/^[a-z0-9_]+\.jpg$/', $_POST['file']))) {
    $file = $name;
} else {
    $file = $config['database']['file'] . '_' . $name;
}

$filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

if (!isset($_POST['style'])) {
    die(
        json_encode([
            'error' => 'No style provided',
        ])
    );
}

if ($_POST['style'] === 'photo') {
    takePicture($filename_tmp);
} elseif ($_POST['style'] === 'collage') {
    if (!is_numeric($_POST['collageNumber'])) {
        die(
            json_encode([
                'error' => 'No or invalid collage number provided',
            ])
        );
    }

    $number = $_POST['collageNumber'] + 0;

    if ($number > $config['collage']['limit']) {
        die(
            json_encode([
                'error' => 'Collage consists only of ' . $config['collage']['limit'] . ' pictures',
            ])
        );
    }

    $basecollage = substr($file, 0, -4);
    $collage_name = $basecollage . '-' . date('Ymd_His') . '.jpg';

    $basename = substr($filename_tmp, 0, -4);
    $filename = $basename . '-' . $number . '.jpg';

    takePicture($filename);
    copy($filename, $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $collage_name);

    die(
        json_encode([
            'success' => 'collage',
            'file' => $file,
            'collage_file' => $collage_name,
            'current' => $number,
            'limit' => $config['collage']['limit'],
        ])
    );
} elseif ($_POST['style'] === 'chroma') {
    takePicture($filename_tmp);
    die(
        json_encode([
            'success' => 'chroma',
            'file' => $file,
        ])
    );
} else {
    die(
        json_encode([
            'error' => 'Invalid photo style provided',
        ])
    );
}

// send imagename to frontend
echo json_encode([
    'success' => 'image',
    'file' => $file,
]);
