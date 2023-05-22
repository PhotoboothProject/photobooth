<?php
    function getMenuBtn( $target, $label, $icon ) {
        $btnClass = "w-full h-12 rounded-full bg-brand-1 text-white flex items-center justify-center relative ml-auto border-2 border-solid border-brand-1 hover:bg-white hover:text-brand-1 transition font-bold px-4";

        return(
            '
                <a href="'. $target .'" class="'. $btnClass .'"><i class="mr-3 '. $icon .'"></i> <span data-i18n="'. $label .'">'. $label .'</span></a>
            '
        );
    }
?>