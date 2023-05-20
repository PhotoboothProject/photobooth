<?php
require_once '../lib/config.php';
require_once '../lib/helper.php';

function trim_slashes($path) {
    return trim($path, '/');
}

function is_dir_mounted($path) {
    $path = realpath($path);
    $mounts = explode("\n", file_get_contents('/proc/mounts'));

    foreach ($mounts as $mount) {
        list($dev, $mountpoint) = explode(' ', $mount);
        if ($mountpoint == $path) {
            return true;
        }
    }

    return false;
}

function create_nc_share_link($filename, $config) {
    $username = $config['nextcloud']['user'];
    $password = $config['nextcloud']['pass'];
    $base_url = trim_slashes($config['nextcloud']['url']);
    $file_path = trim_slashes($config['nextcloud']['path']) . '/' . $filename;

    // Nextcloud API URL
    $api_url = "{$base_url}/ocs/v2.php/apps/files_sharing/api/v1/shares";

    // Create a new share
    function create_share_link($file_path, $username, $password, $api_url, $config) {
        $data = [
            'path' => $file_path,
            'shareType' => 3, // Public link
            'permissions' => 1, // Read-only
        ];

        $retry_count = 0;
        $max_retries = 15; // adjust as needed

        do {
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['OCS-APIRequest: true', 'Accept: application/json']);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response_array = json_decode($response, true);
            if ($http_code == 200 && isset($response_array['ocs']['data'])) {
                return $response_array['ocs']['data'];
            } else {
                $error_message = 'Error creating share link: Data - ' . print_r($data, true) . "; HTTP code - {$http_code}; Response - {$response}";
                $retry_count++;
                if ($retry_count <= $max_retries) {
                    sleep(2); // wait for 2 seconds before retrying
                }
            }
        } while ($retry_count <= $max_retries);

        return null;
    }

    // Create the share link
    $share_data = create_share_link($file_path, $username, $password, $api_url, $config);

    if ($share_data !== null) {
        $share_token = $share_data['token'];

        // Generate the public URL using the token
        $public_url = "{$base_url}/s/{$share_token}";

        // Return the download URL
        return $public_url;
    } else {
        error_log('Error creating share link.');
        return $config['nextcloud']['share'];
    }
}

if (isset($_GET['filename'])) {
    $filename = $_GET['filename'];

    // Check for the various conditions and execute the corresponding actions
    if (!$config['nextcloud']['enabled'] || !is_dir_mounted($config['nextcloud']['mnt'])) {
        if ($config['qr']['append_filename']) {
            echo $config['qr']['url'] . $filename;
        } else {
            echo $config['qr']['url'];
        }
    } elseif ($config['nextcloud']['fileshare']) {
        $share_link = create_nc_share_link($filename, $config);
        echo $share_link;
    } else {
        echo $config['nextcloud']['share'];
    }
}
?>
