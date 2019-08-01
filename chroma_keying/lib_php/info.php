<?php
require_once('../../config.inc.php');

echo json_encode(array('chroma_keying' => $config['chroma_keying']));
?>