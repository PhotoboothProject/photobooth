<?php
// admin/collage-designer/components/preview-canvas.php

use Photobooth\Utility\PathUtility;

?>
<div class="result_images w-full md:max-h-[70vh] flex-1 relative p-4 md:p-8 bg-slate-300">
    <div id="result_canvas" class="relative m-0 left-[50%] top-[50%] right-0 bottom-0 translate-y-[-50%] translate-x-[-50%] max-w-full max-h-full shadow-xl aspect-video bg-white">
        <div id="collage_background" class="absolute h-full w-full">
            <img class="h-full hidden object-contain object-top" src="" alt="Choose the background">
        </div>
        <?php
        // Hier rendern wir die initialen Bild-Platzhalter und den Text-Container
        // Wir könnten dies auch über eine Helferklasse steuern
        for ($i = 0; $i < count($demoImages); $i++) {
            $imagePath = PathUtility::getPublicPath($demoImages[$i]);
            $hiddenClass = $i == 0 ? '' : 'hidden';
            echo "<div id='picture-$i' class='absolute overflow-hidden w-full h-full $hiddenClass' data-element-type='image' data-image-index='$i'>
                    <img class='absolute object-left-top rotate-0 max-w-none' data-src='$imagePath' draggable='false'>
                    <img class='picture-frame absolute object-left-top rotate-0 max-w-none hidden' draggable='false' />
                  </div>";
        }
?>
        <div id="collage_frame" class="absolute h-full w-full">
            <img class="h-full w-full hidden" src="" alt="Choose the frame" draggable="false">
        </div>
        <div id="collage_text_container" class="absolute h-full w-full font-selected">
            <!-- Dynamisch hinzugefügte Textfelder kommen hier rein -->
            <!-- Initialer Platzhalter für text_line_1, text_line_2, text_line_3 aus dem alten Code, kann später entfernt werden -->
            <div class='relative w-full h-full'>
                <div class='absolute whitespace-nowrap origin-top-left text-line-1 leading-none'></div>
                <div class='absolute whitespace-nowrap origin-top-left text-line-2 leading-none'></div>
                <div class='absolute whitespace-nowrap origin-top-left text-line-3 leading-none'></div>
            </div>
        </div>
    </div>
</div>
