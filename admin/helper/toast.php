<?php
    function getToast($type = false, $msg = false) {
        $transition = "slideInRight";
        $scheme = "
            bg-white 
            [&.isSuccess]:bg-green-500 [&.isSuccess]:font-bold [&.isSuccess]:text-white 
            [&.isError]:bg-red-500 [&.isError]:font-bold [&.isError]:text-white
        ";

        return(
            '
                <div class="adminToast hidden [&.isActive]:flex w-64 p-4 rounded-md shadow-md top-2 right-2 fixed '. $scheme .' '. $transition .'">    
                    <div class="flex items-center">
                        <div class="fa fa-check mr-3 text-white"></div>
                        <div class="headline">Fehler beim speichern</div>
                    </div>
                </div>
            '
        );
    }
?> 