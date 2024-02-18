<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

if (empty($_POST['recipient']) || !PHPMailer::validateAddress($_POST['recipient'])) {
    $data = [
        'success' => false,
        'error' => 'E-Mail address invalid',
    ];
    $logger->info('message', $data);
    echo json_encode($data);
    exit();
}

if ($config['mail']['send_all_later']) {
    $mailService = MailService::getInstance();
    $mailService->addRecipientToDatabase($_POST['recipient']);
    echo json_encode(['success' => true, 'saved' => true ]);
    exit();
}

if (empty($_POST['image'])) {
    $data = [
        'success' => false,
        'error' => 'Image not defined',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    exit();
}

$postImage = basename($_POST['image']);
$database = DatabaseManagerService::getInstance();
if (!$database->isInDB($postImage)) {
    $data = [
        'success' => false,
        'error' => 'Image not found in database',
    ];
    $logger->info('message', $data);
    echo json_encode($data);
    exit();
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

if (!$mail->addAddress($_POST['recipient'])) {
    $data = [
        'success' => false,
        'error' => 'E-Mail address not valid / error',
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    exit();
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
    $data = [
        'success' => false,
        'error' => 'File error:' . $path . $postImage,
    ];
    $logger->debug('message', $data);
    echo json_encode($data);
    exit();
}

if ($mail->send()) {
    echo json_encode(['success' => true]);
    exit();
}

$data = [
    'success' => false,
    'error' => $mail->ErrorInfo,
];
$logger->debug('message', $data);
echo json_encode($data);
exit();
