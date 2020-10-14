<?php if ($config['remotebuzzer_enabled']):
	$connection = @fsockopen('127.0.0.1', $config['remotebuzzer_port']);

	if (! is_resource($connection))
	{
		if ($config['dev'])
		{
			$logfile = $config['folders']['tmp']."/".$config['remotebuzzer_logfile'];
		}
		else
		{ $logfile = "/dev/null"; }

		print ("\t<!-- Remote Buzzer Enabled --- starting server -->\n");

		proc_close(proc_open ($config['nodebin']['cmd']." resources/js/remotebuzzer_server.js 1>>".$logfile." 2>&1 &", array(), $foo));

	} else {
	       print ("\t<!-- Remote Buzzer Enabled --- server already started (port in use) -->\n");
	}

?>
<script type="text/javascript" src="node_modules/socket.io-client/dist/socket.io.slim.js"></script>

<?php endif;

    if ($config['synctodrive_enabled'] && !file_exists($config['folders']['tmp'].'/synctodrive_server.pid') {
      	if ($config['dev']) {
	      $logfile = $config['folders']['tmp']."/".$config['synctodrive_logfile'];
		}
		else { $logfile = "/dev/null"; }
		
		print ("\t<!-- Sync To Drive enabled --- starting server -->\n");
		proc_close(proc_open ($config['nodebin']['cmd']." resources/js/sync-to-drive.js 1>>".$logfile." 2>&1 &", array(), $foo));
	}
?>
