<?php
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/log.php';

/**
 * Class Nextcloud
 */
class Nextcloud {
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
     * @var DataLogger|null $logger
     */
    public $logger = null;

    /**
     * NextcloudShareLink constructor.
     * @param DataLogger|null $logger
     */
    public function __construct($ncConfig, $logger = null) {
        if ($logger == null || !is_object($this->logger)) {
            $this->logger = new DataLogger(PHOTOBOOTH_LOG);
            $this->logger->addLogData(['php' => basename($_SERVER['PHP_SELF'])]);
        }

        $this->nextcloudMnt = $ncConfig['mnt'];
        $this->nextcloudUser = $ncConfig['user'];
        $this->nextcloudPass = $ncConfig['pass'];
        $this->nextcloudUrl = $ncConfig['url'];
        $this->nextcloudPath = $ncConfig['path'];
        $this->nextcloudFileshare = $ncConfig['fileshare'];
        $this->nextcloudMntEnabled = $ncConfig['mntEnabled'];
        $this->nextcloudEnabled = $ncConfig['enabled'];
    }

    /**
     * Creates a share link for a file in Nextcloud.
     *
     * @param string $filename The name of the file.
     * @return string|null The share link URL if successful, null otherwise.
     */
    private function createShareLink($filename) {
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

        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_HTTPHEADER => ['OCS-APIRequest: true', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response_array = json_decode($response, true);
        if ($http_code == 200 && isset($response_array['ocs']['data'])) {
            $share_data = $response_array['ocs']['data'];
            $share_token = $share_data['token'];
            $url = "{$base_url}/s/{$share_token}";

            $SuccessData = [
                'success' => $url,
                'data' => print_r($data, true),
                'httpCode' => $http_code,
                'response' => $response,
            ];
            return $SuccessData;
        } else {
            $ErrorData = [
                'error' => 'Error creating share link:',
                'data' => print_r($data, true),
                'httpCode' => $http_code,
                'response' => $response,
            ];
            return $ErrorData;
        }
    }

    /**
     * Generates a share link for a file.
     *
     * @param string $filename The name of the file.
     * @return string The share link URL.
     */
    public function generateShareLink($filename, $qrConfig) {
        $sanity =
            isset($this->nextcloudUser) ||
            !empty($this->nextcloudUser) ||
            isset($this->nextcloudPass) ||
            !empty($this->nextcloudPass) ||
            isset($this->nextcloudUrl) ||
            !empty($this->nextcloudUrl) ||
            isset($this->nextcloudPath) ||
            !empty($this->nextcloudPath) ||
            isset($this->nextcloudEnabled) ||
            $this->nextcloudEnabled ||
            isset($this->nextcloudFileshare) ||
            $this->nextcloudFileshare;

        $shareData = null;
        if (
            $sanity &&
            isset($this->nextcloudMntEnabled) &&
            $this->nextcloudMntEnabled &&
            isset($this->nextcloudMnt) &&
            !empty($this->nextcloudMnt) &&
            Helper::isDirMounted($this->nextcloudMnt)
        ) {
            $maxRetries = 10;
            do {
                $shareData = $this->createShareLink($filename);
                $maxRetries--;
                if (isset($shareData['success'])) {
                    break;
                }
                sleep(2);
            } while ($maxRetries > 0);
        } elseif ($sanity && !$this->nextcloudMntEnabled) {
            $shareData = $this->createShareLink($filename);
        }

        if (isset($shareData['error']) || $shareData == null) {
            if ($qrConfig['append_filename']) {
                $shareData['success'] = $qrConfig['url'] . $filename;
            } else {
                $shareData['success'] = $qrConfig['url'];
            }
        }

        $this->logger->addLogData($shareData);
        return $shareData;
    }

    /**
     * Uploads an image to Nextcloud via WebDAV
     *
     * @param string $filename The name of the file.
     * @return string The success string or null on error
     */
    public function uploadImage($imgDir, $filename) {
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
            $this->logger->addLogData('Failed to upload image to Nextcloud: Check Nextcloud config settings');
            return;
        }

        $image = $imgDir . '/' . $filename;
        $username = $this->nextcloudUser;
        $password = $this->nextcloudPass;
        $base_url = Helper::trimSlashes($this->nextcloudUrl);
        $file_path = Helper::trimSlashes($this->nextcloudPath) . '/' . $filename;

        $api_url = "{$base_url}/remote.php/webdav/{$file_path}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_PUT => true,
            CURLOPT_INFILE => fopen($image, 'r'),
            CURLOPT_INFILESIZE => filesize($image),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 201) {
            $logData = [
                'success' => 'File successfully uploaded to nextcloud!',
                'apiURL' => $api_url,
                'httpCode' => $http_code,
                'response' => $response,
            ];
        } else {
            $logData = [
                'error' => 'Failed to upload file to nextcloud',
                'apiURL' => $api_url,
                'httpCode' => $http_code,
                'response' => $response,
            ];
        }

        $this->logger->addLogData($logData);
    }

    /**
     * Deletes an image on Nextcloud via WebDAV
     *
     * @param string $filename The name of the file.
     * @return string The success string or null on error
     */
    public function deleteImage($filename) {
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
            $this->logger->addLogData('Failed to delete image from Nextcloud: Check Nextcloud config settings');
            return;
        }

        $username = $this->nextcloudUser;
        $password = $this->nextcloudPass;
        $base_url = Helper::trimSlashes($this->nextcloudUrl);
        $file_path = Helper::trimSlashes($this->nextcloudPath) . '/' . $filename;

        $api_url = "{$base_url}/remote.php/webdav/{$file_path}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 204) {
            $logData = [
                'msg' => 'File deleted successfully!',
                'apiURL' => $api_url,
                'httpCode' => $http_code,
                'response' => $response,
            ];
        } else {
            $logData = [
                'error' => "Failed to delete {$filename} on {$base_url}",
                'apiURL' => $api_url,
                'httpCode' => $http_code,
                'response' => $response,
            ];
        }

        $this->logger->addLogData($logData);
    }
}
