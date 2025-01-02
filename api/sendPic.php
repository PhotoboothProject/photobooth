<?php

/** @var array $config */

require_once '../lib/boot.php';

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

$logger = LoggerService::getInstance()->getLogger('main');
$logger->debug(basename($_SERVER['PHP_SELF']));

// Validate email addresses (comma-separated)
if (empty($_POST['recipient'])) {
    $data = [
        'success' => false,
        'error' => 'E-Mail address is required',
    ];
    $logger->info('message', $data);
    echo json_encode($data);
    exit();
}

// Split the comma-separated email addresses
$recipients = array_map('trim', explode(',', $_POST['recipient']));
$invalidEmails = [];

// Check each email address
foreach ($recipients as $recipient) {
    if (!PHPMailer::validateAddress($recipient)) {
        $invalidEmails[] = $recipient;
    }
}

if (!empty($invalidEmails)) {
    $data = [
        'success' => false,
        'error' => 'Invalid email addresses: ' . implode(', ', $invalidEmails),
    ];
    $logger->info('message', $data);
    echo json_encode($data);
    exit();
}

if ($config['mail']['send_all_later']) {
    $mailService = MailService::getInstance();
    // Save each recipient to the database
    foreach ($recipients as $recipient) {
        $mailService->addRecipientToDatabase($recipient);
    }
    echo json_encode(['success' => true, 'saved' => true]);
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

// Prepare the email subject and body content
$mailSubject = trim($config['mail']['subject']) !== ''
    ? $config['mail']['subject']
    : LanguageService::getInstance()->translate('mail:sendPicture:subject');

$mailText = trim($config['mail']['text']) !== ''
    ? $config['mail']['text']
    : LanguageService::getInstance()->translate('mail:sendPicture:text');

$path = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR;

foreach ($recipients as $recipient) {
    // Create a new PHPMailer object for each recipient
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

    // Add the recipient to this email
    $mail->addAddress($recipient);

    // Email subject
    $mail->Subject = $mailSubject;

    // Email body content
    $mail->isHTML($config['mail']['is_html']);
    if ($config['mail']['is_html']) {
        if (isset($config['mail']['alt_text']) && empty($config['mail']['alt_text'])) {
            $mail->msgHTML($mailText);
        } else {
            $mail->Body = $mailText;
            $mail->AltBody = $config['mail']['alt_text'];
        }
    } else {
        $mail->Body = $mailText;
    }

    // Add attachment
    if (!$mail->addAttachment($path . $postImage)) {
        $data = [
            'success' => false,
            'error' => 'File error: ' . $path . $postImage,
        ];
        $logger->debug('message', $data);
        echo json_encode($data);
        exit();
    }

    // Send the email
    if (!$mail->send()) {
        $data = [
            'success' => false,
            'error' => 'Failed to send email to ' . $recipient . '. Error: ' . $mail->ErrorInfo,
        ];
        $logger->debug('message', $data);
        echo json_encode($data);
        exit();
    }
}

// If all emails are sent successfully
echo json_encode(['success' => true]);
exit();
