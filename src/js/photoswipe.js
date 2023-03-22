/* exported initPhotoSwipeFromDOM */
/* global photoBooth photoboothTools rotaryController */

// eslint-disable-next-line no-unused-vars
let PhotoSwipeLightbox,
    ssRunning = false,
    ssOnce = false,
    isPrinting = false;

const ssDelay = config.gallery.pictureTime,
    ssButtonClass = '.pswp__button--playpause';

// eslint-disable-next-line no-unused-vars
function initPhotoSwipeFromDOM(gallerySelector) {
    const gallery = new PhotoSwipeLightbox({
        gallery: gallerySelector,
        children: 'a',
        bgOpacity: config.pswp.bgOpacity,
        loop: config.pswp.loop,
        pinchToClose: config.pswp.pinchToClose,
        closeOnVerticalDrag: config.pswp.closeOnVerticalDrag,
        clickToCloseNonZoomable: config.pswp.clickToCloseNonZoomable,
        counter: config.pswp.counterEl,
        zoom: config.pswp.zoomEl,
        tapAction: config.pswp.tapToToggleControls ? 'close' : false,
        bgClickAction: config.pswp.closeOnOutsideClick ? 'close' : false,

        wheelToZoom: true,
        // eslint-disable-next-line object-property-newline
        //padding: {top: 20, bottom: 40, left: 100, right: 100},
        escKey: true,
        arrowKeys: true,

        returnFocus: true,
        initialZoomLevel: 'fit',
        maxZoomLevel: 1,

        // dynamic import is not supported in UMD version
        pswpModule: PhotoSwipe
    });

    // Slideshow not running from the start
    setSlideshowState(ssButtonClass, false);

    gallery.on('change', function () {
        photoBooth.resetMailForm();
        $('.send-mail').removeClass('mail-active').fadeOut('fast');
        photoboothTools.modal.close('#qrPswp');
        if (ssRunning && ssOnce) {
            ssOnce = false;
            setTimeout(gotoNextSlide, ssDelay);
        }
    });

    gallery.on('close', function () {
        photoBooth.resetMailForm();
        $('.send-mail').removeClass('mail-active').fadeOut('fast');
        photoboothTools.modal.close('#qrPswp');
        if (ssRunning) {
            setSlideshowState(ssButtonClass, false);
            $('.pswp__button--playpause i:first').toggleClass(config.icons.slideshow_toggle);
        }
    });

    gallery.on('uiRegister', function () {
        // counter - 5, zoom button - 10, info - 15, close - 20.
        const orderNumber = [7, 8, 9, 11, 12, 13, 14];

        if (config.mail.enabled) {
            gallery.pswp.ui.registerElement({
                name: 'mail',
                ariaLabel: 'mail',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class="' + config.icons.mail + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onClick: (event, el, pswp) => {
                    $('.pswp').append($('.send-mail'));
                    photoBooth.resetMailForm();
                    photoBooth.toggleMailDialog(pswp.currSlide.data.src.split('\\').pop().split('/').pop());
                }
            });
        }

        if (config.print.from_gallery) {
            gallery.pswp.ui.registerElement({
                name: 'print',
                ariaLabel: 'print',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class="' + config.icons.print + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onClick: (event, el, pswp) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (isPrinting) {
                        photoboothTools.console.log('Printing already in progress!');
                    } else {
                        isPrinting = true;
                        const img = pswp.currSlide.data.src.split('\\').pop().split('/').pop();

                        photoBooth.printImage(img, () => {
                            isPrinting = false;
                            pswp.close();
                        });
                    }
                }
            });
        }

        if (config.qr.enabled) {
            gallery.pswp.ui.registerElement({
                name: 'qrcode',
                ariaLabel: 'qrcode',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class="' + config.icons.qr + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onInit: (el, pswp) => {
                    photoboothTools.modal.empty('#qrPswp');
                },
                // eslint-disable-next-line no-unused-vars
                onClick: (event, el, pswp) => {
                    const image = pswp.currSlide.data.src.split('\\').pop().split('/').pop();
                    photoBooth.showQr('#qrPswp', image);
                    photoboothTools.modal.toggle('#qrPswp');
                }
            });
        }

        if (config.download.enabled) {
            gallery.pswp.ui.registerElement({
                name: 'custom-download',
                tagName: 'a',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class=" center ' + config.icons.download + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onInit: (el, pswp) => {
                    pswp.on('change', () => {
                        el.href =
                            'api/download.php?image=' + pswp.currSlide.data.src.split('\\').pop().split('/').pop();
                    });
                }
            });
        }

        if (config.keying.enabled) {
            gallery.pswp.ui.registerElement({
                name: 'print-chroma-keying',
                tagName: 'a',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class=" center ' + config.icons.chroma + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onInit: (el, pswp) => {
                    pswp.on('change', () => {
                        el.href =
                            'chromakeying.php?filename=' + pswp.currSlide.data.src.split('\\').pop().split('/').pop();
                    });
                }
            });
        }

        if (config.gallery.use_slideshow) {
            gallery.pswp.ui.registerElement({
                name: 'playpause',
                ariaLabel: 'Slideshow',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class="' + config.icons.slideshow_play + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onClick: (event, el, pswp) => {
                    // toggle slideshow on/off
                    $('.pswp__button--playpause i:first').toggleClass(config.icons.slideshow_toggle);
                    setSlideshowState(ssButtonClass, !ssRunning);
                }
            });
        }

        if (config.gallery.allow_delete) {
            gallery.pswp.ui.registerElement({
                name: 'delete',
                ariaLabel: 'delete',
                order: orderNumber.shift(),
                isButton: true,
                html: '<i class="' + config.icons.delete + '"></i>',
                // eslint-disable-next-line no-unused-vars
                onClick: (event, el, pswp) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const img = pswp.currSlide.data.src.split('\\').pop().split('/').pop();
                    const msg = photoboothTools.getTranslation('really_delete_image');
                    const really = config.delete.no_request ? true : confirm(img + ' ' + msg);
                    if (really) {
                        photoBooth.deleteImage(img, () => {
                            setTimeout(() => {
                                photoboothTools.reloadPage();
                            }, config.ui.notification_timeout * 1000);
                        });
                    }
                }
            });
        }
    });

    gallery.on('afterInit', () => {
        // photoswipe fully initialized and opening transition is running (if available)
        $('.pswp__button').addClass('rotaryfocus');
    });
    gallery.init();
    rotaryController.focusSet('.pswp');

    /* slideshow management */
    function gotoNextSlide() {
        const pswp = gallery.pswp;
        if (ssRunning && Boolean(gallery)) {
            ssOnce = true;
            // eslint-disable-next-line no-unused-vars
            pswp.next();
        }
    }

    function setSlideshowState(el, running) {
        if (running) {
            setTimeout(gotoNextSlide, ssDelay / 2.0);
        }
        const title = running ? 'Pause Slideshow' : 'Play Slideshow';
        $(el).prop('title', title);
        ssRunning = running;
    }

    // Close Gallery while Taking a Picture or Collage
    $('.closeGallery').on('click', (e) => {
        e.preventDefault();

        if (gallery) {
            photoboothTools.console.logDev('Closing Gallery');
            gallery.close();
        }
    });

    $(document).on('keyup', function (ev) {
        if (config.print.from_gallery && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
            if (isPrinting) {
                photoboothTools.console.log('Printing already in progress!');
            } else if ($('#gallery').hasClass('gallery--open') && typeof gallery !== 'undefined') {
                $('.pswp__button--print').trigger('click');
            }
        }
    });
}
