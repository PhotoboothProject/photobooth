<?php
require_once('../lib/config.php');

echo json_encode(array('chroma_keying' => $config['chroma_keying']));
?>