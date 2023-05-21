<?php
    function getCtaBtn( $label, $btnId = "", $config = false ) {
        $labels = "";
        if( $config ) {
            $labels = '
                <span class="hidden success"><i class="'. $config['icons']['admin_save_success'] .'"></i><span data-i18n="success"></span></span>
                <span class="hidden error"><i class="'. $config['icons']['admin_save_error'] .'"></i><span data-i18n="saveerror"></span></span>
            ';
        }

        return(
            '
                <button class="save-admin-btn w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-content-1 hover:text-brand-1 transition font-bold" id="'.$btnId.'">
                    <span class="save"><span data-i18n="'. $label .'">'. $label .'</span></span>
                    '. $labels .'
                </button>
            '
        );
    }
?>