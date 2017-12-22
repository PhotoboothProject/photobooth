var L10N = {};
var photoBooth = (function () {
    config = {};
    // vars
    var public = {},
        loader = $('#loader'),
        startPage = $('#start'),
        countDown = 5,
        timeToLive = 90000,
        qr = false,
        timeOut,
        saving = false,
        gallery = $('#gallery'),
        processing = false,
        pswp = {},
        resultPage = $('#result');

    // timeOut function
    public.resetTimeOut = function () {
        timeOut = setTimeout(function () {
            window.location = window.location.origin;
        }, timeToLive);
    }

    // reset whole thing
    public.reset = function () {
        loader.hide();
        qr = false;
        $('.qr').html('').hide();
        $('.qrbtn').removeClass('active').attr('style', '');
        $('.loading').text('');
        gallery.removeClass('open');
        $('.galInner').hide();
        $('.resultInner').css({
            'bottom': '-100px'
        });
        $('.spinner').hide();
    }

    // init
    public.init = function (options) {
        public.l10n();
        public.reset();
        var w = window.innerWidth;
        var h = window.innerHeight;
        $('#wrapper').width(w).height(h);
        $('.galleryInner').width(w * 3);
        $('.galleryInner').height(h);
        $('.galleryInner div').width(w);
        $('.galleryInner').css({
            'left': -w
        });
        loader.width(w).height(h);
        $('.stages').hide();
        public.initPhotoSwipeFromDOM('#galimages');

        startPage.show();
    }

    // check for resizing
    public.handleResize = function () {
        var w = window.innerWidth;
        var h = window.innerHeight;
        $('#wrapper').width(w).height(h);
        $('#loader').width(w).height(h);
    }

    public.l10n = function (elem) {
        elem = $(elem || 'body');
        elem.find('[data-l10n]').each(function (i, item) {
            item = $(item);
            item.html(L10N[item.data('l10n')]);
        });
    }

    // Cheese
    public.cheese = function () {
        $('#counter').text('');
        $('.loading').text(L10N.cheese);
        public.takePic();
    }

    // take Picture
    public.takePic = function () {
        processing = true;
        setTimeout(function () {
            $('#counter').text('');
            $('.spinner').show();
            $('.loading').text(L10N.busy);
        }, 1000);
        $.ajax({
            url: 'takePic.php',
            dataType: "json",
            cache: false,
            success: function (result) {
                if (result.error) {
                    public.errorPic(result);
                } else {
                    public.renderPic(result);
                }
            },
            error: function (xhr, status, error) {
                public.errorPic(result);
            }
        });
    }

    // Show error Msg and reset
    public.errorPic = function (result) {
        setTimeout(function () {
            $('.spinner').hide();
            $('.loading').html(L10N.error + '<a class="btn" href="/">' + L10N.reload + '</a>');
        }, 1100);
    }

    // Render Picture after taking
    public.renderPic = function (result) {
        // Add QR Code Image
        $('.qr').html('');
        $('<img src="qrcode.php?filename=' + result.img + '"/>').load(function () {
            $(this).appendTo($('.qr'));
            $('<p>').html(L10N.qrHelp).appendTo($('.qr'));
        });

        // Add Print Link
        $(document).off('click touchstart', '.printbtn');
        $(document).on('click touchstart', '.printbtn', function (e) {
            e.preventDefault();
            $.ajax({
                url: 'print.php?filename=' + encodeURI(result.img),
            }).done(function (data) {
                console.log(data)
            })
        });

        // Add Image to gallery and slider
        public.addImage(result.img);

        // Add Image
        $('<img src="/images/' + result.img + '" class="original">').load(function () {
            $('#result').css({
                'background-image': 'url(/images/' + result.img + ')'
            });
            startPage.fadeOut(400, function () {
                resultPage.fadeIn(400, function () {
                    setTimeout(function () {
                        processing = false;
                        loader.slideUp('fast');
                    }, 400);
                    setTimeout(function () {
                        $('.resultInner').stop().animate({
                            'bottom': '50px'
                        }, 400).removeClass('hidden');
                    }, 400);
                    clearTimeout(timeOut);
                    public.resetTimeOut();
                });
            });
        });
    }

    // add image to Gallery
    public.addImage = function (image) {
        // fixme: set to appendTo, if new images should appear at the end, or to prependTo, if new images should appear at the beginning
        var $node = $('<a>').html('<img src="/thumbs/' + image + '" />').data('size', '1920x1280').attr('href', '/images/' + image + '?new=1')
        if (gallery_newest_first) {
            $node.prependTo($('#galimages'));
        } else {
            $node.appendTo($('#galimages'));
        }
    }

    // Open Gallery Overview
    public.openGallery = function (elem) {
        var pos = elem.offset();
        gallery.css({
                'left': pos.left,
                'top': pos.top
            })
            .data('left', pos.left)
            .data('top', pos.top)
            .addClass('open')
            .animate({
                'width': '102%',
                'height': '100%',
                'top': 0,
                'left': 0
            }, 300, function () {
                $('.galInner').show();
                gallery.css({
                    'overflow-y': 'scroll'
                });
            });
    }

    $(window).resize(public.handleResize);

    // Open QR Code in Gallery

    // Take Picture Button
    $('.takePic, .newpic').click(function (e) {
        e.preventDefault();
        var target = $(e.target);
        if (target.hasClass('gallery')) {
            public.openGallery(target);
        } else {
            if (!processing) {
                public.reset();
                loader.slideDown('slow', 'easeOutBounce', function () {
                    public.countdown(countDown, $('#counter'));
                });
            }
        }
    });

    // Open Gallery Button
    $('#result .gallery, #start .gallery').click(function (e) {
        e.preventDefault();
        public.openGallery($(this));
    });

    // Close Gallery Overview
    $('.close_gal').click(function (e) {
        e.preventDefault();
        $('.galInner').hide();
        gallery.css({
            'overflow-y': 'visible'
        });
        $('#gallery').animate({
            'width': '200px',
            'height': '70px',
            'left': $('#gallery').data('left'),
            'top': $('#gallery').data('top')
        }, 300, function () {
            $('#gallery').removeClass('open');
        });
    });

    $('.tabbox ul li').click(function () {
        var elem = $(this),
            target = $('.' + elem.data('target'));
        if (!elem.hasClass('active')) {
            $('.tabbox ul li').removeClass('active');
            $('.tab').removeClass('active');
            elem.addClass('active');
            target.addClass('active');
        }
    });
    // QR in gallery
    $(document).on('click touchstart', '.gal-qr-code', function (e) {
        e.preventDefault();

        var pswpQR = $('.pswp__qr');
        if (pswpQR.hasClass('qr-active')) {
            pswpQR.removeClass('qr-active').fadeOut('fast');
        } else {
            pswpQR.empty();
            var img = pswp.currItem.src;
            img = img.replace('/images/', '');
            $('<img>').attr('src', 'qrcode.php?filename=' + img).appendTo(pswpQR);

            pswpQR.addClass('qr-active').fadeIn('fast');
        }
    });
    // print in gallery
    $(document).on('click touchstart', '.gal-print', function (e) {
        e.preventDefault();
        var img = pswp.currItem.src;
        img = img.replace('images/', '');
        $.ajax({
            url: 'print.php?filename=' + encodeURI(img),
        }).done(function (data) {
            console.log(data)
        })
    });

    $('#result').click(function (e) {
        var target = $(e.target);

        // Menü in and out
        if (!target.hasClass('qrbtn') && target.closest('.qrbtn').length == 0 && !target.hasClass('newpic') && !target.hasClass('printbtn') && target.closest('.printbtn').length == 0 && !target.hasClass('resetBtn') && !target.hasClass('gallery') && qr != true && !target.hasClass('homebtn')) {
            if ($('.resultInner').hasClass('hidden')) {
                $('.resultInner').stop().animate({
                    'bottom': '50px'
                }, 400).removeClass('hidden');
            } else {
                $('.resultInner').stop().animate({
                    'bottom': '-100px'
                }, 400).addClass('hidden');
            }
        }

        if (qr && !target.hasClass('qrbtn')) {
					var qrpos = $('.qrbtn').offset(),
						qrbtnwidth = $('.qrbtn').outerWidth(),
						qrbtnheight = $('.qrbtn').outerHeight()
						$('.qr').removeClass('active');
            $('.qr').animate({
							'width': qrbtnwidth,
							'height': qrbtnheight,
							'left': qrpos.left,
							'top': qrpos.top,
							'margin-left': 0,
            }, 250, function(){
							$('.qr').hide();
						});
						qr = false;
        }

        // Go to Home
        if (target.hasClass('homebtn')) {
            window.location = window.location.origin;
        }

        // Qr in and out
        if (target.hasClass('qrbtn') || target.closest('.qrbtn').length > 0) {

						var qrpos = $('.qrbtn').offset(),
							qrbtnwidth = $('.qrbtn').outerWidth(),
							qrbtnheight = $('.qrbtn').outerHeight()

            if (qr) {
								$('.qr').removeClass('active');
                $('.qr').animate({
                    'width': qrbtnwidth,
                    'height': qrbtnheight,
                    'left': qrpos.left,
										'top': qrpos.top,
										'margin-left': 0,
                }, 250, function(){
									$('.qr').hide();
								});
                qr = false;
            } else {
                qr = true;
								$('.qr').css({
									'width': qrbtnwidth,
									'height': qrbtnheight,
									'left': qrpos.left,
									'top': qrpos.top
								});
								$('.qr').show();
                $('.qr').animate({
                    'width': 500,
                    'height': 600,
                    'left': '50%',
                    'margin-left': -265,
                    'top': 50
                }, 250, function(){
									$('.qr').addClass('active');
								});
            }
        }
    });

    // Show QR Code
    $('.qrbtn').click(function (e) {
        e.preventDefault();
    });

    $('.printbtn').click(function (e) {
        e.preventDefault();
    });

    $('.homebtn').click(function (e) {
        e.preventDefault();
    });

    // Countdown Function
    public.countdown = function (calls, element) {
        count = 0;
        current = calls;
        var timerFunction = function () {
            element.text(current);
            current--;
            TweenLite.to(element, 0.0, {
                scale: 8,
                opacity: 0.2
            });
            TweenLite.to(element, 0.75, {
                scale: 1,
                opacity: 1
            });

            if (count < calls) {
                window.setTimeout(timerFunction, 1000);
            } else {
                public.cheese();
            }
            count++;
        };
        timerFunction();
    }

    //////////////////////////////////////////////////////////////////////////////////////////
    ////// PHOTOSWIPE FUNCTIONS /////////////////////////////////////////////////////////////

    public.initPhotoSwipeFromDOM = function (gallerySelector) {

        // select all gallery elements
        var galleryElements = document.querySelectorAll(gallerySelector);
        for (var i = 0, l = galleryElements.length; i < l; i++) {
            galleryElements[i].setAttribute('data-pswp-uid', i + 1);
            galleryElements[i].onclick = onThumbnailsClick;
        }

        // Parse URL and open gallery if it contains #&pid=3&gid=1
        var hashData = public.photoswipeParseHash();
        if (hashData.pid > 0 && hashData.gid > 0) {
            public.openPhotoSwipe(hashData.pid - 1, galleryElements[hashData.gid - 1], true);
        }
    }

    var onThumbnailsClick = function (e) {
        e = e || window.event;
        e.preventDefault ? e.preventDefault() : e.returnValue = false;

        var eTarget = e.target || e.srcElement;

        var clickedListItem = closest(eTarget, function (el) {
            return el.tagName === 'A';
        });

        if (!clickedListItem) {
            return;
        }

        var clickedGallery = clickedListItem.parentNode;

        var childNodes = clickedListItem.parentNode.childNodes,
            numChildNodes = childNodes.length,
            nodeIndex = 0,
            index;

        for (var i = 0; i < numChildNodes; i++) {
            if (childNodes[i].nodeType !== 1) {
                continue;
            }

            if (childNodes[i] === clickedListItem) {
                index = nodeIndex;
                break;
            }
            nodeIndex++;
        }

        if (index >= 0) {
            public.openPhotoSwipe(index, clickedGallery);
        }
        return false;
    };

    public.photoswipeParseHash = function () {
        var hash = window.location.hash.substring(1),
            params = {};

        if (hash.length < 5) { // pid=1
            return params;
        }

        var vars = hash.split('&');
        for (var i = 0; i < vars.length; i++) {
            if (!vars[i]) {
                continue;
            }
            var pair = vars[i].split('=');
            if (pair.length < 2) {
                continue;
            }
            params[pair[0]] = pair[1];
        }

        if (params.gid) {
            params.gid = parseInt(params.gid, 10);
        }

        if (!params.hasOwnProperty('pid')) {
            return params;
        }
        params.pid = parseInt(params.pid, 10);
        return params;
    };

    // Get Items for Photoswipe Gallery
    public.parseThumbnailElements = function (el) {
        var thumbElements = el.childNodes,
            numNodes = thumbElements.length,
            items = [],
            el,
            childElements,
            thumbnailEl,
            size,
            item;

        for (var i = 0; i < numNodes; i++) {
            el = thumbElements[i];

            // include only element nodes
            if (el.nodeType !== 1) {
                continue;
            }

            childElements = el.children;
            size = $(el).data('size').split('x');

            // create slide object
            item = {
                src: el.getAttribute('href'),
                w: parseInt(size[0], 10),
                h: parseInt(size[1], 10),
                author: el.getAttribute('data-author')
            };

            item.el = el; // save link to element for getThumbBoundsFn

            if (childElements.length > 0) {
                item.msrc = childElements[0].getAttribute('src'); // thumbnail url
                if (childElements.length > 1) {
                    item.title = childElements[1].innerHTML; // caption (contents of figure)
                }
            }


            var mediumSrc = el.getAttribute('data-med');
            if (mediumSrc) {
                size = el.getAttribute('data-med-size').split('x');
                // "medium-sized" image
                item.m = {
                    src: mediumSrc,
                    w: parseInt(size[0], 10),
                    h: parseInt(size[1], 10)
                };
            }
            // original image
            item.o = {
                src: item.src,
                w: item.w,
                h: item.h
            };

            items.push(item);
        }

        return items;
    };

    public.openPhotoSwipe = function (index, galleryElement, disableAnimation) {
        var pswpElement = document.querySelectorAll('.pswp')[0],
            gallery,
            options,
            items;

        items = public.parseThumbnailElements(galleryElement);

        // define options (if needed)
        options = {
            index: index,

            galleryUID: galleryElement.getAttribute('data-pswp-uid'),

            getThumbBoundsFn: function (index) {
                // See Options->getThumbBoundsFn section of docs for more info
                var thumbnail = items[index].el.children[0],
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
            addCaptionHTMLFn: function (item, captionEl, isFake) {
                if (!item.title) {
                    captionEl.children[0].innerText = '';
                    return false;
                }
                captionEl.children[0].innerHTML = item.title + '<br/><small>Photo: ' + item.author + '</small>';
                return true;
            }

        };

        var radios = document.getElementsByName('gallery-style');
        for (var i = 0, length = radios.length; i < length; i++) {
            if (radios[i].checked) {
                if (radios[i].id == 'radio-all-controls') {

                } else if (radios[i].id == 'radio-minimal-black') {
                    options.mainClass = 'pswp--minimal--dark';
                    options.barsSize = {
                        top: 0,
                        bottom: 0
                    };
                    options.captionEl = false;
                    options.fullscreenEl = false;
                    options.shareEl = false;
                    options.bgOpacity = 0.85;
                    options.tapToClose = true;
                    options.tapToToggleControls = false;
                }
                break;
            }
        }

        if (disableAnimation) {
            options.showAnimationDuration = 0;
        }

        // Pass data to PhotoSwipe and initialize it
        pswp = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

        // see: http://photoswipe.com/documentation/responsive-images.html
        var realViewportWidth,
            useLargeImages = false,
            firstResize = true,
            imageSrcWillChange;

        pswp.listen('beforeResize', function () {

            var dpiRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
            dpiRatio = Math.min(dpiRatio, 2.5);
            realViewportWidth = pswp.viewportSize.x * dpiRatio;


            if (realViewportWidth >= 1200 || (!pswp.likelyTouchDevice && realViewportWidth > 800) || screen.width > 1200) {
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
                pswp.invalidateCurrItems();
            }

            if (firstResize) {
                firstResize = false;
            }

            imageSrcWillChange = false;

        });

        pswp.listen('gettingData', function (index, item) {
            if (useLargeImages) {
                item.src = item.o.src;
                item.w = item.o.w;
                item.h = item.o.h;
            } else {
                item.src = item.m.src;
                item.w = item.m.w;
                item.h = item.m.h;
            }
        });

        pswp.listen('beforeChange', function () {
            $('.pswp__qr').removeClass('qr-active').fadeOut('fast');
        });

        pswp.listen('close', function () {
            $('.pswp__qr').removeClass('qr-active').fadeOut('fast');
        });

        pswp.init();


    };

    // find nearest parent element
    var closest = function closest(el, fn) {
        return el && (fn(el) ? el : closest(el.parentNode, fn));
    };
    //////////////////////////////////////////////////////////////////////////////////////////


    // clear Timeout to not reset the gallery, if you clicked anywhere
    $(document).click(function (event) {
        if (startPage.is(':visible')) {

        } else {
            clearTimeout(timeOut);
            public.resetTimeOut();
        }
    });
    // Disable Right-Click
    if (!isdev) {
        $(this).bind("contextmenu", function (e) {
            e.preventDefault();
        });
    }

    return public;
})();

// Init on domready
$(function () {
    photoBooth.init();
});
