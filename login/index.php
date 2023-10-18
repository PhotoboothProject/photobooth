<?php

use Photobooth\Service\LanguageService;
use Photobooth\Utility\AdminKeypad;
use Photobooth\Utility\PathUtility;

require_once '../lib/boot.php';

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
$languageService = LanguageService::getInstance();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';

echo '<body>';
echo '<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">';
echo '<div class="w-full flex items-center justify-center flex-col">';

if($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true) && !(isset($_SESSION['rental']))) {
    if(isset($config['login']['keypad']) && $config['login']['keypad'] === true) {
        echo '
            <div class="w-full max-w-md h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl relative overflow-hidden">
                <form method="post">
                    <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">' . $config['ui']['branding'] . ' Login</div>
                    <div class="w-full text-center text-gray-500 mb-8">' . $languageService->translate('login_pin_request') . '</div>
                    <div class="w-full text-center text-gray-500 mb-8">' . AdminKeypad::renderIndicator(strlen($config['login']['pin'])) . '</div>
                    <div class="w-full text-center text-gray-500">' . AdminKeypad::render() . '</div>
                    <div id="keypad_pin" class="hidden"></div>
                    <div class="keypadLoader w-full h-full absolute top-0 left-0 flex-col items-center justify-center bg-white bg-opacity-90 hidden">' . getLoader('sm') . '</div>
                </form>
            </div>
        ';
    } else {
        include PathUtility::getAbsolutePath('login/loginMask.php');
    }
    if(!$config['login']['rental_keypad']) {
        echo '<div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>';
        include PathUtility::getAbsolutePath('login/menu.php');
    }
} else {
    include PathUtility::getAbsolutePath('login/menu.php');
}

echo '</div>';
echo '</div>';

if((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || isset($_SESSION['rental'])) {
    echo '
        <script>
            setTimeout(function() {
                window.location = "' . PathUtility::getPublicPath('login/logout.php') . '";
            }, 60000);
        </script>
    ';
} else {
    echo '
        <script>
            setTimeout(function() {
                window.location = "' . PathUtility::getPublicPath() . '";
            }, 30000);
        </script>
    ';
}

include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
