<?php
session_start();
$fileRoot = '../';

require_once($fileRoot . 'lib/config.php');

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
			if($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
				if(isset($config['login']['keypad']) && $config['login']['keypad'] === true) {
					include('keypad.php');
				} else {
					include('loginMask.php');
				}
			}	
		?>

		<div class="w-full max-w-xl rounded-lg py-8 bg-white flex flex-col shadow-xl relative">

			<div class="px-4">
				<h1 class="text-2xl font-bold text-center mb-6 border-solid border-b border-gray-200 pb-4 text-brand-1">
					<span data-i18n="menu"></span>
				</h1>
			</div>

			<?php if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)): ?>
			<div class="w-12 h-12 bg-white absolute right-4 top-4 rounded-b-l-lg shadow-xls flex items-center justify-center text-brand-1 cursor-pointer">
				<a href="<?=$fileRoot?>" >
					<i class="!text-2xl <?php echo $config['icons']['close']; ?>"></i>
					<!-- <span data-i18n="close"></span> -->
				</a>
			</div>
			<?php endif; ?>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 ">
				<?php 
					if(!$config['protect']['admin'] || (!$config['protect']['localhost_admin'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn($fileRoot . 'admin', 'admin_panel', $config['icons']['admin']);
					}

					echo getMenuBtn($fileRoot . 'gallery', 'gallery', $config['icons']['gallery']);
					echo getMenuBtn($fileRoot . 'slideshow', 'slideshow', $config['icons']['slideshow']);

					if(!$config['protect']['index'] || (!$config['protect']['localhost_index'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn($fileRoot . 'chroma', 'chromaCapture', $config['icons']['chromaCapture']);
					}

					if(!$config['protect']['manual'] || (!$config['protect']['localhost_manual'] && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'])) || !$config['login']['enabled'] || (isset($_SESSION['auth']) && $_SESSION['auth'] === true)) {
						echo getMenuBtn($fileRoot . 'faq', 'show_faq', $config['icons']['faq']);
						echo getMenuBtn($fileRoot . 'manual', 'show_manual', $config['icons']['manual']);
						echo getMenuBtn('https://t.me/PhotoboothGroup', 'telegram', $config['icons']['telegram']);
					}

					// echo getMenuBtn("/", "reload", $config['icons']['refresh']);

					if(isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
						echo getMenuBtn($fileRoot . 'login/logout.php', 'logout', $config['icons']['logout']);
					}

				?>
			</div>

		</div>
		
		</div>
	</div>



<?php
    include($fileRoot . 'admin/components/footer.admin.php');
?>
