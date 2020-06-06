<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;

require '../vendor/PHPMailer/src/Exception.php';
require '../vendor/PHPMailer/src/PHPMailer.php';
require '../vendor/PHPMailer/src/SMTP.php';

require_once('../lib/config.php');
require_once('../lib/db.php');

if (empty($_POST['sendTo']) || empty($_POST['image']) || !PHPMailer::validateAddress($_POST['sendTo'])) {
    die(json_encode([
        'success' => false,
        'error' => 'E-Mail address invalid'
    ]));
}

$postImage = basename($_POST['image']);
if (!isImageInDB($postImage)) {
    die(json_encode([
        'success' => false,
        'error' => 'Image not found in database'
    ]));
}

$mail = new PHPMailer;
$mail->setLanguage($config['language'], '../vendor/PHPMailer/language/');

$mail->isSMTP();
$mail->Host = $config['mail_host'];
$mail->SMTPAuth = true;
$mail->SMTPDebug = 0;
$mail->Username = $config['mail_username'];
$mail->Password = $config['mail_password'];
$mail->SMTPSecure = $config['mail_secure'];
$mail->Port = $config['mail_port'];
$mail->setFrom($config['mail_fromAddress'], $config['mail_fromName']);

if (!$mail->addAddress($_POST['sendTo'])) {
    die(json_encode([
        'success' => false,
        'error' => 'E-Mail address not valid / error'
    ]));
}

// Email subject
$mail->Subject = $config['mail_subject'];

// Email body content
$mailContent = $config['mail_text'];

// for send an attachment
$path = $config['foldersAbs']['images'] . DIRECTORY_SEPARATOR;

$mail->Body = $mailContent;
if (!$mail->addAttachment($path . $postImage)) {
    die(json_encode([
        'success' => false,
        'error' => 'file error:' . $path . $postImage
    ]));
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

    die(json_encode([
        'success' => true,
        'saved' => true
    ]));
}

if ($mail->send()) {
    die(json_encode([
        'success' => true,
    ]));
}

die(json_encode([
    'success' => false,
    'error' => $mail->ErrorInfo
]));
