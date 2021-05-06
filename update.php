<?php

require_once('lib/config.php');
$os = DIRECTORY_SEPARATOR == '\\' || strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';
$path = __DIR__ . DIRECTORY_SEPARATOR;

function is_connected() {
    $connected = @fsockopen("www.google.com", 80);
    // website, port  (try 80 or 443)
    if ($connected) {
        $is_conn = true;
        fclose($connected);
    } else {
        $is_conn = false;
    }

    return $is_conn;
}

if ($_GET['updatedev'] === "start" || $_GET['updatestable'] === "start" ) {
    $checked = true;
    $needs_commit = false;

    if ($_GET['updatedev'] === "start") {
        $updscript = realpath($path . 'resources/sh/update-dev.sh');
    } else {
        $updscript = realpath($path . 'resources/sh/update-stable.sh');
    }

    $execute = exec('bash ' . $updscript  . ' 2>&1');
} elseif ($_GET['commit'] === "start") {
    $checked = true;
    $needs_commit = true;
    $commitscript = realpath($path . 'resources/sh/commit.sh');
    $execute = exec('bash ' . $commitscript  . ' 2>&1');
} else {
    $needs_commit = false;
    $checked = false;
}

if ($checked === false) {
    if ($os === 'linux') {
        if (is_connected()) {
            $status = 'Connected. Trying to update...';
            $ghscript = realpath($path . 'resources/sh/checkgithub.sh');
            $ghnamescript = realpath($path . 'resources/sh/setup-gitname.sh');
            $ghemailscript = realpath($path . 'resources/sh/setup-gitemail.sh');
            $ghname = exec('bash ' . $ghnamescript  . ' 2>&1');
            $ghemail = exec('bash ' . $ghemailscript  . ' 2>&1');
            $gitcheck = exec('bash ' . $ghscript  . ' 2>&1');
            if ($gitcheck === "1") {
                $message = 'Update possible! Click to start the update! Please be Patient - this might take a while!';
                $instructions = '';
                $update_possible = true;
                $can_commit = false;
            } elseif ($gitcheck === "2") {
                $message = 'Update impossible! Please commit your changes first!';
                $instructions = 'Commit changes and backup in branch: "backup-' . date('Ymd') . '"?';
                $update_possible = false;
                $can_commit = true;
            } else {
                $message = 'Can not update! This is not a git repo!';
                $instructions = 'Please install via git to use the updater.';
                $update_possible = false;
                $can_commit = false;
            }

        } else {
            $status = 'Connection failure. ';
        }

    // WINDOWS
    } else {
        $status = 'Update not possible!';
        $message = 'Updater only works on Linux!';
        $instructions = '';
    }
}

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?></title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="resources/img/favicon-16x16.png">
	<link rel="manifest" href="resources/img/site.webmanifest">
	<link rel="mask-icon" href="resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" href="resources/css/<?php echo $config['ui']['style']; ?>_style.css" />
	<link rel="stylesheet" href="resources/css/update.css" />
	<?php if ($config['ui']['rounded_corners'] && $config['ui']['style'] === 'classic'): ?>
	<link rel="stylesheet" href="resources/css/rounded.css" />
	<?php endif; ?>
</head>

<body class="updatewrapper">

	<div class="white-box"><h2>
	<?php
	    if ($checked === false) {
		print_r($status);
                echo '<br/>';
		print_r($message);
                echo '<br/>';
                echo '<br/>';
		print_r($instructions);
                echo '<br/>';
                if ($update_possible === true) {
        ?>
		<form action="update.php" method="get">
			<input type="hidden" name="updatedev" value="start">
			<input type="submit" value="Update to latest development version">
		</form>

		<br/>

		<form action="update.php" method="get">
			<input type="hidden" name="updatestable" value="start">
			<input type="submit" value="Update to latest Stable v3 Release">
		</form>
	<?php
                } elseif ($can_commit === true) {
                    print_r($ghname);
                    echo '<br/>';
                    print_r($ghemail);
                    echo '<br/>';

        ?>
		<br/>
		<form action="update.php" method="get">
			<input type="hidden" name="commit" value="start">
			<input type="submit" value="Commit & backup">
		</form>
	<?php
                }

            // $_GET['updatestable'] or $_GET['updatedev'] or $_GET['commit'] === "start"
	    } else {
                echo '<br/>';
                print_r($execute);
                if ($needs_commit === true) {
                    header("refresh: 10; url=update.php");
                }

            }
	?>
	</h2></div>

	<div>
		<a href="./" class="btn"><i class="fa fa-home"></i> <span data-i18n="home"></span></a>
	</div>

	<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="api/config.php"></script>
	<script type="text/javascript" src="resources/js/theme.js"></script>
	<script src="node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script src="node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="resources/js/i18n.js"></script>

</body>
</html>
