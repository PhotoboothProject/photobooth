/* exported initPhotoSlideFromDOM */
/* global photoboothTools */

// eslint-disable-next-line no-unused-vars
let ssTimeOut,
    ssRunning = false,
    lastDBSize = -1;

const ssDelay = config.slideshow.pictureTime,
    ssButtonClass = '.pswp__button--playpause',
    interval = 1000 * config.slideshow.refreshTime,
    ajaxurl = config.foldersJS.api + '/gallery.php?status';

// eslint-disable-next-line no-unused-vars
function initPhotoSlideFromDOM(gallerySelector) {
    const gallery = new PhotoSwipeLightbox({
        gallery: gallerySelector,
        children: 'a',
        allowPanToNext: true,
        spacing: 0.1,
        loop: true,
        pinchToClose: false,
        closeOnVerticalDrag: false,
        hideAnimationDuration: 333,
        showAnimationDuration: 333,
        zoomAnimationDuration: 333,
        escKey: false,
        close: false,
        zoom: false,
        arrowKeys: true,
        returnFocus: true,
        maxWidthToAnimate: 4000,
        clickToCloseNonZoomable: false,
        imageClickAction: 'toggle-controls',
        bgClickAction: 'toggle-controls',
        tapAction: 'toggle-controls',
        doubleTapAction: 'toggle-controls',
        indexIndicatorSep: ' / ',
        preloaderDelay: 2000,
        bgOpacity: 0.8,

        index: 0,
        errorMsg: 'The image cannot be loaded',
        preload: [1, 2],
        easing: 'cubic-bezier(.4,0,.22,1)',

        // dynamic import is not supported in UMD version
        pswpModule: PhotoSwipe
    });

    // Slideshow not running from the start
    setSlideshowState(ssButtonClass, false);

    gallery.on('change', function () {
        if (ssRunning) {
            gotoNextSlide();
        }
    });

    gallery.on('close', function () {
        if (ssRunning) {
            setSlideshowState(ssButtonClass, false);
            $('.pswp__button--playpause i:first').toggleClass(config.icons.slideshow_toggle);
        }
    });

    gallery.on('uiRegister', function () {
        // Order of element, default order elements: counter - 5, zoom button - 10, info - 15, close - 20.
        if (config.pswp.caption) {
            gallery.pswp.ui.registerElement({
                name: 'custom-caption',
                order: 6,
                isButton: false,
                appendTo: 'root',
                html: 'Caption text',
                // eslint-disable-next-line no-unused-vars
                onInit: (el, pswp) => {
                    gallery.pswp.on('change', () => {
                        const currSlideElement = gallery.pswp.currSlide.data.element;
                        let captionHTML = '';
                        if (currSlideElement) {
                            captionHTML = currSlideElement.querySelector('img').getAttribute('alt');
                        }
                        el.innerHTML = captionHTML || '';
                    });
                }
            });
        }
        gallery.pswp.ui.registerElement({
            name: 'playpause',
            ariaLabel: 'Slideshow',
            order: 18,
            isButton: true,
            html: '<i class="' + config.icons.slideshow_play + '"></i>',
            // eslint-disable-next-line no-unused-vars
            onClick: (event, el, pswp) => {
                // toggle slideshow on/off
                $('.pswp__button--playpause i:first').toggleClass(config.icons.slideshow_toggle);
                setSlideshowState(ssButtonClass, !ssRunning);
            }
        });
        gallery.pswp.ui.registerElement({
            name: 'reload',
            ariaLabel: 'Reload',
            order: 19,
            isButton: true,
            html: '<i class="' + config.icons.refresh + '"></i>',
            // eslint-disable-next-line no-unused-vars
            onClick: (event, el, pswp) => {
                // Stop slideshow
                setSlideshowState(ssButtonClass, false);

                // Reload page
                photoboothTools.reloadPage();
            }
        });
    });

    gallery.on('afterInit', () => {
        // photoswipe fully initialized and opening transition is running (if available)
        if ($('#galimages').children('a').length > 0) {
            $('.pswp__button--playpause i:first').toggleClass(config.icons.slideshow_toggle);
            setSlideshowState(ssButtonClass, !ssRunning);
        }
    });

    gallery.init();
    if ($('#galimages').children('a').length > 0) {
        gallery.loadAndOpen(0, {
            gallery: document.querySelector('#galimages')
        });
    }

    /* slideshow management */
    function gotoNextSlide() {
        clearTimeout(ssTimeOut);
        if (ssRunning && Boolean(gallery)) {
            ssTimeOut = setTimeout(function () {
                gallery.pswp.next();
            }, ssDelay);
        }
    }

    function setSlideshowState(el, running) {
        const title = running ? 'Pause Slideshow' : 'Play Slideshow';
        $(el).prop('title', title);
        ssRunning = running;
        gotoNextSlide();
    }
}

// Init on domready
$(function () {
    initPhotoSlideFromDOM('#galimages');
    if (config.gallery.scrollbar) {
        $('#gallery').addClass('scrollbar');
    }

    const reloadElement = $('<a class="btn btn--' + config.ui.button + ' gallery__reload rotaryfocus">');
    reloadElement.append('<i class="' + config.icons.refresh + '"></i>');
    reloadElement.attr('href', '#');
    reloadElement.on('click', () => photoboothTools.reloadPage());
    reloadElement.appendTo('.gallery__header');

    $('#gallery').addClass('gallery--open');

    function dbUpdated() {
        photoboothTools.console.log('DB is updated - refreshing');
        //location.reload(true); //Alternative
        photoboothTools.reloadPage();
    }

    const checkForUpdates = function () {
        $.getJSON({
            url: ajaxurl,
            success: function (result) {
                const currentDBSize = result.dbsize;
                if (lastDBSize != currentDBSize && lastDBSize != -1) {
                    dbUpdated();
                }
                lastDBSize = currentDBSize;
            }
        });
    };
    setInterval(checkForUpdates, interval);
});
