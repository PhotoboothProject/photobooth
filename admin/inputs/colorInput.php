<?php
    function getColorInput($setting, $i18ntag) {
        
        return (
            '<label class="mb-3" data-i18n="'.$i18ntag.'"> '.$i18ntag.'</label>
            <input class="w-full h-10 border-2 border-gray-300 border-solid rounded-lg overflow-hidden p-1 mt-auto" type="color" name="'.$setting['name'].'" value="'.$setting['value'].'" placeholder="'.$setting['placeholder'].'"/>
            '
        );
    }
?>