<?php
include '../vendor/phpqrcode/lib/full/qrlib.php';

class QrCodeClass {
    public function __construct() {
    }

    public function getWifiQrCode($qrConfig) {
        // hidden
        if ($qrConfig['wifi_ssid_hidden'] == true) {
            $hidden = (string) ',H:true';
        } else {
            $hidden = (string) '';
        }

        // secure
        if ($qrConfig['wifi_secure'] == 'WPA' || $qrConfig['wifi_secure'] == 'WPA2') {
            $sec = (string) 'T:WPA;';
        } elseif ($qrConfig['wifi_secure'] == 'WEP') {
            $sec = (string) 'T:WEP;';
        } else {
            $sec = (string) '';
        }

        // $ecLevel = "false";
        $ecLevel = $this->getEcLevel($qrConfig['ecLevel']);

        $wifi = 'WIFI:S:' . $qrConfig['wifi_ssid'] . ';' . $sec . 'P:' . $qrConfig['wifi_pass'] . $hidden . ';';
        $svg = QRcode::svg($wifi, false, $ecLevel, 8);
        echo $svg;
    }

    public function getImageQrCode($qrConfig, $filename) {
        if ($qrConfig['append_filename']) {
            $url = $qrConfig['url'] . $filename;
        } else {
            $url = $qrConfig['url'];
        }

        // $ecLevel = "false";
        $ecLevel = $this->getEcLevel($qrConfig['ecLevel']);

        $svg = QRcode::svg($url, false, $ecLevel, 8);
        echo $svg;
    }

    private function getEcLevel($configEcLevel) {
        switch ($configEcLevel) {
            case 'QR_ECLEVEL_L':
                $ecLevel = 'QR_ECLEVEL_L';
                break;
            case 'QR_ECLEVEL_M':
                $ecLevel = 'QR_ECLEVEL_M';
                break;
            case 'QR_ECLEVEL_Q':
                $ecLevel = 'QR_ECLEVEL_Q';
                break;
            case 'QR_ECLEVEL_H':
                $ecLevel = 'QR_ECLEVEL_H';
                break;
            default:
                $ecLevel = 'QR_ECLEVEL_M';
                break;
        }

        return $ecLevel;
    }
}

?>
