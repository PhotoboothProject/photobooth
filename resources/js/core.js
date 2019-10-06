var L10N = {};
var photoBooth = (function () {
    config = {};
    // vars
    var public = {},
        loader = $('#loader'),
        startPage = $('#start'),
        countDown = cntdwn_time,       // Countdown from config
        cheeseTime = cheese_time,
        timeToLive = 90000,
        qr = false,
        timeOut,
        saving = false,
        gallery = $('#gallery'),
        showScrollbarsInGallery = gallery_scrollbar,
        processing = false,
        pswp = {},
        resultPage = $('#result'),
        imgFilter = 'imgPlain',
        stream,
        webcamConstraints = {
            audio: false,
            video: {
                width: 720,
                height: 480,
                facingMode: "user",
            }
        };

    var modal = {
        open: function(selector) {
            $(selector).addClass('modal--show');
        },
        close: function(selector) {
            if ($(selector).hasClass('modal--show')) {
                $(selector).removeClass('modal--show');

                return true;
            }

            return false;
        },
        toggle: function(selector) {
            $(selector).toggleClass('modal--show');
        },
        empty: function(selector) {
            modal.close(selector);

            $(selector).find('.modal__body').empty();
        }
    };

    public.reloadPage = function () {
        window.location.reload();
    }

    // timeOut function
    public.resetTimeOut = function () {
        timeOut = setTimeout(function () {
            public.reloadPage();
        }, timeToLive);
    }

    // reset whole thing
    public.reset = function () {
        loader.removeClass('open');
        qr = false;
        modal.empty('#qrCode');
        $('.qrbtn').removeClass('active').attr('style', '');
        $('.loading').text('');
        gallery.removeClass('gallery--open');
        gallery.find('.gallery__inner').hide();
        $('.spinner').hide();
        $('.send-mail').hide();
        public.resetMailForm();
    }

    // init
    public.init = function (options) {
        public.l10n();
        public.reset();

        public.initPhotoSwipeFromDOM('#galimages');

        startPage.addClass('open');
    }

    public.l10n = function (elem) {
        elem = $(elem || 'body');
        elem.find('[data-l10n]').each(function (i, item) {
            item = $(item);
            item.html(L10N[item.data('l10n')]);
        });
    }

    public.openNav = function () {
        $('#mySidenav').addClass('sidenav--open');
    }

    public.closeNav = function () {
        $('#mySidenav').removeClass('sidenav--open');
    }

    public.toggleNav = function () {
        $('#mySidenav').toggleClass('sidenav--open');
    }

    public.startVideo = function () {
        if(!navigator.mediaDevices) {
            return;
        }

        var getMedia = (navigator.mediaDevices.getUserMedia || navigator.mediaDevices.webkitGetUserMedia || navigator.mediaDevices.mozGetUserMedia || false);

        if(!getMedia) {
            return;
        }

        getMedia.call(navigator.mediaDevices, webcamConstraints)
            .then(function(stream) {
                $('#video').show();
                var video = $('#video').get(0);
                video.srcObject = stream;
                public.stream = stream;
            })
            .catch(function (error) {
                console.log('Could not get user media: ', error)
            });
    }

    public.stopVideo = function () {
        if(public.stream) {
            var track = public.stream.getTracks()[0];
            track.stop();
            $('#video').hide();
        }
    }

    // Cheese
    public.cheese = function (photoStyle) {
        if (isdev) {
            console.log(photoStyle);
        }
        if ((photoStyle=='photo')){
            $('#counter').text('');
            $('.loading').text(L10N.cheese);
        } else {
            $('#counter').text('');
            $('.loading').text(L10N.cheeseCollage);
        }
        public.takePic(photoStyle);
    }

    // take Picture
    public.takePic = function (photoStyle) {
        processing = true;
        if (isdev) {
            console.log('Take Picture:' + photoStyle);
        }
        if (useVideo) {
            public.stopVideo();
        }
        setTimeout(function () {
	    if ((photoStyle=='photo')){
                $('#counter').text('');
                $('.spinner').show();
                $('.loading').text(L10N.busy);
            } else {
                $('#counter').text('');
                if (!isdev) {
                    setTimeout(function () {
                        $('.spinner').show();
                        $('.loading').text(L10N.busyCollage);
                }, 7500);
                } else {
                    $('.spinner').show();
                    $('.loading').text(L10N.busyCollage);
                }
	    }
            $('#counter').text('');
        }, cheeseTime);
        jQuery.post("takePic.php",{filter: imgFilter,style: photoStyle}).done(function( result ){
            result = JSON.parse(result);
            if (result.error) {
                public.errorPic(result);
            } else {
                public.renderPic(result);
            }

        }).fail(function(xhr, status, result){
            public.errorPic(result);
        });
    }

    // Show error Msg and reset
    public.errorPic = function (result) {
        setTimeout(function () {
            $('.spinner').hide();
            $('.loading').html(L10N.error + '<a class="btn" href="./">' + L10N.reload + '</a>');
        }, 1100);
    }

    // Render Picture after taking
    public.renderPic = function (result) {
        // Add QR Code Image
        var qrCodeModal = $('#qrCode');
        modal.empty(qrCodeModal);
        $('<img src="qrcode.php?filename=' + result.img + '"/>').on('load', function () {
            var body = qrCodeModal.find('.modal__body');

            $(this).appendTo(body);
            $('<p>').html(L10N.qrHelp).appendTo(body);
        });

        // Add Print Link
        $(document).off('click touchstart', '.printbtn');
        $(document).on('click', '.printbtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#print_mesg').addClass('modal--show');
            setTimeout(function () {
                $.ajax({
                    url: 'print.php?filename=' + encodeURI(result.img),
                }).done(function (data) {
                    if (isdev) {
                        console.log(data)
                    }
                    setTimeout(function () {
                        $('#print_mesg').removeClass('modal--show');
                        public.reloadPage();
                    },5000);
                })
            },1000);
        });

        // Add Image to gallery and slider
        public.addImage(result.img);

        // Add Image
        $('<img src="'+imgFolder+'/' + result.img + '" class="original">').on('load', function () {
            $('#result').css({
                'background-image': 'url('+imgFolder+'/' + result.img + ')'
            });
            startPage.fadeOut(400, function () {
                resultPage.fadeIn(400, function () {
                    setTimeout(function () {
                        processing = false;
                        loader.removeClass('open');
                    }, 400);
                    setTimeout(function () {
                        $('.resultInner').addClass('show');
                    }, 400);
                    clearTimeout(timeOut);
                    public.resetTimeOut();
                });
            });
        });
    }

    // add image to Gallery
    public.addImage = function (imageName) {
        var thumbImg = new Image();
        var bigImg = new Image();
        var thumbSize = '';
        var bigSize = '';

        var imgtoLoad = 2;

        thumbImg.onload = function() {
            thumbSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) {allLoaded();}
        }

        bigImg.onload = function() {
            bigSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) {allLoaded();}
        }

        bigImg.src = imgFolder+'/' + imageName;
        thumbImg.src = thumbFolder+'/' + imageName;

        function allLoaded() {
            var $node = $('<a>').html(thumbImg).data('size', bigSize).attr('href', imgFolder+'/' + imageName).attr('data-med', thumbFolder+'/' + imageName).attr('data-med-size', thumbSize);
            if (gallery_newest_first) {
                $node.prependTo($('#galimages'));
            } else {
                $node.appendTo($('#galimages'));
            }

            $('#galimages').children().not('a').remove();
        }
    }

    // Open Gallery Overview
    public.openGallery = function (elem) {
        if(showScrollbarsInGallery) {
            gallery.addClass('scrollbar');
        }

        gallery.addClass('gallery--open');

        setTimeout(() => gallery.find('.gallery__inner').show(), 300);
    }

    //Filter
    $('.imageFilter').on('click', function (e) {
        public.toggleNav();
    });

    $('.sidenav > div').on('click', function (e) {
        $('.sidenav > div').removeAttr("class");
        $(this).addClass("activeSidenavBtn");
        imgFilter = $(this).attr("id");
        if (isdev) {
            console.log(imgFilter);
        }
    });

    function takePictureHandler(photoStyle) {
        if (!processing) {
            public.closeNav();
            public.reset();
            if (useVideo) {
                public.startVideo();
            }
            loader.addClass('open');
            public.countdown(countDown, $('#counter'),photoStyle);
        }
    }

    // Take Picture Button
    $('.takePic, .newpic').on('click', function (e) {
        e.preventDefault();

        takePictureHandler('photo');
    });

    // Take Collage Button
    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();

        takePictureHandler('collage');
    });

    $('#mySidenav .closebtn').on('click', function (e) {
        e.preventDefault();

        public.closeNav();
    });

    // Open Gallery Button
    $('.gallery-button').on('click', function (e) {
        e.preventDefault();

        public.closeNav();
        public.openGallery($(this));
    });

    // Close Gallery Overview
    $('.gallery__close').on('click', function (e) {
        e.preventDefault();
        gallery.find('.gallery__inner').hide();
        gallery.removeClass('gallery--open');
    });

    // QR in gallery
    $('.gal-qr-code').on('click', function (e) {
        e.preventDefault();

        var pswpQR = $('.pswp__qr');
        if (pswpQR.hasClass('qr-active')) {
            pswpQR.removeClass('qr-active').fadeOut('fast');
        } else {
            pswpQR.empty();
            var img = pswp.currItem.src;
            img = img.split('/').pop();

            $('<img>').attr('src', 'qrcode.php?filename=' + img).appendTo(pswpQR);

            pswpQR.addClass('qr-active').fadeIn('fast');
        }
    });

    // print in gallery
    $('.gal-print').on('click', function (e) {
        e.preventDefault();
        var img = pswp.currItem.src;
        img = img.split('/').pop();
        modal.open('#print_mesg');
        setTimeout(function () {
            $.ajax({
                url: 'print.php?filename=' + encodeURI(img),
            }).done(function (data) {
                if (isdev) {
                    console.log(data)
                }
                setTimeout(function () {
                    modal.close('#print_mesg');
                    pswp.close();
                },5000);
            });
        },1000);
    });

    // chroma keying print
    $(document).on('click touchstart', '.gal-print-chroma_keying', function (e) {
        e.preventDefault();
        var img = pswp.currItem.src;
        img = img.split('/').pop();
        $.post( "chromakeying_info.php", function( info ) {
            if (info.chroma_keying == true) {
                var currentHref = $(location).attr('href').split('#')[0];;
                var encodedString = btoa(currentHref);
                //var decodedString = atob(encodedString);
                $(location).attr('href','chromakeying.php?filename=' + encodeURI(img) + '&location=' + encodeURI(encodedString));
            }
        }, "json");
    });

    $('.gal-mail, .mailbtn').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var mail = $('.send-mail');
        if (mail.hasClass('mail-active')) {
            public.resetMailForm();
            mail.removeClass('mail-active').fadeOut('fast');
        } else {
            mail.addClass('mail-active').fadeIn('fast');
        }
    });

    $('#send-mail-form').on('submit', function (e) {
        e.preventDefault();
        var img = '';
        if($('.pswp.pswp--open.pswp--visible').length) {
            img = pswp.currItem.src;
        } else {
            img = resultPage.css("background-image").replace('url(','').replace(')','').replace(/\"/gi, "").split('/'+imgFolder+'/')[1];
        }

        img = img.split('/').pop();

        $('#mail-form-image').val(img);
        var message = $('#mail-form-message');
        message.empty();

        var form = $(this);
        var oldValue = form.find('.btn').html();
        form.find('.btn').html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: 'sendPic.php',
            type: 'POST',
            data: form.serialize(),
            dataType: "json",
            cache: false,
            success: function (result) {
                if (result.success === true) {
                    message.fadeIn().html('<span style="color:green">' + L10N.mailSent + '</span>');
                } else {
                    message.fadeIn().html('<span style="color:red">' + result.error + '</span>');
                }
            },
            error: function (xhr, status, error) {
                message.fadeIn('fast').html('<span style="color: red;">' + L10N.mailError + '</span>');
            },
            complete: function () {
                form.find('.btn').html(oldValue);
            }
        });
    });

    $('#send-mail-close').on('click', function (e) {
        public.resetMailForm();
        $('.send-mail').removeClass('mail-active').fadeOut('fast');
    });

    public.resetMailForm = function() {
        $('#send-mail-form').trigger('reset');
        $('#mail-form-message').html('');
    };

    $('#result').on('click', function (e) {
        if (!modal.close('#qrCode')) {
            $('.resultInner').toggleClass('show');
        }
    });

    // Show QR Code
    $('.qrbtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        modal.toggle('#qrCode');
    });

    $('.homebtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        public.reloadPage();
    });

    // Countdown Function
    public.countdown = function (calls, element, photoStyle) {
        count = 0;
        current = calls;
        if (isdev) {
            console.log(photoStyle);
        }
        var timerFunction = function () {
            element.text(current);
            current--;

            element.removeClass('tick');

            if (count < calls) {
                window.setTimeout(() => element.addClass('tick'), 50);
                window.setTimeout(timerFunction, 1000);
            } else {
                if (isdev) {
                    console.log(photoStyle);
                }
                public.cheese(photoStyle);
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
            public.resetMailForm();
            $('.send-mail').removeClass('mail-active').fadeOut('fast');
        });

        pswp.listen('close', function () {
            $('.pswp__qr').removeClass('qr-active').fadeOut('fast');
            public.resetMailForm();
            $('.send-mail').removeClass('mail-active').fadeOut('fast');
        });

        pswp.init();


    };

    // find nearest parent element
    var closest = function closest(el, fn) {
        return el && (fn(el) ? el : closest(el.parentNode, fn));
    };
    //////////////////////////////////////////////////////////////////////////////////////////


    // clear Timeout to not reset the gallery, if you clicked anywhere
    $(document).on('click', function (event) {
        if (startPage.is(':visible')) {

        } else {
            clearTimeout(timeOut);
            public.resetTimeOut();
        }
    });
    // Disable Right-Click
    if (!isdev) {
        $(this).on("contextmenu", function (e) {
            e.preventDefault();
        });
    }

    return public;
})();

// Init on domready
$(function () {
    photoBooth.init();
});
