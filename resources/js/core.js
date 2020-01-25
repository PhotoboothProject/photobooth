/* globals initPhotoSwipeFromDOM L10N */

const photoBooth = (function () {
    // vars
    const public = {},
        loader = $('#loader'),
        startPage = $('#start'),
        timeToLive = config.time_to_live,
        gallery = $('#gallery'),
        resultPage = $('#result'),
        webcamConstraints = {
            audio: false,
            video: {
                width: config.videoWidth,
                height: config.videoHeight,
                facingMode: config.camera_mode,
            }
        },
        videoView = $('#video--view').get(0),
        videoSensor = document.querySelector('#video--sensor');

    let timeOut,
        nextCollageNumber = 0,
        currentCollageFile = '',
        imgFilter = config.default_imagefilter;

    const modal = {
        open: function (selector) {
            $(selector).addClass('modal--show');
        },
        close: function (selector) {
            if ($(selector).hasClass('modal--show')) {
                $(selector).removeClass('modal--show');

                return true;
            }

            return false;
        },
        toggle: function (selector) {
            $(selector).toggleClass('modal--show');
        },
        empty: function (selector) {
            modal.close(selector);

            $(selector).find('.modal__body').empty();
        }
    };

    public.reloadPage = function () {
        window.location.reload();
    }

    // timeOut function
    public.resetTimeOut = function () {
        clearTimeout(timeOut);

        timeOut = setTimeout(function () {
            public.reloadPage();
        }, timeToLive);
    }

    // reset whole thing
    public.reset = function () {
        loader.removeClass('open');
        loader.removeClass('error');
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
    public.init = function () {
        public.reset();

        initPhotoSwipeFromDOM('#galimages');

        resultPage.hide();
        startPage.addClass('open');
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
        if (!navigator.mediaDevices) {
            return;
        }

        const getMedia = (navigator.mediaDevices.getUserMedia || navigator.mediaDevices.webkitGetUserMedia || navigator.mediaDevices.mozGetUserMedia || false);

        if (!getMedia) {
            return;
        }

        if (config.previewCamFlipHorizontal) {
            $('#video--view').addClass('flip-horizontal');
        }

        getMedia.call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                $('#video--view').show();
                videoView.srcObject = stream;
                public.stream = stream;
            })
            .catch(function (error) {
                console.log('Could not get user media: ', error)
            });
    }

    public.stopVideo = function () {
        if (public.stream) {
            const track = public.stream.getTracks()[0];
            track.stop();
            $('#video--view').hide();
        }
    }

    public.thrill = function (photoStyle) {
        public.closeNav();
        public.reset();

        if (currentCollageFile && nextCollageNumber) {
            photoStyle = 'collage';
        }

        if (config.previewFromCam) {
            public.startVideo();
        }

        loader.addClass('open');
        public.startCountdown(nextCollageNumber ? config.collage_cntdwn_time : config.cntdwn_time, $('#counter'), () => {
            public.cheese(photoStyle);
        });
    }

    // Cheese
    public.cheese = function (photoStyle) {
        if (config.dev) {
            console.log(photoStyle);
        }

        $('#counter').empty();
        $('.cheese').empty();

        if (photoStyle === 'photo') {
            $('.cheese').text(L10N.cheese);
        } else {
            $('.cheese').text(L10N.cheeseCollage);
            $('<p>').text(`${nextCollageNumber + 1} / ${config.collage_limit}`).appendTo('.cheese');
        }

        setTimeout(() => {
            public.takePic(photoStyle);
        }, config.cheese_time);
    }

    // take Picture
    public.takePic = function (photoStyle) {
        if (config.dev) {
            console.log('Take Picture:' + photoStyle);
        }

        if (config.previewFromCam) {
            if (config.previewCamTakesPic && !config.dev) {
                videoSensor.width = videoView.videoWidth;
                videoSensor.height = videoView.videoHeight;
                videoSensor.getContext('2d').drawImage(videoView, 0, 0);
            }
            public.stopVideo();
        }

        const data = {
            filter: imgFilter,
            style: photoStyle,
            canvasimg: videoSensor.toDataURL('image/jpeg'),
        };

        if (photoStyle === 'collage') {
            data.file = currentCollageFile;
            data.collageNumber = nextCollageNumber;
        }

        jQuery.post('api/takePic.php', data).done(function (result) {
            console.log('took picture', result);
            $('.cheese').empty();
            if (config.previewCamFlipHorizontal) {
                $('#video--view').removeClass('flip-horizontal');
            }

            // reset filter (selection) after picture was taken
            imgFilter = config.default_imagefilter;
            $('#mySidenav .activeSidenavBtn').removeClass('activeSidenavBtn');
            $('#' + imgFilter).addClass('activeSidenavBtn');

            if (result.error) {
                public.errorPic(result);
            } else if (result.success === 'collage' && (result.current + 1) < result.limit) {
                currentCollageFile = result.file;
                nextCollageNumber = result.current + 1;

                $('.spinner').hide();
                $('.loading').empty();
                $('#video--sensor').hide();

                if (config.continuous_collage) {
                    setTimeout(() => {
                        public.thrill('collage');
                    }, 1000);
                } else {
                    $('<a class="btn" href="#">' + L10N.nextPhoto + '</a>').appendTo('.loading').click((ev) => {
                        ev.preventDefault();

                        public.thrill('collage');
                    });
                    $('.loading').append($('<a class="btn" style="margin-left:2px" href="./">').text(L10N.abort));
                }
            } else {
                currentCollageFile = '';
                nextCollageNumber = 0;

                public.processPic(photoStyle, result);
            }

        }).fail(function (xhr, status, result) {
            public.errorPic(result);
        });
    }

    // Show error Msg and reset
    public.errorPic = function (data) {
        setTimeout(function () {
            $('.spinner').hide();
            $('.loading').empty();
            $('.cheese').empty();
            $('#video--sensor').hide();
            loader.addClass('error');
            $('.loading').append($('<p>').text(L10N.error));
            if (config.show_error_messages || config.dev) {
                $('.loading').append($('<p class="text-muted">').text(data.error));
            }
            $('.loading').append($('<a class="btn" href="./">').text(L10N.reload));
        }, 500);
    }

    public.processPic = function (photoStyle, result) {
        const tempImageUrl = config.folders.tmp + '/' + result.file;

        $('.spinner').show();
        $('.loading').text(photoStyle === 'photo' ? L10N.busy : L10N.busyCollage);

        if (photoStyle === 'photo') {
            const preloadImage = new Image();
            preloadImage.onload = () => {
                $('#loader').css('background-image', `url(${tempImageUrl})`);
                $('#loader').addClass('showBackgroundImage');
            }
            preloadImage.src = tempImageUrl;
        }

        $.ajax({
            method: 'POST',
            url: 'api/applyEffects.php',
            data: {
                file: result.file,
                filter: imgFilter,
                isCollage: photoStyle === 'collage',
            },
            success: (data) => {
                console.log('picture processed', data);

                if (data.error) {
                    public.errorPic(data);
                } else {
                    public.renderPic(data.file);
                }
            },
            error: (jqXHR, textStatus) => {
                console.log('An error occurred', textStatus);

                public.errorPic({
                    error: 'Request failed: ' + textStatus,
                });
            },
        });
    }

    // Render Picture after taking
    public.renderPic = function (filename) {
        // Add QR Code Image
        const qrCodeModal = $('#qrCode');
        modal.empty(qrCodeModal);
        $('<img src="api/qrcode.php?filename=' + filename + '"/>').on('load', function () {
            const body = qrCodeModal.find('.modal__body');

            $(this).appendTo(body);
            $('<p>').css('max-width', this.width + 'px').html(L10N.qrHelp).appendTo(body);
        });

        // Add Print Link
        $(document).off('click touchstart', '.printbtn');
        $(document).on('click', '.printbtn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            public.printImage(filename, () => {
                public.reloadPage();
            });
        });

        resultPage.find('.deletebtn').off('click').on('click', (ev) => {
            ev.preventDefault();

            public.deleteImage(filename, (data) => {
                if (data.success) {
                    public.reloadPage();
                } else {
                    console.log('Error while deleting image');
                }
            })
        });

        // Add Image to gallery and slider
        public.addImage(filename);

        const imageUrl = config.folders.images + '/' + filename;

        const preloadImage = new Image();
        preloadImage.onload = () => {
            resultPage.css({
                'background-image': `url(${imageUrl}?filter=${imgFilter})`,
            });
            resultPage.attr('data-img', filename);

            startPage.hide();
            resultPage.show();

            $('.resultInner').addClass('show');
            loader.removeClass('open');

            $('#loader').css('background-image', 'url()');
            $('#loader').removeClass('showBackgroundImage');

            public.resetTimeOut();
        };

        preloadImage.src = imageUrl;
    }

    // add image to Gallery
    public.addImage = function (imageName) {
        const thumbImg = new Image();
        const bigImg = new Image();
        let thumbSize = '';
        let bigSize = '';

        let imgtoLoad = 2;

        thumbImg.onload = function () {
            thumbSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) { allLoaded(); }
        }

        bigImg.onload = function () {
            bigSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) { allLoaded(); }
        }

        bigImg.src = config.folders.images + '/' + imageName;
        thumbImg.src = config.folders.thumbs + '/' + imageName;

        function allLoaded() {
            const linkElement = $('<a>').html(thumbImg);

            linkElement.attr('data-size', bigSize);
            linkElement.attr('href', config.folders.images + '/' + imageName);
            linkElement.attr('data-med', config.folders.thumbs + '/' + imageName);
            linkElement.attr('data-med-size', thumbSize);

            if (config.newest_first) {
                linkElement.prependTo($('#galimages'));
            } else {
                linkElement.appendTo($('#galimages'));
            }

            $('#galimages').children().not('a').remove();
        }
    }

    // Open Gallery Overview
    public.openGallery = function () {
        if (config.scrollbar) {
            gallery.addClass('scrollbar');
        }

        gallery.addClass('gallery--open');

        setTimeout(() => gallery.find('.gallery__inner').show(), 300);
    }

    public.resetMailForm = function () {
        $('#send-mail-form').trigger('reset');
        $('#mail-form-message').html('');
    };

    // Countdown Function
    public.startCountdown = function (start, element, cb) {
        let count = 0;
        let current = start;

        function timerFunction() {
            element.text(current);
            current--;

            element.removeClass('tick');

            if (count < start) {
                window.setTimeout(() => element.addClass('tick'), 50);
                window.setTimeout(timerFunction, 1000);
            } else {
                cb();
            }
            count++;
        }
        timerFunction();
    }

    public.printImage = function (imageSrc, cb) {
        modal.open('#print_mesg');

        setTimeout(function () {
            $.ajax({
                url: 'api/print.php?filename=' + encodeURI(imageSrc),
            }).done(function (data) {
                if (config.dev) {
                    console.log(data)
                }

                setTimeout(function () {
                    modal.close('#print_mesg');
                    cb();
                }, 5000);
            });
        }, 1000);
    }

    public.deleteImage = function (imageName, cb) {
        $.ajax({
            url: 'api/deletePhoto.php',
            method: 'POST',
            data: {
                file: imageName,
            },
            success: (data) => {
                cb(data);
            }
        });
    }

    public.toggleMailDialog = function (img) {
        const mail = $('.send-mail');

        if (mail.hasClass('mail-active')) {
            public.resetMailForm();
            mail.removeClass('mail-active').fadeOut('fast');
        } else {
            $('#mail-form-image').val(img);

            mail.addClass('mail-active').fadeIn('fast');
        }
    }

    //Filter
    $('.imageFilter').on('click', function () {
        public.toggleNav();
    });

    $('.sidenav > div').on('click', function () {
        $('.sidenav > div').removeAttr('class');
        $(this).addClass('activeSidenavBtn');

        imgFilter = $(this).attr('id');
        const result = {file: $('#result').attr('data-img')};
        if (config.dev) {
            console.log('Applying filter', imgFilter, result);
        }
        public.processPic(imgFilter, result);
    });

    // Take Picture Button
    $('.takePic, .newpic').on('click', function (e) {
        e.preventDefault();

        public.thrill('photo');
    });

    // Take Collage Button
    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();

        public.thrill('collage');
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

    $('.mailbtn').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const img = resultPage.attr('data-img');

        public.toggleMailDialog(img);
    });

    $('#send-mail-form').on('submit', function (e) {
        e.preventDefault();

        const message = $('#mail-form-message');
        message.empty();

        const form = $(this);
        const oldValue = form.find('.btn').html();

        form.find('.btn').html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: 'api/sendPic.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            cache: false,
            success: function (result) {
                if (result.success) {
                    if (result.saved) {
                        message.fadeIn().html('<span style="color:green">' + L10N.mailSaved + '</span>');
                    } else {
                        message.fadeIn().html('<span style="color:green">' + L10N.mailSent + '</span>');
                    }
                } else {
                    message.fadeIn().html('<span style="color:red">' + result.error + '</span>');
                }
            },
            error: function () {
                message.fadeIn('fast').html('<span style="color: red;">' + L10N.mailError + '</span>');
            },
            complete: function () {
                form.find('.btn').html(oldValue);
            }
        });
    });

    $('#send-mail-close').on('click', function () {
        public.resetMailForm();
        $('.send-mail').removeClass('mail-active').fadeOut('fast');
    });

    $('#result').on('click', function () {
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

    $('#cups-button').on('click', function (ev) {
        ev.preventDefault();

        const url = `http://${location.hostname}:631/jobs/`;
        const features = 'width=1024,height=600,left=0,top=0,screenX=0,screenY=0,resizable=NO,scrollbars=NO';

        window.open(url, 'newwin', features);
    });

    $(document).on('keyup', function (ev) {
        if (config.photo_key && parseInt(config.photo_key, 10) === ev.keyCode) {
            public.thrill('photo');
        }

        if (config.collage_key && parseInt(config.collage_key, 10) === ev.keyCode) {
            if (config.use_collage) {
                public.thrill('collage');
            } else {
                if (config.dev) {
                    console.log('Collage key pressed. Please enable collage in your config. Triggering photo now.');
                }
                public.thrill('photo');
            }
        }
    });

    // clear Timeout to not reset the gallery, if you clicked anywhere
    $(document).on('click', function () {
        if (!startPage.is(':visible')) {
            public.resetTimeOut();
        }
    });

    // Disable Right-Click
    if (!config.dev) {
        $(this).on('contextmenu', function (e) {
            e.preventDefault();
        });
    }

    return public;
})();

// Init on domready
$(function () {
    photoBooth.init();
});
