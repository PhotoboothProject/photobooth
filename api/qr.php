<?php

use Photobooth\Utility\QrCodeUtility;

class QrCodeClass
{
    public function getWifiQrCode($qrConfig)
    {
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
        $wifi = 'WIFI:S:' . $qrConfig['wifi_ssid'] . ';' . $sec . 'P:' . $qrConfig['wifi_pass'] . $hidden . ';';

        $result = QrCodeUtility::create($wifi);
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
    }

    public function getImageQrCode($qrConfig, $filename)
    {
        if ($qrConfig['append_filename']) {
            $url = $qrConfig['url'] . $filename;
        } else {
            $url = $qrConfig['url'];
        }

        $result = QrCodeUtility::create($url);
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
    }
}
