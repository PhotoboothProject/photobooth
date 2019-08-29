<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'resources/lib/PHPMailer/src/Exception.php';
require 'resources/lib/PHPMailer/src/PHPMailer.php';
require 'resources/lib/PHPMailer/src/SMTP.php';

$my_config = 'my.config.inc.php';
if (file_exists($my_config)) {
	require_once('my.config.inc.php');
} else {
	require_once('config.inc.php');
}
require_once ('db.php');

if (array_key_exists('sendTo', $_POST) && PHPMailer::validateAddress($_POST['sendTo']) && array_key_exists('image', $_POST) ) {

    $postImage = str_replace( 'images' . DIRECTORY_SEPARATOR, '', $_POST['image']);
    if ( !in_array($postImage, $images) ) {
        echo json_encode(array('success' => false, 'error' => 'Image not found in database'));
        exit;
    }

    //try {
        $mail = new PHPMailer;
        $mail->setLanguage($config['language'], 'resources/lib/PHPMailer/language/');

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
            echo json_encode(array('success' => false, 'error' => 'E-Mail address not valid / error'));
            exit;
        }

        // Email subject
        $mail->Subject = $config['mail_subject'];
        // Set email format to HTML
        //$mail->isHTML(true);
        // Email body content
        $mailContent = $config['mail_text'];

        // for send an attatchment
        $path = $config['folders']['images'] . DIRECTORY_SEPARATOR;

        //$file_name = $_POST['image'];

        $mail->Body = $mailContent;
        if (!$mail->addAttachment($path . $postImage)) {
            echo 'file error:' . $path . $postImage;
            exit;
        }

        /*$mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );*/
        // Send email
        if ($mail->send() === false) {
            echo json_encode(array('success' => false, 'error' => $mail->ErrorInfo));
        } else {
            if ($_POST['send-link'] == 'yes') {
                if (!file_exists('mail-addresses.txt')) {
                    file_put_contents('mail-addresses.txt', json_encode(array()));
                }
                $addresses = json_decode(file_get_contents('mail-addresses.txt'));
                if (!in_array($_POST['sendTo'], $addresses)) {
                    $addresses[] = $_POST['sendTo'];
                }
                file_put_contents('mail-addresses.txt', json_encode($addresses));
            }
            echo json_encode(array('success' => true));
        }
    /*} catch (Exception $e) {
        echo json_encode(array('success' => false, 'error' => $e.getMessage()));
    }*/
} else {
    echo json_encode(array('success' => false, 'error' => 'E-Mail address invalid'));
}
