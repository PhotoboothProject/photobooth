<?php

use Photobooth\Service\ApplicationService;
use Photobooth\Service\LanguageService;
use Photobooth\Utility\AdminKeypad;
use Photobooth\Utility\PathUtility;

require_once '../lib/boot.php';

// LOGIN
$username = $config['login']['username'];
$hashed_password = $config['login']['password'];
$error = false;
$now = time();

// Per-IP throttle persisted on disk to survive session clearing
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$throttleFile = PathUtility::getAbsolutePath('var/run/login_throttle.json');
$windowSeconds = 300; // 5 minutes
$maxAttempts = 10;

$ipAttempts = ['count' => 0, 'window' => $now];
if (is_readable($throttleFile)) {
    $raw = file_get_contents($throttleFile);
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded) && isset($decoded[$ip]) && is_array($decoded[$ip])) {
        $ipAttempts = $decoded[$ip];
    }
}
if (($now - ($ipAttempts['window'] ?? 0)) > $windowSeconds) {
    $ipAttempts = ['count' => 0, 'window' => $now];
}

if (isset($_POST['submit'])) {
    if ($_SESSION['login_attempts']['count'] >= $maxAttempts || $ipAttempts['count'] >= $maxAttempts) {
        $error = true;
    }

    if (!$error && isset($_POST['username']) && $_POST['username'] == $username && isset($_POST['password']) && password_verify($_POST['password'], $hashed_password)) {
        //IF USERNAME AND PASSWORD ARE CORRECT SET THE LOG-IN SESSION
        $_SESSION['auth'] = true;
        $_SESSION['login_attempts'] = ['count' => 0, 'window' => $now];
        $ipAttempts = ['count' => 0, 'window' => $now];
    } else {
        // DISPLAY FORM WITH ERROR
        $error = true;
        $_SESSION['login_attempts']['count']++;
        $ipAttempts['count']++;
        // small delay to slow brute force even if session is cleared
        usleep(300000); // 0.3s
    }

    // Persist IP attempts
    $allAttempts = [];
    if (is_readable($throttleFile)) {
        $raw = file_get_contents($throttleFile);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            $allAttempts = $decoded;
        }
    }
    $allAttempts[$ip] = $ipAttempts;
    @file_put_contents($throttleFile, json_encode($allAttempts), LOCK_EX);
}
// END LOGIN

$pageTitle = 'Login - ' . ApplicationService::getInstance()->getTitle();
$languageService = LanguageService::getInstance();
include PathUtility::getAbsolutePath('admin/components/head.admin.php');
include PathUtility::getAbsolutePath('admin/helper/index.php');

$labelClass = 'w-full flex flex-col mb-1';
$inputClass = 'w-full h-10 border border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto';
$btnClass = 'w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4';

echo '<body>';
echo '<div class="w-full h-screen grid place-items-center absolute bg-brand-2 px-6 py-12 overflow-x-hidden overflow-y-auto">';
echo '<div class="w-full flex items-center justify-center flex-col">';

if ($config['login']['enabled'] && !(isset($_SESSION['auth']) && $_SESSION['auth'] === true) && !(isset($_SESSION['rental']))) {
    if (isset($config['login']['keypad']) && $config['login']['keypad'] === true) {
        echo '
            <div class="w-full max-w-md rounded-lg p-8 bg-white flex flex-col shadow-xl relative overflow-hidden">
                <form method="post">
                    <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">Login</div>
                    <div class="w-full text-center text-gray-500 mb-8">' . $languageService->translate('login_pin_request') . '</div>
                    <div class="w-full text-center text-gray-500 mb-4">' . AdminKeypad::renderIndicator(AdminKeypad::pinLength($config['login']['pin'])) . '</div>
                    <div id="keypad_message" class="text-center text-red-600 font-semibold mb-4 min-h-[1.5rem]"></div>
                    <div class="w-full text-center text-gray-500">' . AdminKeypad::render() . '</div>
                    <div id="keypad_pin" class="hidden"></div>
                    <div class="keypadLoader w-full h-full absolute top-0 left-0 flex-col items-center justify-center bg-white/90 hidden">' . getLoader('sm') . '</div>
                </form>
            </div>
        ';
    } else {
        include PathUtility::getAbsolutePath('login/loginMask.php');
    }
    if (!$config['login']['rental_keypad']) {
        echo '<div class="w-full max-w-xl my-12 border-b border-solid border-white/20"></div>';
        include PathUtility::getAbsolutePath('login/menu.php');
    }
} else {
    include PathUtility::getAbsolutePath('login/menu.php');
}

echo '</div>';
echo '</div>';

if ((isset($_SESSION['auth']) && $_SESSION['auth'] === true) || isset($_SESSION['rental'])) {
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

include PathUtility::getAbsolutePath('admin/components/footer.scripts.php');
include PathUtility::getAbsolutePath('admin/components/footer.admin.php');
