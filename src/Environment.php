<?php

namespace Photobooth;

use Photobooth\Enum\FolderEnum;
use Photobooth\Utility\PathUtility;

class Environment implements \JsonSerializable
{
    public static function isLinux(): bool
    {
        return self::getOperatingSystem() === 'linux';
    }

    public static function isWindows(): bool
    {
        return self::getOperatingSystem() === 'windows';
    }

    public static function getOperatingSystem(): string
    {
        return (stripos(PHP_OS, 'darwin') === false
            && stripos(PHP_OS, 'cygwin') === false
            && stripos(PHP_OS, 'win') !== false)
            ? 'windows'
            : 'linux';
    }

    public static function getIp(): string
    {
        static $cachedIp = null;

        if ($cachedIp !== null) {
            return $cachedIp;
        }

        if (self::isLinux()) {
            $ip = trim((string) (shell_exec('hostname -I') ?: ''));
            $cachedIp = $ip === '' ? '' : (preg_split('/\s+/', $ip)[0] ?? '');
        } else {
            $cachedIp = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
        }

        return $cachedIp;
    }

    /**
     * Returns the machine network interfaces with their assigned addresses,
     * so an operator can tell on which network each address lives (e.g. to
     * reach the machine via SSH/VNC). Loopback and IPv6 link-local addresses
     * are skipped.
     *
     * @return array<string, array{up: bool, description: string|null, ssid: string|null, addresses: array<int, array{address: string, family: string, netmask: string|null, network: string|null}>}>
     */
    public static function getNetworkInterfaces(): array
    {
        $interfaces = [];
        $raw = function_exists('net_get_interfaces') ? net_get_interfaces() : false;
        if ($raw === false) {
            return $interfaces;
        }

        foreach ($raw as $name => $data) {
            $addresses = [];
            foreach ($data['unicast'] ?? [] as $unicast) {
                $address = (string) ($unicast['address'] ?? '');
                if (filter_var($address, FILTER_VALIDATE_IP) === false) {
                    continue;
                }
                if ($address === '::1' || str_starts_with($address, '127.') || stripos($address, 'fe80:') === 0) {
                    continue;
                }
                $isIpv4 = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
                $netmask = (string) ($unicast['netmask'] ?? '');
                $addresses[] = [
                    'address' => $address,
                    'family' => $isIpv4 ? 'IPv4' : 'IPv6',
                    'netmask' => $netmask !== '' ? $netmask : null,
                    'network' => self::calculateNetwork($address, $netmask),
                ];
            }
            if (empty($addresses)) {
                continue;
            }
            $interfaces[(string) $name] = [
                'up' => (bool) ($data['up'] ?? false),
                'description' => isset($data['description']) ? (string) $data['description'] : null,
                'ssid' => self::getWifiSsid((string) $name),
                'addresses' => $addresses,
            ];
        }

        return $interfaces;
    }

    /**
     * Network address in CIDR notation (e.g. 192.168.1.0/24) for an
     * address/netmask pair. Byte-wise so it works for IPv4 and IPv6.
     */
    protected static function calculateNetwork(string $address, string $netmask): ?string
    {
        if ($netmask === '') {
            return null;
        }
        $addressBinary = @inet_pton($address);
        $netmaskBinary = @inet_pton($netmask);
        if ($addressBinary === false || $netmaskBinary === false || strlen($addressBinary) !== strlen($netmaskBinary)) {
            return null;
        }
        $prefix = 0;
        foreach (str_split($netmaskBinary) as $byte) {
            $prefix += substr_count(decbin(ord($byte)), '1');
        }
        $network = inet_ntop($addressBinary & $netmaskBinary);
        if ($network === false) {
            return null;
        }

        return $network . '/' . $prefix;
    }

    /**
     * Best effort: the SSID a wireless interface is connected to (Linux
     * only, requires iwgetid from wireless-tools).
     */
    protected static function getWifiSsid(string $interface): ?string
    {
        if (!self::isLinux() || !function_exists('shell_exec') || !str_starts_with($interface, 'wl')) {
            return null;
        }
        $ssid = trim((string) (shell_exec('iwgetid ' . escapeshellarg($interface) . ' -r 2>/dev/null') ?: ''));

        return $ssid === '' ? null : $ssid;
    }

    public static function getPublicFolders(): array
    {
        $data = [];
        foreach (FolderEnum::cases() as $folder) {
            $data[$folder->identifier()] = $folder->public();
        }

        return $data;
    }

    public static function getAbsoluteFolders(): array
    {
        $data = [];
        foreach (FolderEnum::cases() as $folder) {
            $data[$folder->identifier()] = $folder->absolute();
        }

        return $data;
    }

    /**
     * Config for frontend
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'operatingSystem' => self::getOperatingSystem(),
            'ip' => self::getIp(),
            'baseUrl' => PathUtility::getBaseUrl(),
            'publicFolders' => self::getPublicFolders(),
            'absoluteFolders' => self::getAbsoluteFolders(),
        ];
    }
}
