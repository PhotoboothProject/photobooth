
<div class="stages" id="loader">
    <div class="loaderInner">
        <div class="spinner">
            <i class="<?php echo $config['icons']['spinner']; ?>"></i>
        </div>

        <div id="ipcam--view" class="<?php echo $config['preview']['style']; ?>"></div>

        <div id="counter">
            <canvas id="video--sensor"></canvas>
        </div>
        <div class="cheese"></div>
        <div class="w-full h-full top-0 left-0 transform-none bg-contain bg-center bg-no-repeat absolute loaderImage"></div>
        <div class="h-56 flex items-center justify-center pt-12 !text-white opacity-100 !bottom-0 bg-gradient-to-t from-black/30 bg-blend-multiply loading rotarygroup"></div>
    </div>
</div> 