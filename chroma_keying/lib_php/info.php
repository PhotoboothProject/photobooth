<?php
$my_config = '../../my.config.inc.php';
if (file_exists($my_config)) {
	require_once('../../my.config.inc.php');
} else {
	require_once('../../config.inc.php');
}

echo json_encode(array('chroma_keying' => $config['chroma_keying']));
?>