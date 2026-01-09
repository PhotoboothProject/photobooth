<?php

use Photobooth\Enum\FolderEnum;
use Photobooth\Service\ApplicationService;
use Photobooth\Service\AssetService;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\DatabaseManagerService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\MailService;
use Photobooth\Service\PrintManagerService;
use Photobooth\Service\ProcessService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Service\SoundService;
use Photobooth\Utility\FileUtility;
use Photobooth\Utility\PathUtility;

// Harden session cookie defaults; auto-enable Secure on HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Longer-lived sessions for kiosk use
ini_set('session.gc_maxlifetime', 172800);   // 48h server-side lifetime
ini_set('session.cookie_lifetime', 172800);  // 48h client cookie lifetime

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $isHttps,
]);
session_start();

// Ensure a CSRF token exists for client-side requests
if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf']) || $_SESSION['csrf'] === '') {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!function_exists('checkCsrfOrFail')) {
    /**
     * Validate a CSRF token from request data and exit with 403 on mismatch.
     *
     * @param  array   $source  Typically $_POST or $_GET
     * @param  string  $key     CSRF field name (defaults to session key)
     */
    function checkCsrfOrFail(array $source, string $key = 'csrf'): void
    {
        $sessionToken  = $_SESSION[$key] ?? '';
        $incomingToken = $source[$key] ?? '';
        if (!hash_equals((string)$sessionToken, (string)$incomingToken)) {
            $logger = Photobooth\Service\LoggerService::getInstance()->getLogger('main');
            $logger->debug('CSRF validation failed', [
                'expected' => $sessionToken,
                'provided' => $incomingToken,
                'path'     => $_SERVER['REQUEST_URI'] ?? '',
                'method'   => $_SERVER['REQUEST_METHOD'] ?? '',
            ]);
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit();
        }
    }
}

// Ensure login attempt tracking structure exists to avoid notices on fresh sessions
if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'window' => time()];
}

// Basic security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

FileUtility::createDirectory(PathUtility::getAbsolutePath('config'));
FileUtility::createDirectory(FolderEnum::DATA->absolute());
FileUtility::createDirectory(FolderEnum::IMAGES->absolute());
FileUtility::createDirectory(FolderEnum::KEYING->absolute());
FileUtility::createDirectory(FolderEnum::PRINT->absolute());
FileUtility::createDirectory(FolderEnum::QR->absolute());
FileUtility::createDirectory(FolderEnum::TEST->absolute());
FileUtility::createDirectory(FolderEnum::THUMBS->absolute());
FileUtility::createDirectory(FolderEnum::TEMP->absolute());
FileUtility::createDirectory(FolderEnum::PRIVATE->absolute());
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/fonts'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/screensavers'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/background'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/frames'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/keyingBackgrounds'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/logo'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/placeholder'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/cheese'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/images/demo'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('private/videos/background'));
FileUtility::createDirectory(FolderEnum::VAR->absolute());
FileUtility::createDirectory(PathUtility::getAbsolutePath('var/log'));
FileUtility::createDirectory(PathUtility::getAbsolutePath('var/run'));

// Shared instances
//
// We are assigning shared instances to $GLOBALS
// to avoid needing to construct them multiple times
// through the runtime and provide an easy way
// to use them as Singleton.
//
// Instances assigned to $GLOBALS should implement
// a getInstance method to recieve the shared state
// again.
//
// public static function getInstance(): self
// {
//     if (!isset($GLOBALS[self::class])) {
//         $GLOBALS[self::class] = new self();
//     }
//
//     return $GLOBALS[self::class];
// }
//
// Example:
// $languageService = LanguageService::getInstance();
// $languageService->translate('abort');
//
$GLOBALS[ApplicationService::class] = new ApplicationService();
$GLOBALS[ConfigurationService::class] = new ConfigurationService();
$GLOBALS[AssetService::class] = new AssetService();
$GLOBALS[LanguageService::class] = new LanguageService();
$GLOBALS[SoundService::class] = new SoundService();
$GLOBALS[LoggerService::class] = new LoggerService();
$GLOBALS[PrintManagerService::class] = new PrintManagerService();
$GLOBALS[DatabaseManagerService::class] = new DatabaseManagerService();
$GLOBALS[MailService::class] = new MailService();
$GLOBALS[ProcessService::class] = new ProcessService();
$GLOBALS[RemoteStorageService::class] = new RemoteStorageService();

$config = ConfigurationService::getInstance()->getConfiguration();
// Collect errors; only display when dev loglevel > 0
if ($config['dev']['loglevel'] > 0) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

date_default_timezone_set((string)$config['ui']['local_timezone']->value);
