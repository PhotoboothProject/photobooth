<?php
session_start();

require_once('../lib/config.php');

// LOGIN
$username = $config['login']['username'];
$hashed_password = $config['login']['password'];
$error = false;

if (isset($_POST['submit'])) {
    if (isset($_POST['username']) && $_POST['username'] == $username && isset($_POST['password']) && password_verify($_POST["password"], $hashed_password)) {
        //IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
        $_SESSION['auth'] = true;
    } else {
        // DISPLAY FORM WITH ERROR
        $error = true;
    }
}
// END LOGIN

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0 user-scalable=no">
	<meta name="msapplication-TileColor" content="<?=$config['colors']['primary']?>">
	<meta name="theme-color" content="<?=$config['colors']['primary']?>">

	<title><?=$config['ui']['branding']?> Login</title>

	<!-- Favicon + Android/iPhone Icons -->
	<link rel="apple-touch-icon" sizes="180x180" href="../resources/img/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../resources/img/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../resources/img/favicon-16x16.png">
	<link rel="manifest" href="../resources/img/site.webmanifest">
	<link rel="mask-icon" href="../resources/img/safari-pinned-tab.svg" color="#5bbad5">

	<!-- Fullscreen Mode on old iOS-Devices when starting photobooth from homescreen -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<link rel="stylesheet" href="../node_modules/normalize.css/normalize.css" />
	<link rel="stylesheet" href="../node_modules/font-awesome/css/font-awesome.css" />
	<link rel="stylesheet" href="../node_modules/material-icons/iconfont/material-icons.css">
	<link rel="stylesheet" href="../node_modules/material-icons/css/material-icons.css">

	<!-- tw admin -->
	<link rel="stylesheet" href="../resources/css/tailwind.admin.css"/>
</head>
<?php
	include("../admin/helper/index.php");

	$labelClass = "w-full flex flex-col mb-1";
	$inputClass = "w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto";
	$btnClass = "w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4";
?>

<body>
	<div class="w-full h-screen grid place-items-center absolute bg-brand-1 px-6 py-12 overflow-x-hidden overflow-y-auto">
		<div class="w-full flex items-center justify-center flex-col">

		<!-- login -->
		<?php if($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
			<div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
				<form method="post">

					<div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
						<?=$config['ui']['branding']?> Login
					</div>

					<div class="w-full text-center text-gray-500 mb-8">
						Bitte logge dich ein um fortzufahren.
					</div>

					<!-- user -->
					<div class="relative">
						<label class="<?=$labelClass?>" for="username"><span data-i18n="login_username"></span></label>
						<input class="<?=$inputClass?>" type="text" name="username" id="username" autocomplete="on" required>
					</div>

					<!-- pw -->
					<div class="relative mt-2">
						<label class="<?=$labelClass?>" for="password"><span data-i18n="login_password"></span></label>
						<input class="<?=$inputClass?>"  type="password" name="password" id="password" autocomplete="on" required>
						<span toggle="#password" class="absolute w-10 h-10 bottom-0 right-0 cursor-pointer text-brand-1 flex items-center justify-center password-toggle <?=$config['icons']['password_visibility']?>"></span>
					</div>

					<!-- btn -->
					<div class="mt-6">
						<input class="<?=$btnClass?>" type="submit" name="submit" value="Login">
					</div>
					<?php if ($error !== false) {
						echo '<span class="w-full flex mt-6 text-red-500" data-i18n="login_invalid"></span>'; 
					} ?>  
				</form>
			</div>
			<div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>
		<?php endif; ?>


		<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">

			<div class="px-4">
				<h1 class="text-2xl font-bold text-center mb-6 border-solid border-b border-gray-200 pb-4 text-brand-1">Menü</h1>
			</div>

			<?php if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
			<div class="w-12 h-12 bg-white absolute right-4 top-4 rounded-b-l-lg shadow-xls flex items-center justify-center text-brand-1 cursor-pointer">
				<a href="/" >
					<i class="text-xl <?php echo $config['icons']['close']; ?>"></i>
					<!-- <span data-i18n="close"></span> -->
				</a>
			</div>
			<?php endif; ?>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
				<?php 
					if(!$config['protect']['admin'] || (!$config['protect']['localhost_admin'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn("/admin", "admin_panel", $config['icons']['admin']);
					}

					echo getMenuBtn("/gallery.php", "gallery", $config['icons']['gallery']);
					echo getMenuBtn("/slideshow", "slideshow", $config['icons']['slideshow']);

					if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn("/livechroma.php", "livechroma", $config['icons']['livechroma']);
					}

					if(!$config['protect']['manual'] || (!$config['protect']['localhost_manual'] && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn("/faq", "show_faq", $config['icons']['faq']);
						echo getMenuBtn("/manual", "show_manual", $config['icons']['manual']);
						echo getMenuBtn("https://t.me/PhotoboothGroup", "telegram", $config['icons']['telegram']);
					}

					// echo getMenuBtn("/", "reload", $config['icons']['refresh']);

					if(isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
						echo getMenuBtn("/login/logout.php", "logout", $config['icons']['logout']);
					}

				?>
			</div>

		</div>
		
		</div>
	</div>

	<script src="../node_modules/whatwg-fetch/dist/fetch.umd.js"></script>
	<script type="text/javascript" src="../api/config.php?v=<?=$config['photobooth']['version']?>"></script>
	<script type="text/javascript" src="../node_modules/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" src="../resources/js/adminshortcut.js?v=<?=$config['photobooth']['version']?>"></script>
	<script type="text/javascript" src="../resources/js/login.js?v=<?=$config['photobooth']['version']?>"></script>
	<script type="text/javascript" src="../resources/js/theme.js?v=<?=$config['photobooth']['version']?>"></script>
	<script src="../node_modules/@andreasremdt/simple-translator/dist/umd/translator.min.js"></script>
	<script type="text/javascript" src="../resources/js/i18n.js?v=<?=$config['photobooth']['version']?>"></script>
</body>
</html>
