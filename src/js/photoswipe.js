/* exported initPhotoSwipeFromDOM */
/* globals photoBooth photoboothTools rotaryController remoteBuzzerClient */

// eslint-disable-next-line no-unused-vars
let globalGalleryHandle;

// eslint-disable-next-line no-unused-vars
function initPhotoSwipeFromDOM(gallerySelector) {
    let ssTimeOut,
        ssRunning = false;

    const ssDelay = config.gallery.pictureTime,
        ssButtonClass = '.pswp__button--playpause',
        actionImageClick = config.pswp.imageClickAction === 'none' ? false : config.pswp.imageClickAction,
        actionBgClick = config.pswp.bgClickAction === 'none' ? false : config.pswp.bgClickAction,
        actionTap = config.pswp.tapAction === 'none' ? false : config.pswp.tapAction,
        actionDoubleTap = config.pswp.doubleTapAction === 'none' ? false : config.pswp.doubleTapAction;

    const openPhotoSwipe = function (selector, galIndex) {
        const gallery = new PhotoSwipeLightbox({
            mainClass: 'rotarygroup',
            gallery: selector,
            children: 'a',
            bgOpacity: config.pswp.bgOpacity,
            loop: config.pswp.loop,
            pinchToClose: config.pswp.pinchToClose,
            closeOnVerticalDrag: config.pswp.closeOnVerticalDrag,
            clickToCloseNonZoomable: config.pswp.clickToCloseNonZoomable,
            counter: config.pswp.counterEl,
            zoom: config.pswp.zoomEl,
            imageClickAction: actionImageClick,
            bgClickAction: actionBgClick,
            tapAction: actionTap,
            doubleTapAction: actionDoubleTap,

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
            if (ssRunning) {
                gotoNextSlide();
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
            if (typeof rotaryController !== 'undefined') {
                setTimeout(() => {
                    rotaryController.focusSet('#gallery');
                }, 300);
            }
        });

        gallery.on('uiRegister', function () {
            // counter - 5, zoom button - 10, info - 15, close - 20.
            const orderNumber = [7, 8, 9, 11, 12, 13, 14];

            if (config.print.from_gallery && config.print.limit > 0) {
                gallery.pswp.ui.registerElement({
                    name: 'print-counter',
                    order: 4,
                    // eslint-disable-next-line no-unused-vars
                    onInit: (el, pswp) => {
                        $.ajax({
                            method: 'GET',
                            url: 'api/printDB.php',
                            data: {
                                action: 'getPrintCount'
                            },
                            success: (data) => {
                                el.innerText = photoboothTools.getTranslation('printed') + ' ' + data.count;
                                if (data.locked) {
                                    $('.pswp__print-counter').addClass('error');
                                    $('.pswp__button--print').addClass('error');
                                }
                            },
                            // eslint-disable-next-line no-unused-vars
                            error: (jqXHR, textStatus) => {
                                $('.pswp__print-counter').addClass('warning');
                                el.innerText = photoboothTools.getTranslation('printed') + ' unknown';
                                $('.pswp__button--print').addClass('warning');
                            }
                        });
                    }
                });
            }

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

                        if (photoboothTools.isPrinting) {
                            photoboothTools.console.log('Printing already in progress!');
                        } else {
                            const img = pswp.currSlide.data.src.split('\\').pop().split('/').pop();

                            photoboothTools.printImage(img, () => {
                                if (typeof remoteBuzzerClient !== 'undefined') {
                                    remoteBuzzerClient.inProgress(false);
                                }
                                pswp.close();
                            });
                        }
                    }
                });
            }

            if (config.qr.enabled) {
                gallery.pswp.ui.registerElement({
                    name: 'qrPswp',
                    className: 'modal',
                    appendTo: 'root',
                    // eslint-disable-next-line no-unused-vars
                    onInit: (el, pswp) => {
                        el.setAttribute('id', 'qrPswp');
                    }
                });

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
                                config.foldersJS.api +
                                '/download.php?image=' +
                                pswp.currSlide.data.src.split('\\').pop().split('/').pop();
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
                                config.foldersJS.chroma +
                                '/chromakeying.php?filename=' +
                                pswp.currSlide.data.src.split('\\').pop().split('/').pop();
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
            if (config.qr.enabled) {
                $('#qrPswp').html('<div class="modal__body shape--' + config.ui.style + '"></div>');
            }
            $('.pswp__button').addClass('rotaryfocus');
            if (!config.no_request) {
                $('.pswp__button--delete').removeClass('rotaryfocus');
            }
            if ($('.pswp').hasClass('pswp--touch')) {
                $('.pswp__button--arrow--prev').removeClass('rotaryfocus');
                $('.pswp__button--arrow--next').removeClass('rotaryfocus');
            }
            $('.pswp__button--close').empty();
            $('.pswp__button--close').html('<i class="' + config.icons.close + '"></i>');
            if (config.pswp.zoomEl) {
                $('.pswp__button--zoom').empty();
                $('.pswp__button--zoom').html('<i class="' + config.icons.zoom + '"></i>');
            }
            if (typeof rotaryController !== 'undefined') {
                rotaryController.focusSet('.pswp');
            }
        });
        gallery.init();
        if ($(gallerySelector).children('a').length > 0) {
            gallery.loadAndOpen(galIndex, {
                gallery: document.querySelector(gallerySelector)
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

        return gallery;
    };

    $(gallerySelector).on('click', function (e) {
        e.preventDefault();
        if ($(gallerySelector).children('a').length > 0) {
            const element = $(e.target).closest('a');
            const index = $(gallerySelector).find('>a').index(element);
            globalGalleryHandle = openPhotoSwipe(gallerySelector, index);
        }
    });

    $(document).on('keyup', function (ev) {
        if (config.print.from_gallery && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
            if (photoboothTools.isPrinting) {
                photoboothTools.console.log('Printing already in progress!');
            } else if ($('#gallery').hasClass('gallery--open') && typeof gallery !== 'undefined') {
                $('.pswp__button--print').trigger('click');
            }
        }
    });
}
