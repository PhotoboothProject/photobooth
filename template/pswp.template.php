<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

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
                <button class="pswp__button pswp__button--close" title="Close (Esc)"><i
                        class="fa fa-times"></i></button>
                <button class="pswp__button pswp__button--share" title="Share"></button>
                <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>

                <?php if ($config['use_mail']): ?>
                <button class="pswp__button pswp__button--mail" title="Send Email"><i class="fa fa-envelope"></i></button>
                <?php endif; ?>

                <?php if ($config['use_print']): ?>
                <button class="pswp__button pswp__button--print" title="Print"><i class="fa fa-print"></i></button>
                <?php endif; ?>

                <?php if ($config['use_qr']): ?>
                <button class="pswp__button pswp__button--qrcode" title="QR Code"><i class="fa fa-qrcode"></i></button>
                <?php endif; ?>

                <?php if ($config['use_download'] && ($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR'] || $config['dev'])): ?>
                <a href="" download="" class="pswp__button pswp__button--download" title="Download"><i class="fa fa-download"></i></a>
                <?php endif; ?>

                <?php if ($config['chroma_keying']): ?>
                <button class="pswp__button pswp__button--print-chroma-keying" title="Chroma Key"><i class="fa fa-paint-brush"></i></button>
                <?php endif; ?>

                <!-- custom slideshow button: -->
                <button class="pswp__button pswp__button--playpause" title="Play Slideshow"></button>

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

            <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
            </button>

            <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
            </button>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>
        </div>
    </div>
    <div class="pswp__qr">

    </div>
</div>
