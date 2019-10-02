var initPhotoSwipeFromDOM = function (gallerySelector) {

    var gallery;

    var parseThumbnailElements = function (container) {
        return $(container).find('>a').map(function () {
            let element = $(this);

            let size = element.attr('data-size').split('x');
            let medSize = element.attr('data-med-size').split('x');

            // create slide object
            item = {
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
        }).get();
    };

    var onThumbnailClick = function (ev) {
        ev.preventDefault();

        let element = $(ev.target).closest('a');
        let index = $(gallerySelector).find('>a').index(element);

        openPhotoSwipe(index);
    };

    var openPhotoSwipe = function (index) {
        let pswpElement = $('.pswp').get(0);
        let items = parseThumbnailElements(gallerySelector);

        let options = {
            index: index,

            getThumbBoundsFn: function (index) {
                // See Options->getThumbBoundsFn section of docs for more info
                var thumbnail = items[index].element.children[0],
                    pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                    rect = thumbnail.getBoundingClientRect();

                return {
                    x: rect.left,
                    y: rect.top + pageYScroll,
                    w: rect.width
                };
            },

            shareEl: false,
            zoomEl: false,
            fullscreenEl: false,
        };

        // Pass data to PhotoSwipe and initialize it
        gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

        // see: http://photoswipe.com/documentation/responsive-images.html
        var realViewportWidth,
            useLargeImages = false,
            firstResize = true,
            imageSrcWillChange;

        gallery.listen('beforeResize', function () {

            var dpiRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
            dpiRatio = Math.min(dpiRatio, 2.5);
            realViewportWidth = gallery.viewportSize.x * dpiRatio;


            if (realViewportWidth >= 1200 || (!gallery.likelyTouchDevice && realViewportWidth > 800) || screen.width > 1200) {
                if (!useLargeImages) {
                    useLargeImages = true;
                    imageSrcWillChange = true;
                }

            } else {
                if (useLargeImages) {
                    useLargeImages = false;
                    imageSrcWillChange = true;
                }
            }

            if (imageSrcWillChange && !firstResize) {
                gallery.invalidateCurrItems();
            }

            if (firstResize) {
                firstResize = false;
            }

            imageSrcWillChange = false;

        });

        gallery.listen('gettingData', function (index, item) {
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

        var resetMailForm = function () {
            $('.pswp__qr').removeClass('qr-active').fadeOut('fast');

            photoBooth.resetMailForm();

            $('.send-mail').removeClass('mail-active').fadeOut('fast');
        };

        gallery.listen('beforeChange', resetMailForm);
        gallery.listen('close', resetMailForm);

        gallery.init();
    };

    // QR in gallery
    $('.pswp__button--qrcode').on('click', function (e) {
        e.preventDefault();

        var pswpQR = $('.pswp__qr');

        if (pswpQR.hasClass('qr-active')) {
            pswpQR.removeClass('qr-active').fadeOut('fast');
        } else {
            pswpQR.empty();
            var img = gallery.currItem.src;
            img = img.split('/').pop();

            $('<img>').attr('src', 'api/qrcode.php?filename=' + img).appendTo(pswpQR);

            pswpQR.addClass('qr-active').fadeIn('fast');
        }
    });

    // print in gallery
    $('.pswp__button--print').on('click', function (e) {
        e.preventDefault();

        var img = gallery.currItem.src.split('/').pop();

        photoBooth.printImage(img, () => {
            gallery.close();
        });
    });

    // chroma keying print
    $('.pswp__button--print-chroma-keying').on('click', function (e) {
        e.preventDefault();

        var img = gallery.currItem.src.split('/').pop();

        if (config.chroma_keying) {
            var currentHref = location.href.split('#')[0];;
            var encodedString = btoa(currentHref);

            location = 'chromakeying.php?filename=' + encodeURI(img) + '&location=' + encodeURI(encodedString);
        }
    });

    $('.pswp__button--mail').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var img = gallery.currItem.src.split('/').pop();

        photoBooth.toggleMailDialog(img);
    });

    $(gallerySelector).on('click', onThumbnailClick);
};

