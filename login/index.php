<?php

$fileRoot = '../';
require_once($fileRoot . 'lib/boot.php');

// LOGIN
$username = $config['login']['username'];
$hashed_password = $config['login']['password'];
$error = false;

if (isset($_POST['submit'])) {
    if (isset($_POST['username']) && $_POST['username'] == $username && isset($_POST['password']) && password_verify($_POST['password'], $hashed_password)) {
        //IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
        $_SESSION['auth'] = true;
    } else {
        // DISPLAY FORM WITH ERROR
        $error = true;
    }
}
// END LOGIN

$pageTitle = 'Login';
include($fileRoot . 'admin/components/head.admin.php');
include($fileRoot . 'admin/helper/index.php');

$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';
?>

<body>
	<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">
		<div class="w-full flex items-center justify-center flex-col">

		<?php
            if($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true) && !(isset($_SESSION['rental']))) {
                if(isset($config['login']['keypad']) && $config['login']['keypad'] === true) {
                    include('keypad.php');
                } else {
                    include('loginMask.php');
                }

                if(!$config['login']['rental_keypad']) {
                    echo '<div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>';
                    include('menu.php');
                }
            } else {
                include('menu.php');
            }
?>

		
<?php
    if((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || isset($_SESSION['rental'])) {
        echo'<script>
			setTimeout(function() {
				window.location = "' . $fileRoot . '/login/logout.php";
			}, 60000);
		</script>';
    } else {
        echo'<script>
			setTimeout(function() {
				window.location = "' . $fileRoot . '";
			}, 30000);
		</script>';
    }

    include($fileRoot . 'admin/components/footer.admin.php');
?>
