<?php
header('Content-Type: application/javascript');

require('../lib/config.php');
?>
const config = <?=json_encode($config)?>;