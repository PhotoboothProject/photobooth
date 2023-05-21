<?php
    function getInputButton($setting, $i18ntag, $key, $config) {

        switch($key) {
            case 'reset_button':
                $btn = getCtaBtn("reset", $setting['value'], $config);
                break;
            case 'database_rebuild':
            case 'check_version':
                $btn = getCtaBtn("check", $setting['value'], $config);
                break;
            default:
                $btn = getCtaBtn($setting['placeholder'], $setting['value'], $config);
                break;
        }

        $test = "";
        switch ($key) {
            case 'check_version':
                $test = '<table id="version_text_table"><tr><td><span id="current_version_text"></span></td><td><span id="current_version"></span></td></tr><tr><td><span id="available_version_text"></span></td><td></span><span id="available_version"></td></tr></table>';
                break;
            default:
            break;
        }
        
        return (
            getHeadline($i18ntag) .'
                <div class="w-full flex flex-col mt-auto">
                        '. $btn .'
                </div>'. $test
        );
    }
?>