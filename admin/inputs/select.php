<?php
    function getSelect($setting, $i18ntag) {
        $className = $setting['type'] === 'multi-select' ? ' multi-select' : '';
        $className .= "w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-2 mt-auto";
        $settingName = $setting['name'].''.($setting['type'] === 'multi-select' ? '[]' : '');
        $options = "";

        foreach($setting['options'] as $val => $option) {
            $selected = '';
            if ((is_array($setting['value']) && in_array($val, $setting['value'])) || ($val === $setting['value'])) {
                    $selected = ' selected="selected"';
            }
            $options .= '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
        }
        
        return (
            getHeadline($i18ntag) .'                                                                          
            <select class="'.$className .'" name="'. $settingName .'" '. ($setting['type'] === 'multi-select' ? ' multiple="multiple"' : '') .'>
                '.$options.'                               
            </select>'
        );
    }
?>