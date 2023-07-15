<?php
    $pinLength = strlen($config['login']['pin']);
    $pinMap = str_split($config['login']['pin']);

    function getKeyIndicator($pinMap, $length) {
        echo '<div class="pinIndicator flex items-center justify-center">';
        for ($x = 0; $x <= $length - 1; $x++) {
            $activeClass = "";
            if( $x == 0 ) {
                $activeClass = "active";
            }

            $containerClass = '
                keypad_keybox '.$activeClass.' 
                flex items-center justify-center w-10 h-14 
                border border-solid border-gray-200 bg-gray-50 rounded m-2 
                [&.active]:border-brand-1 
                [&.error]:animate-error [&.error]:border-red-500 [&.error]:border-opacity-70
            ';
            $dotClass = '
                keypad_key '.$activeClass.' 
                w-3 h-3 rounded-full bg-gray-400 
                [&.active]:border-2 [&.active]:border-solid [&.active]:border-brand-1 [&.active]:bg-transparent
                [&.checked]:bg-brand-1
                [&.error]:bg-red-500 [&.error]:bg-opacity-70
            ';
            echo '<div class="'.$containerClass.'">
                    <span class="'.$dotClass.'"></span>
                </div>';
        }
        echo '</div>';
    }
    function getKey( $key = null ) {
        global $fileRoot;
        $containerClass = 'keypad_key peer flex items-center justify-center p-2 hover:text-brand-1 transition-all';
        $keyClass = '
            flex items-center justify-center w-16 h-16 transition-all
            text-gray-500 text-lg cursor-pointer font-bold
            border border-solid border-gray-200 rounded-full
            hover:border-brand-1 hover:text-brand-1 hover:scale-110
            active:border-brand-1 active:bg-brand-1 active:text-white
            outline-none focus:outline-none focus:ring-2 focus:ring-brand-1 active:ring-2 active:ring-brand-1 active:outline-none
        ';

        if( isset($key) ) {
            if( is_numeric($key) ) {
                echo '<div class="'. $containerClass .'">
                    <span class="'. $keyClass .'" onclick="keypadAdd('. $key .');">'. $key .'</span>
                </div>';
            } elseif ( $key  === "remove" ) {
                echo '<div class="'. $containerClass .' cursor-pointer" onclick="keypadRemoveLastValue();"><span class="fa fa-chevron-left"></span></div>';
            } elseif ( $key  === "home" ) {
                echo '<a href="' . $fileRoot . '" class="text-2xl '. $containerClass .' cursor-pointer"><span class="fa fa-home"></span></div>';
            }
        } else {
            echo '<div class="'. $containerClass .'"></div>';
        }
    }

?>

<div class="w-full max-w-md h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl relative overflow-hidden">
    <form method="post">

        <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
            <?=$config['ui']['branding']?> Login
        </div>

        <div class="w-full text-center text-gray-500 mb-8">
            <span data-i18n="login_pin_request"></span>
        </div>

        <div class="w-full text-center text-gray-500 mb-8">
            <?php getKeyIndicator($pinMap, $pinLength); ?>
        </div>

        <div class="w-full text-center text-gray-500 mb-8">
            <div class="grid grid-cols-3">
                <?php
                    echo getKey(1);
                    echo getKey(2);
                    echo getKey(3);
                    echo getKey(4);
                    echo getKey(5);
                    echo getKey(6);
                    echo getKey(7);
                    echo getKey(8);
                    echo getKey(9);
                    echo getKey("remove");
                    echo getKey(0);
                    echo getKey("home");
                ?>
            </div>
        </div>

        <div id="keypad_pin" class="hidden"></div>

        <div class="keypadLoader w-full h-full absolute top-0 left-0 flex flex-col items-center justify-center bg-white bg-opacity-90 hidden">
            <?php echo getLoader("sm"); ?>
        </div>

    </form>
</div>
<div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>
