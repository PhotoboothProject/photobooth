/* exported initPhotoSwipeFromDOM */
/* globals photoBooth photoboothTools rotaryController remoteBuzzerClient csrf */

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
            escKey: true,
            arrowKeys: true,
            returnFocus: true,
            initialZoomLevel: 'fit',
            maxZoomLevel: 1,
            pswpModule: PhotoSwipe
        });

        setSlideshowState(ssButtonClass, false);

        gallery.on('change', () => {
            photoboothTools.modal.close();
            if (ssRunning) {
                gotoNextSlide();
            }
        });

        gallery.on('close', () => {
            photoboothTools.modal.close();
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

        gallery.on('uiRegister', () => {
            const orderNumber = [7, 8, 9, 11, 12, 13, 14];

            if (config.print.from_gallery && config.print.limit > 0) {
                gallery.pswp.ui.registerElement({
                    name: 'print-counter',
                    order: 4,
                    onInit: (el) => {
                        $.ajax({
                            method: 'GET',
                            url: 'api/printDB.php',
                            data: {
                                action: 'getPrintCount',
                                [csrf.key]: csrf.token
                            },
                            success: (data) => {
                                el.innerText = photoboothTools.getTranslation('printed') + ' ' + data.count;
                                if (data.locked) {
                                    $('.pswp__print-counter').addClass('error');
                                    $('.pswp__button--print').addClass('error');
                                }
                            },
                            error: () => {
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
                    onInit: (el) => {
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
                    html: `<i class="${config.icons.mail}"></i>`,
                    onClick: (event, el, pswp) => {
                        const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                        photoBooth.showMailForm(img);
                    }
                });
            }

            if (config.print.from_gallery) {
                gallery.pswp.ui.registerElement({
                    name: 'print',
                    ariaLabel: 'print',
                    order: orderNumber.shift(),
                    isButton: true,
                    html: `<i class="${config.icons.print}"></i>`,
                    onClick: async (event, el, pswp) => {
                        event.preventDefault();
                        event.stopPropagation();

                        if (photoboothTools.isPrinting) {
                            photoboothTools.console.log('Printing already in progress!');
                            return;
                        }

                        const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                        const copies = config.print.max_multi === 1 ? 1 : await photoboothTools.askCopies();

                        if (copies && !isNaN(copies)) {
                            photoboothTools.printPayment(img, copies, () => {
                                if (typeof remoteBuzzerClient !== 'undefined') {
                                    remoteBuzzerClient.inProgress(false);
                                }
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
                    html: `<i class="${config.icons.qr}"></i>`,
                    onInit: (el, pswp) => {
                        if (config.qr.pswp !== 'hidden') {
                            pswp.on('change', () => {
                                $('#pswpQR').remove();
                                const imgName = pswp.currSlide.data.src.split(/[\\/]/).pop();
                                const qrWrapper = $('<div id="pswpQR"></div>')
                                    .addClass(`pswp-qrcode ${config.qr.pswp}`);
                                
                                const qrImage = $('<img>')
                                    .addClass('pswp-qrcode__image')
                                    .attr('src', `${environment.publicFolders.api}/qrcode.php?filename=${imgName}`)
                                    .attr('alt', 'QR-Code')
                                    .on('load', () => $('.pswp').append(qrWrapper));

                                qrWrapper.append(qrImage);

                                if (config.qr.short_text) {
                                    $('<p></p>')
                                        .addClass('pswp-qrcode__caption')
                                        .text(config.qr.short_text)
                                        .appendTo(qrWrapper);
                                }
                            });
                        }
                    },
                    onClick: (event, el, pswp) => {
                        const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                        photoBooth.showQrCode(img);
                    }
                });
            }

            if (config.download.enabled) {
                gallery.pswp.ui.registerElement({
                    name: 'custom-download',
                    tagName: 'a',
                    order: orderNumber.shift(),
                    isButton: true,
                    html: `<i class="center ${config.icons.download}"></i>`,
                    onInit: (el, pswp) => {
                        pswp.on('change', () => {
                            const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                            el.href = `${environment.publicFolders.api}/download.php?image=${img}`;
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
                    html: `<i class="center ${config.icons.chroma}"></i>`,
                    onInit: (el, pswp) => {
                        pswp.on('change', () => {
                            const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                            el.href = `${environment.publicFolders.chroma}/chromakeying.php?filename=${img}`;
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
                    html: `<i class="${config.icons.slideshow_play}"></i>`,
                    onClick: () => {
                        $(`${ssButtonClass} i:first`).toggleClass(config.icons.slideshow_toggle);
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
                    html: `<i class="${config.icons.delete}"></i>`,
                    onClick: async (event, el, pswp) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const img = pswp.currSlide.data.src.split(/[\\/]/).pop();
                        const msg = photoboothTools.getTranslation('really_delete_image');
                        const really = config.delete.no_request ? true : await photoboothTools.confirm(`${img} ${msg}`);
                        
                        if (really) {
                            photoBooth.deleteImage(img, () => {
                                setTimeout(() => photoboothTools.reloadPage(), config.ui.notification_timeout * 1000);
                            });
                        }
                    }
                });
            }
        });

        gallery.on('afterInit', () => {
            $('.pswp__button').addClass('rotaryfocus');
            if (!config.no_request) {
                $('.pswp__button--delete').removeClass('rotaryfocus');
            }
            if ($('.pswp').hasClass('pswp--touch')) {
                $('.pswp__button--arrow--prev, .pswp__button--arrow--next').removeClass('rotaryfocus');
            }
            $('.pswp__button--close').html(`<i class="${config.icons.close}"></i>`);
            if (config.pswp.zoomEl) {
                $('.pswp__button--zoom').html(`<i class="${config.icons.zoom}"></i>`);
            }
            if (config.qr.enabled && config.qr.pswp !== 'hidden') {
                $('.pswp__button--qrcode').hide();
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

        function gotoNextSlide() {
            clearTimeout(ssTimeOut);
            if (ssRunning && gallery) {
                ssTimeOut = setTimeout(() => gallery.pswp.next(), ssDelay);
            }
        }

        function setSlideshowState(el, running) {
            $(el).prop('title', running ? 'Pause Slideshow' : 'Play Slideshow');
            ssRunning = running;
            gotoNextSlide();
        }

        return gallery;
    };

    $(gallerySelector).on('click', (e) => {
        e.preventDefault();
        const $links = $(gallerySelector).find('>a');
        if ($links.length > 0) {
            const index = $links.index($(e.target).closest('a'));
            globalGalleryHandle = openPhotoSwipe(gallerySelector, index);
        }
    });

    $(document).on('keyup', (ev) => {
        if (config.print.from_gallery && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
            if (photoboothTools.isPrinting) {
                photoboothTools.console.log('Printing already in progress!');
            } else if ($('#gallery').hasClass('gallery--open')) {
                $('.pswp__button--print').trigger('click');
            }
        }
    });
}
