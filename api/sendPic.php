<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;

require '../vendor/PHPMailer/src/Exception.php';
require '../vendor/PHPMailer/src/PHPMailer.php';
require '../vendor/PHPMailer/src/SMTP.php';

require_once '../lib/config.php';
require_once '../lib/db.php';
require_once '../lib/log.php';

if (empty($_POST['sendTo']) || !PHPMailer::validateAddress($_POST['sendTo'])) {
    $LogData = [
        'success' => false,
        'error' => 'E-Mail address invalid',
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    die($LogString);
}

if (isset($_POST['send-link']) && $_POST['send-link'] === 'yes') {
    if (!file_exists(MAIL_FILE)) {
        $addresses = [];
    } else {
        $addresses = json_decode(file_get_contents(MAIL_FILE));
    }

    if (!in_array($_POST['sendTo'], $addresses)) {
        $addresses[] = $_POST['sendTo'];
    }

    file_put_contents(MAIL_FILE, json_encode($addresses));

    die(
        json_encode([
            'success' => true,
            'saved' => true,
        ])
    );
}

if (empty($_POST['image'])) {
    $LogData = [
        'success' => false,
        'error' => 'Image not defined',
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    die($LogString);
}

$postImage = basename($_POST['image']);
if (!isImageInDB($postImage)) {
    $LogData = [
        'success' => false,
        'error' => 'Image not found in database',
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    die($LogString);
}

$mail = new PHPMailer();
$mail->setLanguage($config['ui']['language'], '../vendor/PHPMailer/language/');

$mail->isSMTP();
$mail->Host = $config['mail']['host'];
$mail->SMTPAuth = true;
$mail->SMTPDebug = 0;
$mail->Username = $config['mail']['username'];
$mail->Password = $config['mail']['password'];
$mail->SMTPSecure = $config['mail']['secure'];
$mail->Port = $config['mail']['port'];
$mail->setFrom($config['mail']['fromAddress'], $config['mail']['fromName']);

if (!$mail->addAddress($_POST['sendTo'])) {
    $LogData = [
        'success' => false,
        'error' => 'E-Mail address not valid / error',
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    die($LogString);
}

// Email subject
$mail->Subject = $config['mail']['subject'];

// Email body content
$mail->isHTML($config['mail']['is_html']);
if ($config['mail']['is_html']) {
    if (isset($config['mail']['alt_text']) && empty($config['mail']['alt_text'])) {
        $mail->msgHTML($config['mail']['text']);
    } else {
        $mail->Body = $config['mail']['text'];
        $mail->AltBody = $config['mail']['alt_text'];
    }
} else {
    $mail->Body = $config['mail']['text'];
}

// for send an attachment
$path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR;

if (!$mail->addAttachment($path . $postImage)) {
    $LogData = [
        'success' => false,
        'error' => 'File error:' . $path . $postImage,
        'php' => basename($_SERVER['PHP_SELF']),
    ];
    $LogString = json_encode($LogData);
    if ($config['dev']['loglevel'] > 0) {
        logError($LogData);
    }
    die($LogString);
}

if ($mail->send()) {
    die(
        json_encode([
            'success' => true,
        ])
    );
}

$LogData = [
    'success' => false,
    'error' => $mail->ErrorInfo,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 0) {
    logError($LogData);
}
die($LogString);
