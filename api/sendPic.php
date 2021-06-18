<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;

require '../vendor/PHPMailer/src/Exception.php';
require '../vendor/PHPMailer/src/PHPMailer.php';
require '../vendor/PHPMailer/src/SMTP.php';

require_once '../lib/config.php';
require_once '../lib/db.php';

if (empty($_POST['sendTo']) || empty($_POST['image']) || !PHPMailer::validateAddress($_POST['sendTo'])) {
    die(
        json_encode([
            'success' => false,
            'error' => 'E-Mail address invalid',
        ])
    );
}

$postImage = basename($_POST['image']);
if (!isImageInDB($postImage)) {
    die(
        json_encode([
            'success' => false,
            'error' => 'Image not found in database',
        ])
    );
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
    die(
        json_encode([
            'success' => false,
            'error' => 'E-Mail address not valid / error',
        ])
    );
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
    die(
        json_encode([
            'success' => false,
            'error' => 'file error:' . $path . $postImage,
        ])
    );
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

if ($mail->send()) {
    die(
        json_encode([
            'success' => true,
        ])
    );
}

die(
    json_encode([
        'success' => false,
        'error' => $mail->ErrorInfo,
    ])
);
