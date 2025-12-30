<?php
// admin/collage-designer/components/preview-canvas.php

use Photobooth\Utility\PathUtility;

// The PHP part that provides demo images remains relevant.
// We will pass these paths to our JavaScript.
?>
<div class="result_images w-full md:max-h-[70vh] flex-1 relative p-4 md:p-8 bg-slate-300">
    <!-- The wrapper for the canvas. The aspect ratio will be set dynamically. -->
    <div id="collageCanvasWrapper" class="relative m-0 left-[50%] top-[50%] right-0 bottom-0 translate-y-[-50%] translate-x-[-50%] max-w-full max-h-full shadow-xl bg-white">
         <!-- The actual HTML5 Canvas -->
        <canvas id="collageCanvas" class="w-full h-full"></canvas>

        <!-- Optional: Place a loading indicator or overlay for interactive elements here -->
        <div id="loadingOverlay" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
            <span class="text-brand-1 text-lg">Loading...</span>
        </div>
    </div>
</div>

<?php
// PHP code to export demo images as a JavaScript array.
// This is the direct way to pass image paths to the frontend.
// Later, we could replace these with the user's actual images.
$jsDemoImages = json_encode(array_map(fn ($img) => PathUtility::getPublicPath($img), $demoImages));
echo '<script type="text/javascript">';
echo 'const initialDemoImagePaths = ' . $jsDemoImages . ';';
echo '</script>';
?>
