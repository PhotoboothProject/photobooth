<?php

declare(strict_types=1);

// Run only in a disposable root container with vsftpd, openssh-server and PHP FTP installed.
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Photobooth\Configuration\PhotoboothConfiguration;
use Photobooth\Enum\RemoteStorageTypeEnum;
use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LanguageService;
use Photobooth\Service\LoggerService;
use Photobooth\Service\RemoteStorageQueueService;
use Photobooth\Service\RemoteStorageService;
use Photobooth\Utility\PathUtility;
use Symfony\Component\Config\Definition\Processor;

if (getenv('PHOTOBOOTH_INTEGRATION_CONTAINER') !== '1' || !is_file('/.dockerenv') || posix_geteuid() !== 0) {
    throw new RuntimeException('Requires explicit PHOTOBOOTH_INTEGRATION_CONTAINER=1 inside a disposable root container.');
}

/** @phpstan-assert true $condition */
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function command(array $command, string $input = ''): void
{
    $process = proc_open($command, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
    check(is_resource($process), 'Could not start fixture command.');
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    check(proc_close($process) === 0, 'Fixture command failed: ' . $command[0]);
}

/** @return resource */
function startServer(array $command, int $port, string $log)
{
    $occupied = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
    if ($occupied !== false) {
        fclose($occupied);
        throw new RuntimeException('Fixture port is already occupied: ' . $port);
    }
    $process = proc_open($command, [['file', '/dev/null', 'r'], ['file', $log, 'a'], ['file', $log, 'a']], $pipes);
    check(is_resource($process), 'Could not start fixture server.');
    $deadline = microtime(true) + 5;
    do {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if ($socket !== false) {
            fclose($socket);
            return $process;
        }
        usleep(20000);
    } while (microtime(true) < $deadline && proc_get_status($process)['running']);
    stopServer($process);
    throw new RuntimeException('Fixture server did not become ready: ' . file_get_contents($log));
}

function killServerTree(int $pid): void
{
    foreach (preg_split('/\s+/', trim((string) @file_get_contents('/proc/' . $pid . '/task/' . $pid . '/children'))) ?: [] as $child) {
        if ((int) $child > 0) {
            killServerTree((int) $child);
        }
    }
    @posix_kill($pid, SIGKILL);
}

/** @param resource|null $process */
function stopServer($process): void
{
    if (!is_resource($process)) {
        return;
    }
    $status = proc_get_status($process);
    if ($status['running']) {
        killServerTree($status['pid']);
    }
    proc_close($process);
}

function fixture(string $root, string $name): void
{
    file_put_contents($root . '/data/images/' . $name, 'synthetic photo: ' . $name);
    file_put_contents($root . '/data/thumbs/' . $name, 'synthetic thumb: ' . $name);
}

function verifyUpload(string $root, string $destination, string $name): void
{
    foreach (['images', 'thumbs'] as $kind) {
        check(file_get_contents($root . '/data/' . $kind . '/' . $name) === @file_get_contents($destination . '/' . $kind . '/' . $name), 'Remote bytes differ: ' . $kind . '/' . $name);
    }
}

$root = sys_get_temp_dir() . '/photobooth-remote-' . bin2hex(random_bytes(6));
$username = 'pb' . bin2hex(random_bytes(5));
$password = bin2hex(random_bytes(24));
$server = null;
$userCreated = false;
$previousRoot = PathUtility::$rootPathCache;
$classes = [ConfigurationService::class, LoggerService::class, RemoteStorageService::class, LanguageService::class];
$previousGlobals = [];
foreach ($classes as $class) {
    $previousGlobals[$class] = $GLOBALS[$class] ?? null;
    unset($GLOBALS[$class]);
}
$report = ['started_at_utc' => gmdate(DATE_ATOM), 'php' => PHP_VERSION, 'servers' => 'loopback vsftpd:2121 and OpenSSH internal-sftp:2222', 'trigger' => 'enqueue followed by direct production process(); detached launch outside this harness', 'protocols' => []];
try {
    foreach (['data/images', 'data/thumbs', 'var/log', 'resources/lang', 'remote-ftp', 'remote-sftp'] as $folder) {
        mkdir($root . '/' . $folder, 0755, true);
    }
    file_put_contents($root . '/resources/lang/en.json', '{}');
    file_put_contents($root . '/gallery-template.php', '<?php echo "synthetic gallery";');
    PathUtility::$rootPathCache = $root . '/';
    command(['useradd', '--no-create-home', '--home-dir', $root, '--shell', '/bin/sh', $username]);
    $userCreated = true;
    command(['chpasswd'], $username . ':' . $password . "\n");
    foreach (['remote-ftp', 'remote-sftp'] as $folder) {
        chown($root . '/' . $folder, $username);
    }
    command(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-f', $root . '/host-key']);
    if (!is_dir('/run/sshd')) {
        mkdir('/run/sshd', 0755, true);
    }
    file_put_contents($root . '/sshd.conf', "ListenAddress 127.0.0.1\nPort 2222\nHostKey $root/host-key\nPidFile $root/sshd.pid\nPasswordAuthentication yes\nKbdInteractiveAuthentication no\nUsePAM no\nPermitRootLogin no\nAllowUsers $username\nSubsystem sftp internal-sftp\n");
    file_put_contents($root . '/vsftpd.conf', "listen=YES\nlisten_ipv6=NO\nlisten_address=127.0.0.1\nlisten_port=2121\nbackground=NO\nanonymous_enable=NO\nlocal_enable=YES\nwrite_enable=YES\nlocal_umask=022\nlocal_max_rate=1048576\npam_service_name=vsftpd\nseccomp_sandbox=NO\npasv_address=127.0.0.1\n");

    foreach ([RemoteStorageTypeEnum::FTP, RemoteStorageTypeEnum::SFTP] as $type) {
        $protocol = $type->value;
        $port = $type === RemoteStorageTypeEnum::FTP ? 2121 : 2222;
        $remoteRoot = $root . '/remote-' . $protocol;
        $serverCommand = $type === RemoteStorageTypeEnum::FTP
            ? ['/usr/sbin/vsftpd', $root . '/vsftpd.conf']
            : ['/usr/sbin/sshd', '-D', '-e', '-f', $root . '/sshd.conf'];
        $config = (new Processor())->processConfiguration(new PhotoboothConfiguration(), [[]]);
        $config['ftp'] = array_replace($config['ftp'], [
            'enabled' => true, 'type' => $type, 'baseURL' => '127.0.0.1', 'port' => $port,
            'username' => $username, 'password' => $password, 'baseFolder' => $remoteRoot,
            'folder' => 'events', 'title' => 'Synthetic party', 'create_webpage' => true,
            'template_location' => 'gallery-template.php',
        ]);
        $config['ui']['language'] = 'en';
        $config['colors']['button_font'] = '#ffffff';
        $GLOBALS[ConfigurationService::class] = new class ($config) extends ConfigurationService {
            public function __construct(array $config)
            {
                $this->configuration = $config;
            }
            public function load(): void
            {
            }
        };
        unset($GLOBALS[RemoteStorageService::class]);
        $remote = RemoteStorageService::getInstance();
        $destination = $remoteRoot . '/' . $remote->getStorageFolder();
        $queue = new RemoteStorageQueueService($root . '/queue-' . $protocol);
        $server = startServer($serverCommand, $port, $root . '/' . $protocol . '.log');

        fixture($root, 'online.jpg');
        $queue->enqueue('online.jpg');
        $online = $queue->process();
        check($online['uploaded'] === 1 && $online['failed'] === 0 && $online['remaining'] === 0, $protocol . ': online upload failed.');
        verifyUpload($root, $destination, 'online.jpg');
        check(file_get_contents($destination . '/index.php') === file_get_contents($root . '/gallery-template.php'), 'Gallery template differs.');
        check(is_file($destination . '/config.inc.php'), 'Gallery configuration missing.');

        stopServer($server);
        $server = null;
        fixture($root, 'offline-a.jpg');
        fixture($root, 'offline-b.jpg');
        $queue->enqueue('offline-a.jpg');
        $queue->enqueue('offline-b.jpg');
        $offline = $queue->process();
        check($offline['uploaded'] === 0 && $offline['failed'] === 1 && $offline['remaining'] === 2, $protocol . ': offline jobs were not retained or later job was attempted.');

        $server = startServer($serverCommand, $port, $root . '/' . $protocol . '.log');
        fixture($root, 'recovery.jpg');
        $queue->enqueue('recovery.jpg');
        $recovery = $queue->process();
        check($recovery['uploaded'] === 3 && $recovery['failed'] === 0 && $recovery['remaining'] === 0, $protocol . ': capture-triggered recovery failed.');
        foreach (['offline-a.jpg', 'offline-b.jpg', 'recovery.jpg'] as $name) {
            verifyUpload($root, $destination, $name);
        }

        fixture($root, 'a-denied.jpg');
        fixture($root, 'z-allowed.jpg');
        file_put_contents($destination . '/images/a-denied.jpg', 'root-owned protected fixture');
        chmod($destination . '/images/a-denied.jpg', 0444);
        $queue->enqueue('a-denied.jpg');
        $queue->enqueue('z-allowed.jpg');
        $permissionErrors = [];
        $permission = $queue->process(static function (string $file, bool $success, ?string $reason) use (&$permissionErrors): void {
            if (!$success) {
                $permissionErrors[] = $reason;
            }
        });
        check($permissionErrors === [\League\Flysystem\UnableToWriteFile::class], 'Expected a generic write failure for permission denial.');
        check($permission['uploaded'] === 1 && $permission['failed'] === 1 && $permission['remaining'] === 1, $protocol . ': permission denial blocked the later image.');
        verifyUpload($root, $destination, 'z-allowed.jpg');
        chmod($destination . '/images/a-denied.jpg', 0666);
        $permissionRecovery = $queue->process();
        check($permissionRecovery['uploaded'] === 1 && $permissionRecovery['remaining'] === 0, 'Permission recovery failed.');
        verifyUpload($root, $destination, 'a-denied.jpg');

        fixture($root, 'a-interrupted.jpg');
        fixture($root, 'z-after-interruption.jpg');
        file_put_contents($root . '/data/images/a-interrupted.jpg', str_repeat('synthetic', 2 * 1024 * 1024));
        $queue->enqueue('a-interrupted.jpg');
        $queue->enqueue('z-after-interruption.jpg');
        $serverPid = proc_get_status($server)['pid'];
        $barrier = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        check($barrier !== false, 'Could not create interruption barrier.');
        $watcher = pcntl_fork();
        check($watcher !== false && $watcher !== -1, 'Could not fork interruption watcher.');
        if ($watcher === 0) {
            fclose($barrier[0]);
            fwrite($barrier[1], 'ready');
            $deadline = microtime(true) + 15;
            do {
                clearstatcache();
                $bytes = @filesize($destination . '/images/a-interrupted.jpg');
                if ($bytes !== false && $bytes > 0) {
                    killServerTree($serverPid);
                    fwrite($barrier[1], 'killed-after-byte');
                    // Avoid inherited connection destructors issuing FTP QUIT on the parent's socket.
                    posix_kill(posix_getpid(), SIGKILL);
                }
                usleep(100);
            } while (microtime(true) < $deadline);
            fwrite($barrier[1], 'timeout');
            posix_kill(posix_getpid(), SIGKILL);
        }
        fclose($barrier[1]);
        check(fread($barrier[0], 5) === 'ready', 'Interruption watcher not ready.');
        $interruptionErrors = [];
        $interruption = $queue->process(static function (string $file, bool $success, ?string $reason) use (&$interruptionErrors): void {
            if (!$success) {
                $interruptionErrors[] = $reason;
            }
        });
        $watcherResult = stream_get_contents($barrier[0]);
        fclose($barrier[0]);
        pcntl_waitpid($watcher, $status);
        check($watcherResult === 'killed-after-byte', 'No mid-transfer interruption observed.');
        check($interruptionErrors === [\League\Flysystem\UnableToWriteFile::class], 'Expected a generic write failure for interrupted transfer.');
        check($interruption['uploaded'] === 0 && $interruption['failed'] === 1 && $interruption['remaining'] === 2, $protocol . ': interrupted transfer did not stop the queue.');
        stopServer($server);
        $server = startServer($serverCommand, $port, $root . '/' . $protocol . '.log');
        // The failed upload's contents can be replaced locally before an explicit retry.
        fixture($root, 'a-interrupted.jpg');
        $interruptionRecovery = $queue->process();
        check($interruptionRecovery['uploaded'] === 2 && $interruptionRecovery['remaining'] === 0, 'Interrupted upload recovery failed.');
        verifyUpload($root, $destination, 'a-interrupted.jpg');
        verifyUpload($root, $destination, 'z-after-interruption.jpg');
        $report['protocols'][$protocol] = compact('online', 'offline', 'recovery', 'permission', 'permissionErrors', 'permissionRecovery', 'interruption', 'interruptionErrors', 'interruptionRecovery');
        $report['protocols'][$protocol]['photo_thumb_bytes_verified'] = true;
        $report['protocols'][$protocol]['gallery_verified'] = true;
        stopServer($server);
        $server = null;
    }
    $report['status'] = 'passed';
} finally {
    stopServer($server);
    foreach ($classes as $class) {
        unset($GLOBALS[$class]);
        if ($previousGlobals[$class] !== null) {
            $GLOBALS[$class] = $previousGlobals[$class];
        }
    }
    PathUtility::$rootPathCache = $previousRoot;
    if ($userCreated) {
        command(['userdel', $username]);
    }
    if (is_dir($root)) {
        command(['rm', '-rf', '--', $root]);
    }
}
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
$options = getopt('', ['report:']);
check(is_array($options), 'Could not parse integration options.');
if (isset($options['report'])) {
    check(is_string($options['report']), 'Expected one report filename.');
    file_put_contents($options['report'], $json);
}
echo $json;
