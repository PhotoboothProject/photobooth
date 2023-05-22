<?php
require_once '../lib/helper.php';

/**
 * Class NextcloudShareLink
 */
class NextcloudShareLink {
    /**
     * @var string|null $nextcloudMnt The mount point of the Nextcloud directory.
     */
    public $nextcloudMnt;

    /**
     * @var string|null $nextcloudUser The username for authenticating with Nextcloud.
     */
    public $nextcloudUser;

    /**
     * @var string|null $nextcloudPass The password for authenticating with Nextcloud.
     */
    public $nextcloudPass;

    /**
     * @var string|null $nextcloudUrl The base URL of the Nextcloud instance.
     */
    public $nextcloudUrl;

    /**
     * @var string|null $nextcloudPath The path to the Nextcloud directory.
     */
    public $nextcloudPath;

    /**
     * Creates a share link for a file in Nextcloud.
     *
     * @param string $filename The name of the file.
     * @return string|null The share link URL if successful, null otherwise.
     */
    private function createShareLink($filename) {
        if (
            !isset($this->nextcloudUser) ||
            empty($this->nextcloudUser) ||
            !isset($this->nextcloudPass) ||
            empty($this->nextcloudPass) ||
            !isset($this->nextcloudUrl) ||
            empty($this->nextcloudUrl) ||
            !isset($this->nextcloudPath) ||
            empty($this->nextcloudPath)
        ) {
            return null;
        }
        $username = $this->nextcloudUser;
        $password = $this->nextcloudPass;
        $base_url = Helper::trimSlashes($this->nextcloudUrl);
        $file_path = Helper::trimSlashes($this->nextcloudPath) . '/' . $filename;

        $api_url = "{$base_url}/ocs/v2.php/apps/files_sharing/api/v1/shares";

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
                $share_data = $response_array['ocs']['data'];
                $share_token = $share_data['token'];
                $public_url = "{$base_url}/s/{$share_token}";
                return $public_url;
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

    /**
     * Generates a share link for a file.
     *
     * @param string $filename The name of the file.
     * @return string The share link URL.
     */
    public function generateShareLink($filename) {
        if (isset($this->nextcloudMnt) && !empty($this->nextcloudMnt) && Helper::isDirMounted($this->nextcloudMnt)) {
            return $this->createShareLink($filename);
        }
        return null;
    }
}
