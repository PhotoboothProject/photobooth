<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp rotarygroup" tabindex="-1" role="dialog" aria-hidden="true">

    <!-- Background of PhotoSwipe.
    It's a separate element, as animating opacity is faster than rgba(). -->
    <div class="pswp__bg"></div>

    <!-- Slides wrapper with overflow:hidden. -->
    <div class="pswp__scroll-wrap">

        <!-- Container that holds slides.
        PhotoSwipe keeps only 3 of them in DOM to save memory.
        Don't modify these 3 pswp__item elements, data is added later on. -->
        <div class="pswp__container">
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>

        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
        <div class="pswp__ui pswp__ui--hidden">
            <div class="pswp__top-bar">
                <!--  Controls are self-explanatory. Order can be changed. -->

                <div class="pswp__counter"></div>
                <button class="pswp__button pswp__button--close rotaryfocus" title="Close (Esc)"><i class="fa fa-times"></i></button>
                <button class="pswp__button pswp__button--share rotaryfocus" title="Share"><i class="fa fa-share-alt" aria-hidden="true"></i></button>
                <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"><i class="fa fa-arrows-alt" aria-hidden="true"></i></button>
                <button class="pswp__button pswp__button--zoom rotaryfocus" title="Zoom in/out"><i class="fa fa-search-plus" aria-hidden="true"></i></button>

                <!-- custom buttons: -->
                <?php if ($config['mail']['enabled']): ?>
                <button type="button" class="pswp__button pswp__button--mail rotaryfocus" title="Send Email"><i class="fa fa-envelope"></i></button>
                <?php endif; ?>

                <?php if ($config['print']['from_gallery']): ?>
                <button type="button" class="pswp__button pswp__button--print rotaryfocus" title="Print"><i class="fa fa-print"></i></button>
                <?php endif; ?>

                <?php if ($config['qr']['enabled']): ?>
                <button type="button" class="pswp__button pswp__button--qrcode rotaryfocus" title="QR Code"><i class="fa fa-qrcode"></i></button>
                <?php endif; ?>

		<?php if ($config['download']['enabled'] && ($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR'] || $config['dev']['enabled'])): ?>
		<!-- <button type="button" class="pswp__button pswp__button--download" title="Download"><i class="fa fa-download"></i></button> -->
		<a href="" download="" class="pswp__button pswp__button--custom-download rotaryfocus" title="Download"><i class="fa fa-download"></i></a>
                <?php endif; ?>

                <?php if ($config['keying']['enabled']): ?>
                <button type="button" class="pswp__button pswp__button--print-chroma-keying rotaryfocus" title="Chroma Key"><i class="fa fa-paint-brush"></i></button>
                <?php endif; ?>

                <?php if ($config['gallery']['use_slideshow']): ?>
                <button type="button" class="pswp__button pswp__button--playpause fa fa-play rotaryfocus" title="Play Slideshow"></button>
                <?php endif; ?>

                <?php if ($config['gallery']['allow_delete']): ?>
                <button type="button" class="pswp__button pswp__button--delete <?php if ($config['delete']['no_request']){ echo 'rotaryfocus';} ?> " title="Delete"><i class="fa fa-trash"></i></button>
                <?php endif; ?>

                <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
                <!-- element will get class pswp__preloader--active when preloader is running -->
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                        <div class="pswp__preloader__cut">
                            <div class="pswp__preloader__donut"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                <div class="pswp__share-tooltip"></div>
            </div>

            <button class="pswp__button pswp__button--arrow--left rotaryfocus" title="Previous (arrow left)">
            </button>

            <button class="pswp__button pswp__button--arrow--right rotaryfocus" title="Next (arrow right)">
            </button>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>
        </div>
    </div>
    <div class="pswp__qr">

    </div>
</div>
