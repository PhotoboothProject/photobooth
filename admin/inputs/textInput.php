<?php
    function getTextInput($setting, $i18ntag) {
        
        return (
            getHeadline($i18ntag) .'
                <input class="w-full h-10 border-2 border-solid border-gray-300 focus:border-brand-1 rounded-md px-3 mt-auto" type="'.($setting['type'] === 'number' ? 'number' : 'text').'" name="'.$setting['name'].'" value="'.$setting['value'].'" placeholder="'.$setting['placeholder'].'"/>
            '
        );
    }
?>