/* exported initPhotoSwipeFromDOM */
/* global photoBooth photoboothTools rotaryController */

// eslint-disable-next-line no-unused-vars
let globalGalleryHandle;

// eslint-disable-next-line no-unused-vars
function initPhotoSwipeFromDOM(gallerySelector) {
    let gallery,
        ssRunning = false,
        ssOnce = false,
        isPrinting = false;

    const ssDelay = config.gallery.pictureTime,
        ssButtonClass = '.pswp__button--playpause';

    const parseThumbnailElements = function (container) {
        return $(container)
            .find('>a')
            .map(function () {
                const element = $(this);

                const size = element.attr('data-size').split('x');
                const medSize = element.attr('data-med-size').split('x');

                // create slide object
                const item = {
                    element: element.get(0),
                    src: element.attr('href'),
                    w: parseInt(size[0], 10),
                    h: parseInt(size[1], 10),
                    msrc: element.find('>img').attr('src'),
                    mediumImage: {
                        src: element.attr('data-med'),
                        w: parseInt(medSize[0], 10),
                        h: parseInt(medSize[1], 10)
                    }
                };

                item.originalImage = {
                    src: item.src,
                    w: item.w,
                    h: item.h
                };

                return item;
            })
            .get();
    };

    const onThumbnailClick = function (ev) {
        ev.preventDefault();

        const element = $(ev.target).closest('a');
        const index = $(gallerySelector).find('>a').index(element);

        // eslint-disable-next-line no-unused-vars
        globalGalleryHandle = openPhotoSwipe(index);
    };

    const openPhotoSwipe = function (index) {
        const pswpElement = $('.pswp').get(0);
        const items = parseThumbnailElements(gallerySelector);

        const options = {
            index: index,

            getThumbBoundsFn: function (thumbIndex) {
                // See Options->getThumbBoundsFn section of docs for more info
                const thumbnail = items[thumbIndex].element.children[0],
                    pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                    rect = thumbnail.getBoundingClientRect();

                return {
                    x: rect.left,
                    y: rect.top + pageYScroll,
                    w: rect.width
                };
            },

            focus: true,
            clickToCloseNonZoomable: config.pswp.clickToCloseNonZoomable,
            closeOnScroll: config.pswp.closeOnScroll,
            closeOnOutsideClick: config.pswp.closeOnOutsideClick,
            preventSwiping: config.pswp.preventSwiping,
            pinchToClose: config.pswp.pinchToClose,
            closeOnVerticalDrag: config.pswp.closeOnVerticalDrag,
            tapToToggleControls: config.pswp.tapToToggleControls,
            animateTransitions: config.pswp.animateTransitions,
            shareEl: false,
            zoomEl: config.pswp.zoomEl,
            fullscreenEl: config.pswp.fullscreenEl,
            counterEl: config.pswp.counterEl,
            history: config.pswp.history,
            loop: config.pswp.loop,
            bgOpacity: config.pswp.bgOpacity
        };

        // Pass data to PhotoSwipe and initialize it
        gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

        // Slideshow not running from the start
        setSlideshowState(ssButtonClass, false);

        // see: http://photoswipe.com/documentation/responsive-images.html
        let realViewportWidth,
            useLargeImages = false,
            firstResize = true,
            imageSrcWillChange;

        gallery.listen('beforeResize', function () {
            let dpiRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
            dpiRatio = Math.min(dpiRatio, 2.5);
            realViewportWidth = gallery.viewportSize.x * dpiRatio;

            if (
                realViewportWidth >= 1200 ||
                (!gallery.likelyTouchDevice && realViewportWidth > 800) ||
                screen.width > 1200
            ) {
                if (!useLargeImages) {
                    useLargeImages = true;
                    imageSrcWillChange = true;
                }
            } else if (useLargeImages) {
                useLargeImages = false;
                imageSrcWillChange = true;
            }

            if (imageSrcWillChange && !firstResize) {
                gallery.invalidateCurrItems();
            }

            if (firstResize) {
                firstResize = false;
            }

            imageSrcWillChange = false;
        });

        gallery.listen('gettingData', function (_index, item) {
            if (useLargeImages) {
                item.src = item.originalImage.src;
                item.w = item.originalImage.w;
                item.h = item.originalImage.h;
            } else {
                item.src = item.mediumImage.src;
                item.w = item.mediumImage.w;
                item.h = item.mediumImage.h;
            }
        });

        gallery.listen('afterChange', function () {
            const img = gallery.currItem.src.split('\\').pop().split('/').pop();

            photoboothTools.console.logDev('Current image: ' + img);

            $('.pswp__button--custom-download').attr({
                href: 'api/download.php?image=' + img,
                download: img
            });

            if (ssRunning && ssOnce) {
                ssOnce = false;
                setTimeout(gotoNextSlide, ssDelay);
            }
        });

        gallery.listen('destroy', function () {
            rotaryController.focusSet('#gallery');
        });

        const resetMailForm = function () {
            $('.pswp__qr').removeClass('qr-active').fadeOut('fast');

            photoBooth.resetMailForm();

            $('.send-mail').removeClass('mail-active').fadeOut('fast');
        };

        const stopSlideshow = function () {
            if (ssRunning) {
                setSlideshowState(ssButtonClass, false);
                $('.pswp__button--playpause').toggleClass(config.icons.slideshow_toggle);
            }
        };

        gallery.listen('beforeChange', resetMailForm);
        gallery.listen('close', resetMailForm);
        gallery.listen('close', stopSlideshow);

        gallery.init();

        rotaryController.focusSet('.pswp');

        return gallery;
    };

    // Delete from DB in gallery
    $('.pswp__button--delete').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        let img = gallery.currItem.src;
        img = img.split('\\').pop().split('/').pop();

        const msg = photoboothTools.getTranslation('really_delete_image');
        const really = config.delete.no_request ? true : confirm(img + ' ' + msg);
        if (really) {
            photoBooth.deleteImage(img, () => {
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, 5000);
            });
        }
    });

    // QR in gallery
    $('.pswp__button--qrcode').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const pswpQR = $('.pswp__qr');

        if (pswpQR.hasClass('qr-active')) {
            pswpQR.removeClass('qr-active').fadeOut('fast');
        } else {
            pswpQR.empty();
            const qrHelpText = config.qr.custom_text
                ? config.qr.text
                : photoboothTools.getTranslation('qrHelp') + '</br><b>' + config.webserver.ssid + '</b>';
            let img = gallery.currItem.src;
            img = img.split('\\').pop().split('/').pop();

            $('<button>')
                .on('click touchstart', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    $('.pswp__qr').empty().removeClass('qr-active').fadeOut('fast');
                })
                .append('<i class="' + config.icons.close + '"></i>')
                .css('float', 'right')
                .appendTo(pswpQR);
            $('<img>')
                .attr('src', 'api/qrcode.php?filename=' + img)
                .appendTo(pswpQR);
            $('<p>')
                .css('max-width', this.width + 'px')
                .html(qrHelpText)
                .appendTo(pswpQR);

            pswpQR.addClass('qr-active').fadeIn('fast');
        }
    });

    // print in gallery
    $('.pswp__button--print').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (isPrinting) {
            photoboothTools.console.log('Printing already in progress!');
        } else {
            isPrinting = true;
            const img = gallery.currItem.src.split('\\').pop().split('/').pop();

            photoBooth.printImage(img, () => {
                gallery.close();
                isPrinting = false;
            });
        }
    });

    // Close Gallery while Taking a Picture or Collage
    $('.closeGallery').on('click', function (e) {
        e.preventDefault();

        if (gallery) {
            photoboothTools.console.logDev('Closing Gallery');
            gallery.close();
        }
    });

    // chroma keying print
    $('.pswp__button--print-chroma-keying').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const img = gallery.currItem.src.split('\\').pop().split('/').pop();

        if (config.keying.enabled) {
            location = 'chromakeying.php?filename=' + encodeURI(img);
        }
    });

    $('.pswp__button--mail').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const img = gallery.currItem.src.split('\\').pop().split('/').pop();

        photoBooth.toggleMailDialog(img);
    });

    /* slideshow management */
    $(ssButtonClass).on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();
        // toggle slideshow on/off
        $('.pswp__button--playpause').toggleClass(config.icons.slideshow_toggle);
        setSlideshowState(this, !ssRunning);
    });

    function setSlideshowState(el, running) {
        if (running) {
            setTimeout(gotoNextSlide, ssDelay / 2.0);
        }
        const title = running ? 'Pause Slideshow' : 'Play Slideshow';
        $(el).prop('title', title);
        ssRunning = running;
    }

    function gotoNextSlide() {
        if (ssRunning && Boolean(gallery)) {
            ssOnce = true;
            gallery.next();
        }
    }

    $(gallerySelector).on('click', onThumbnailClick);

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
