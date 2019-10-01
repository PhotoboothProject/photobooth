<?php
header('Content-Type: application/json');

require_once('../lib/config.php');

$img = $_POST['imgData'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$data = base64_decode($img);
$filename_print = $config['foldersAbs']['print'] . DIRECTORY_SEPARATOR .basename($_POST['mainImageSrc']).'.png';
$success = file_put_contents($filename_print, $data);

$printimage = shell_exec(
	sprintf(
		$config['print']['cmd'],
		$filename_print
	)
);

echo json_encode(array('status' => 'ok', 'msg' => $printimage || ''));
?>