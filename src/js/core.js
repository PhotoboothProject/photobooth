/* globals initPhotoSwipeFromDOM i18n io */

const photoBooth = (function () {
    // vars
    const api = {},
        loader = $('#loader'),
        startPage = $('#start'),
        wrapper = $('#wrapper'),
        timeToLive = config.time_to_live,
        gallery = $('#gallery'),
        resultPage = $('#result'),
        webcamConstraints = {
            audio: false,
            video: {
                width: config.videoWidth,
                height: config.videoHeight,
                facingMode: config.camera_mode
            }
        },
        videoView = $('#video--view').get(0),
        videoPreview = $('#video--preview').get(0),
        videoSensor = document.querySelector('#video--sensor');

    let timeOut,
        isPrinting = false,
        takingPic = false,
        nextCollageNumber = 0,
        currentCollageFile = '',
        imgFilter = config.default_imagefilter;

    let ioClient;

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

    api.reloadPage = function () {
        window.location.reload();
    };

    // Returns true when timeOut is pending
    api.isTimeOutPending = function () {
        return typeof timeOut !== 'undefined';
    };

    // timeOut function
    api.resetTimeOut = function () {
        clearTimeout(timeOut);

        timeOut = setTimeout(function () {
            api.reloadPage();
        }, timeToLive);
    };

    // reset whole thing
    api.reset = function () {
        loader.removeClass('open');
        loader.removeClass('error');
        modal.empty('#qrCode');
        $('.qrbtn').removeClass('active').attr('style', '');
        $('.loading').text('');
        gallery.removeClass('gallery--open');
        gallery.find('.gallery__inner').hide();
        $('.spinner').hide();
        $('.send-mail').hide();
        $('#video--view').hide();
        $('#video--preview').hide();
        $('#video--sensor').hide();
        $('#ipcam--view').hide();
        api.resetMailForm();
        $('#loader').css('background', config.colors.background_countdown);
        $('#loader').css('background-color', config.colors.background_countdown);
    };

    // init
    api.init = function () {
        api.reset();

        initPhotoSwipeFromDOM('#galimages');

        resultPage.hide();
        startPage.addClass('open');
        if (config.previewCamBackground) {
            api.startVideo('preview');
        }

        if (config.remotebuzzer_enabled) {
            if (config.webserver_ip) {
                ioClient = io('http://' + config.webserver_ip + ':' + config.remotebuzzer_port);

                console.log(
                    ' Remote buzzer connecting to http://' + config.webserver_ip + ':' + config.remotebuzzer_port
                );

                ioClient.on('photobooth-socket', function (data) {
                    switch (data) {
                        case 'start-picture':
                            $('.resultInner').removeClass('show');
                            api.thrill('photo');
                            break;
                        case 'start-collage':
                            if (config.use_collage) {
                                $('.resultInner').removeClass('show');
                                api.thrill('collage');
                            }
                            break;
                        default:
                            break;
                    }
                });

                ioClient.on('connect_failed', function () {
                    console.log(' Remote buzzer unable to connect');
                });
            } else {
                console.log(' Remote buzzer unable to connect - webserver_ip not defined in config');
            }
        }
    };

    api.openNav = function () {
        $('#mySidenav').addClass('sidenav--open');
    };

    api.closeNav = function () {
        $('#mySidenav').removeClass('sidenav--open');
    };

    api.toggleNav = function () {
        $('#mySidenav').toggleClass('sidenav--open');
    };

    api.startVideo = function (mode) {
        if (config.previewCamBackground) {
            api.stopVideo('preview');
        }

        if (!navigator.mediaDevices) {
            return;
        }

        const getMedia =
            navigator.mediaDevices.getUserMedia ||
            navigator.mediaDevices.webkitGetUserMedia ||
            navigator.mediaDevices.mozGetUserMedia ||
            false;

        if (!getMedia) {
            return;
        }

        if (config.previewCamFlipHorizontal) {
            $('#video--view').addClass('flip-horizontal');
            $('#video--preview').addClass('flip-horizontal');
        }

        getMedia
            .call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                if (mode === 'preview') {
                    $('#video--preview').show();
                    videoPreview.srcObject = stream;
                    api.stream = stream;
                    wrapper.css('background-image', 'none');
                    wrapper.css('background-color', 'transparent');
                } else {
                    $('#video--view').show();
                    videoView.srcObject = stream;
                }
                api.stream = stream;
            })
            .catch(function (error) {
                console.log('Could not get user media: ', error);
            });
    };

    api.stopVideo = function (mode) {
        if (api.stream) {
            const track = api.stream.getTracks()[0];
            track.stop();
            if (mode === 'preview') {
                $('#video--preview').hide();
            } else {
                $('#video--view').hide();
            }
        }
    };

    api.thrill = function (photoStyle) {
        api.closeNav();
        api.reset();

        takingPic = true;
        if (config.dev) {
            console.log('Taking photo:', takingPic);
        }

        if (config.remotebuzzer_enabled) {
            ioClient.emit('photobooth-socket', 'in progress');
        }

        if (config.previewCamBackground) {
            wrapper.css('background-color', config.colors.panel);
        }

        if (currentCollageFile && nextCollageNumber) {
            photoStyle = 'collage';
        }

        if (config.previewFromCam) {
            api.startVideo('view');
        }

        if (config.previewFromIPCam) {
            $('#ipcam--view').show();
            $('#ipcam--view').addClass('streaming');
        }

        loader.addClass('open');
        api.startCountdown(nextCollageNumber ? config.collage_cntdwn_time : config.cntdwn_time, $('#counter'), () => {
            api.cheese(photoStyle);
        });
    };

    // Cheese
    api.cheese = function (photoStyle) {
        if (config.dev) {
            console.log(photoStyle);
        }

        $('#counter').empty();
        $('.cheese').empty();

        if (config.no_cheese) {
            console.log('Cheese is disabled.');
        } else if (photoStyle === 'photo') {
            const cheesemsg = i18n('cheese');
            $('.cheese').text(cheesemsg);
        } else {
            const cheesemsg = i18n('cheeseCollage');
            $('.cheese').text(cheesemsg);
            $('<p>')
                .text(`${nextCollageNumber + 1} / ${config.collage_limit}`)
                .appendTo('.cheese');
        }

        if (config.previewFromCam && config.previewCamTakesPic && !api.stream && !config.dev) {
            console.log('No preview by device cam available!');

            if (config.previewFromIPCam) {
                $('#ipcam--view').removeClass('streaming');
                $('#ipcam--view').hide();
            }

            api.errorPic({
                error: 'No preview by device cam available!'
            });
        } else if (config.no_cheese) {
            api.takePic(photoStyle);
        } else {
            setTimeout(() => {
                api.takePic(photoStyle);
            }, config.cheese_time);
        }
    };

    // take Picture
    api.takePic = function (photoStyle) {
        if (config.dev) {
            console.log('Take Picture:' + photoStyle);
        }

        if (config.remotebuzzer_enabled) {
            ioClient.emit('photobooth-socket', 'in progress');
        }

        if (config.previewFromCam) {
            if (config.previewCamTakesPic && !config.dev) {
                videoSensor.width = videoView.videoWidth;
                videoSensor.height = videoView.videoHeight;
                videoSensor.getContext('2d').drawImage(videoView, 0, 0);
            }
            api.stopVideo('view');
        }

        if (config.previewFromIPCam) {
            $('#ipcam--view').removeClass('streaming');
            $('#ipcam--view').hide();
        }

        const data = {
            filter: imgFilter,
            style: photoStyle,
            canvasimg: videoSensor.toDataURL('image/jpeg')
        };

        if (photoStyle === 'collage') {
            data.file = currentCollageFile;
            data.collageNumber = nextCollageNumber;
        }

        loader.css('background', config.colors.panel);
        loader.css('background-color', config.colors.panel);

        jQuery
            .post('api/takePic.php', data)
            .done(function (result) {
                console.log('took picture', result);
                $('.cheese').empty();
                if (config.previewCamFlipHorizontal) {
                    $('#video--view').removeClass('flip-horizontal');
                    $('#video--preview').removeClass('flip-horizontal');
                }

                // reset filter (selection) after picture was taken
                imgFilter = config.default_imagefilter;
                $('#mySidenav .activeSidenavBtn').removeClass('activeSidenavBtn');
                $('#' + imgFilter).addClass('activeSidenavBtn');

                if (result.error) {
                    api.errorPic(result);
                } else if (result.success === 'collage' && result.current + 1 < result.limit) {
                    currentCollageFile = result.file;
                    nextCollageNumber = result.current + 1;

                    $('.spinner').hide();
                    $('.loading').empty();
                    $('#video--sensor').hide();

                    if (config.continuous_collage) {
                        setTimeout(() => {
                            api.thrill('collage');
                        }, 1000);
                    } else {
                        if (config.remotebuzzer_enabled) {
                            ioClient.emit('photobooth-socket', 'collage-wait-for-next');
                        }

                        $('<a class="btn" href="#">' + i18n('nextPhoto') + '</a>')
                            .appendTo('.loading')
                            .click((ev) => {
                                ev.preventDefault();

                                api.thrill('collage');
                            });
                        const abortmsg = i18n('abort');
                        $('.loading').append($('<a class="btn" style="margin-left:2px" href="./">').text(abortmsg));
                    }
                } else {
                    currentCollageFile = '';
                    nextCollageNumber = 0;

                    api.processPic(photoStyle, result);
                }
            })
            .fail(function (xhr, status, result) {
                api.errorPic(result);
            });
    };

    // Show error Msg and reset
    api.errorPic = function (data) {
        setTimeout(function () {
            $('.spinner').hide();
            $('.loading').empty();
            $('.cheese').empty();
            $('#video--view').hide();
            $('#video--sensor').hide();
            loader.addClass('error');
            const errormsg = i18n('error');
            takingPic = false;
            if (config.dev) {
                console.log('Taking photo:', takingPic);
            }
            $('.loading').append($('<p>').text(errormsg));
            if (config.show_error_messages || config.dev) {
                $('.loading').append($('<p class="text-muted">').text(data.error));
            }
            if (config.auto_reload_on_error) {
                const reloadmsg = i18n('auto_reload');
                $('.loading').append($('<p>').text(reloadmsg));
                setTimeout(function () {
                    api.reloadPage();
                }, 5000);
            } else {
                const reloadmsg = i18n('reload');
                $('.loading').append($('<a class="btn" href="./">').text(reloadmsg));
            }
        }, 500);
    };

    api.processPic = function (photoStyle, result) {
        const tempImageUrl = config.folders.tmp + '/' + result.file;

        $('.spinner').show();
        $('.loading').text(photoStyle === 'photo' ? i18n('busy') : i18n('busyCollage'));

        takingPic = false;
        if (config.dev) {
            console.log('Taking photo:', takingPic);
        }

        if (photoStyle === 'photo' && config.image_preview_before_processing) {
            const preloadImage = new Image();
            preloadImage.onload = () => {
                $('#loader').css('background-image', `url(${tempImageUrl})`);
                $('#loader').addClass('showBackgroundImage');
            };
            preloadImage.src = tempImageUrl;
        }

        $.ajax({
            method: 'POST',
            url: 'api/applyEffects.php',
            data: {
                file: result.file,
                filter: imgFilter,
                isCollage: photoStyle === 'collage'
            },
            success: (data) => {
                console.log('picture processed', data);

                if (data.error) {
                    api.errorPic(data);
                    if (config.remotebuzzer_enabled) {
                        ioClient.emit('photobooth-socket', 'completed');
                    }
                } else {
                    api.renderPic(data.file);
                }
            },
            error: (jqXHR, textStatus) => {
                console.log('An error occurred', textStatus);

                api.errorPic({
                    error: 'Request failed: ' + textStatus
                });

                if (config.remotebuzzer_enabled) {
                    ioClient.emit('photobooth-socket', 'completed');
                }
            }
        });
    };

    // Render Picture after taking
    api.renderPic = function (filename) {
        // Add QR Code Image
        const qrCodeModal = $('#qrCode');
        modal.empty(qrCodeModal);
        $('<img src="api/qrcode.php?filename=' + filename + '"/>').on('load', function () {
            const body = qrCodeModal.find('.modal__body');

            $(this).appendTo(body);
            $('<p>')
                .css('max-width', this.width + 'px')
                .html(i18n('qrHelp') + '</br><b>' + config.wifi_ssid + '</b>')
                .appendTo(body);
        });

        // Add Print Link
        $(document).off('click touchstart', '.printbtn');
        $(document).on('click', '.printbtn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            api.printImage(filename, () => {
                $('.printbtn').blur();
            });
        });

        // If autoprint is activated the picture will immediately printed after taken.
        if (config.auto_print) {
            setTimeout(function () {
                api.printImage(filename);
            }, config.auto_print_delay);
        }

        resultPage
            .find('.deletebtn')
            .off('click')
            .on('click', (ev) => {
                ev.preventDefault();

                api.deleteImage(filename, (data) => {
                    if (data.success) {
                        api.reloadPage();
                    } else {
                        console.log('Error while deleting image');
                        setTimeout(function () {
                            api.reloadPage();
                        }, 5000);
                    }
                });
            });

        // Add Image to gallery and slider
        api.addImage(filename);

        const imageUrl = config.folders.images + '/' + filename;

        const preloadImage = new Image();
        preloadImage.onload = () => {
            resultPage.css({
                'background-image': `url(${imageUrl}?filter=${imgFilter})`
            });
            resultPage.attr('data-img', filename);

            startPage.hide();
            resultPage.show();

            $('.resultInner').addClass('show');
            loader.removeClass('open');

            $('#loader').css('background-image', 'url()');
            $('#loader').removeClass('showBackgroundImage');

            api.resetTimeOut();
        };

        preloadImage.src = imageUrl;

        if (config.remotebuzzer_enabled) {
            ioClient.emit('photobooth-socket', 'completed');
        }
    };

    // add image to Gallery
    api.addImage = function (imageName) {
        const thumbImg = new Image();
        const bigImg = new Image();
        let thumbSize = '';
        let bigSize = '';

        let imgtoLoad = 2;

        thumbImg.onload = function () {
            thumbSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) {
                allLoaded();
            }
        };

        bigImg.onload = function () {
            bigSize = this.width + 'x' + this.height;
            if (--imgtoLoad == 0) {
                allLoaded();
            }
        };

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
    };

    // Open Gallery Overview
    api.openGallery = function () {
        if (config.scrollbar) {
            gallery.addClass('scrollbar');
        }

        gallery.addClass('gallery--open');

        setTimeout(() => gallery.find('.gallery__inner').show(), 300);
    };

    api.resetMailForm = function () {
        $('#send-mail-form').trigger('reset');
        $('#mail-form-message').html('');
    };

    // Countdown Function
    api.startCountdown = function (start, element, cb) {
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
    };

    api.printImage = function (imageSrc, cb) {
        const errormsg = i18n('error');

        if (isPrinting) {
            console.log('Printing already: ' + isPrinting);
        } else {
            modal.open('#print_mesg');
            isPrinting = true;
            setTimeout(function () {
                $.ajax({
                    method: 'GET',
                    url: 'api/print.php',
                    data: {
                        filename: imageSrc
                    },
                    success: (data) => {
                        console.log('Picture processed: ', data);

                        if (data.error) {
                            console.log('An error occurred: ', data.error);
                            $('#print_mesg').empty();
                            $('#print_mesg').html(
                                '<div class="modal__body"><span style="color:red">' + data.error + '</span></div>'
                            );
                        }

                        setTimeout(function () {
                            modal.close('#print_mesg');
                            if (data.error) {
                                $('#print_mesg').empty();
                                $('#print_mesg').html(
                                    '<div class="modal__body"><span>' + i18n('printing') + '</span></div>'
                                );
                            }
                            cb();
                            isPrinting = false;
                        }, config.printing_time);
                    },
                    error: (jqXHR, textStatus) => {
                        console.log('An error occurred: ', textStatus);
                        $('#print_mesg').empty();
                        $('#print_mesg').html(
                            '<div class="modal__body"><span style="color:red">' + errormsg + '</span></div>'
                        );

                        setTimeout(function () {
                            modal.close('#print_mesg');
                            $('#print_mesg').empty();
                            $('#print_mesg').html(
                                '<div class="modal__body"><span>' + i18n('printing') + '</span></div>'
                            );
                            cb();
                            isPrinting = false;
                        }, 5000);
                    }
                });
            }, 1000);
        }
    };

    api.deleteImage = function (imageName, cb) {
        $.ajax({
            url: 'api/deletePhoto.php',
            method: 'POST',
            data: {
                file: imageName
            },
            success: (data) => {
                if (data.error) {
                    console.log('Error while deleting image');
                }
                cb(data);
            },
            error: (jqXHR, textStatus) => {
                console.log('Error while deleting image: ', textStatus);
                setTimeout(function () {
                    api.reloadPage();
                }, 5000);
            }
        });
    };

    api.toggleMailDialog = function (img) {
        const mail = $('.send-mail');

        if (mail.hasClass('mail-active')) {
            api.resetMailForm();
            mail.removeClass('mail-active').fadeOut('fast');
        } else {
            $('#mail-form-image').val(img);

            mail.addClass('mail-active').fadeIn('fast');
        }
    };

    //Filter
    $('.imageFilter').on('click', function () {
        api.toggleNav();
    });

    $('.sidenav > div').on('click', function () {
        $('.sidenav > div').removeAttr('class');
        $(this).addClass('activeSidenavBtn');

        imgFilter = $(this).attr('id');
        const result = {file: $('#result').attr('data-img')};
        if (config.dev) {
            console.log('Applying filter', imgFilter, result);
        }
        api.processPic(imgFilter, result);
    });

    // Take Picture Button
    $('.takePic, .newpic').on('click', function (e) {
        e.preventDefault();

        api.thrill('photo');
        $('.newpic').blur();
    });

    // Take Collage Button
    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();

        api.thrill('collage');
        $('.newcollage').blur();
    });

    $('#mySidenav .closebtn').on('click', function (e) {
        e.preventDefault();

        api.closeNav();
    });

    // Open Gallery Button
    $('.gallery-button').on('click', function (e) {
        e.preventDefault();

        api.closeNav();
        api.openGallery($(this));
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

        api.toggleMailDialog(img);
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
                        message.fadeIn().html('<span style="color:green">' + i18n('mailSaved') + '</span>');
                    } else {
                        message.fadeIn().html('<span style="color:green">' + i18n('mailSent') + '</span>');
                    }
                } else {
                    message.fadeIn().html('<span style="color:red">' + result.error + '</span>');
                }
            },
            error: function () {
                message.fadeIn('fast').html('<span style="color: red;">' + i18n('mailError') + '</span>');
            },
            complete: function () {
                form.find('.btn').html(oldValue);
            }
        });
    });

    $('#send-mail-close').on('click', function () {
        api.resetMailForm();
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

        api.reloadPage();
    });

    $('#cups-button').on('click', function (ev) {
        ev.preventDefault();

        const url = `http://${location.hostname}:631/jobs/`;
        const features = 'width=1024,height=600,left=0,top=0,screenX=0,screenY=0,resizable=NO,scrollbars=NO';

        window.open(url, 'newwin', features);
    });

    // Go Fullscreen
    $('#fs-button').on('click', function (e) {
        e.preventDefault();
        if (!document.fullscreenElement) {
            document.body.requestFullscreen();
        } else if (document.fullscreenElement) {
            document.exitFullscreen();
        }
        $('#fs-button').blur();
    });

    // Fake buttons
    $('.triggerPic').on('click', function (e) {
        e.preventDefault();

        api.thrill('photo');
        $('.newpic').blur();
    });

    $('.triggerCollage').on('click', function (e) {
        e.preventDefault();

        api.thrill('collage');
        $('.newcollage').blur();
    });

    $(document).on('keyup', function (ev) {
        if (config.photo_key && parseInt(config.photo_key, 10) === ev.keyCode) {
            if (!takingPic) {
                $('.closeGallery').trigger('click');
                $('.triggerPic').trigger('click');
            } else if (config.dev && takingPic) {
                console.log('Taking photo already in progress!');
            }
        }

        if (config.collage_key && parseInt(config.collage_key, 10) === ev.keyCode) {
            if (!takingPic) {
                $('.closeGallery').trigger('click');
                if (config.use_collage) {
                    $('.triggerCollage').trigger('click');
                } else {
                    if (config.dev) {
                        console.log('Collage key pressed. Please enable collage in your config. Triggering photo now.');
                    }
                    $('.triggerPic').trigger('click');
                }
            } else if (config.dev && takingPic) {
                console.log('Taking photo already in progress!');
            }
        }

        if (config.use_print_result && config.print_key && parseInt(config.print_key, 10) === ev.keyCode) {
            if (isPrinting) {
                console.log('Printing already in progress!');
            } else {
                $('.printbtn').trigger('click');
                $('.printbtn').blur();
            }
        }
    });

    // clear Timeout to not reset the gallery, if you clicked anywhere
    $(document).on('click', function () {
        if (!startPage.is(':visible')) {
            api.resetTimeOut();
        }
    });

    // Disable Right-Click
    if (!config.dev) {
        $(this).on('contextmenu', function (e) {
            e.preventDefault();
        });
    }

    return api;
})();

// Init on domready
$(function () {
    photoBooth.init();
});
