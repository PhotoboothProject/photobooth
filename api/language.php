<?php
header('Content-Type: application/javascript');

require('../lib/config.php');
?>
export const LANGUAGE = <?=json_encode($config['language'])?>;
