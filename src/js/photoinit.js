/* exported initPhotoSwipeFromDOM */
/* global photoBooth photoboothTools rotaryController remoteBuzzerClient */

// eslint-disable-next-line no-unused-vars
let globalGalleryHandle;

// eslint-disable-next-line no-unused-vars
function initPhotoSwipeFromDOM(gallerySelector) {
    let gallery,
        ssTimeOut,
        ssRunning = false;

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

            if (ssRunning) {
                gotoNextSlide();
            }
        });

        gallery.listen('destroy', function () {
            rotaryController.focusSet('#gallery');
        });

        const resetMailForm = function () {
            photoboothTools.modal.close('#qrPswp');
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
                }, config.ui.notification_timeout * 1000);
            });
        }
    });

    // QR in gallery
    $('.pswp__button--qrcode').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const image = gallery.currItem.src.split('\\').pop().split('/').pop();
        photoBooth.showQr('#qrPswp', image);
        photoboothTools.modal.toggle('#qrPswp');
    });

    // print in gallery
    $('.pswp__button--print').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (photoboothTools.isPrinting) {
            photoboothTools.console.log('Printing already in progress!');
        } else {
            const img = gallery.currItem.src.split('\\').pop().split('/').pop();

            photoboothTools.printImage(img, () => {
                if (typeof remoteBuzzerClient !== 'undefined') {
                    remoteBuzzerClient.inProgress(false);
                }
                gallery.close();
            });
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
        const title = running ? 'Pause Slideshow' : 'Play Slideshow';
        $(el).prop('title', title);
        ssRunning = running;
        gotoNextSlide();
    }

    function gotoNextSlide() {
        clearTimeout(ssTimeOut);
        if (ssRunning && Boolean(gallery)) {
            ssTimeOut = setTimeout(function () {
                gallery.next();
            }, ssDelay);
        }
    }

    $(gallerySelector).on('click', onThumbnailClick);

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
