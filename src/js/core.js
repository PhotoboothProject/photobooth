/* globals initPhotoSwipeFromDOM initRemoteBuzzerFromDOM setMainImage remoteBuzzerClient rotaryController globalGalleryHandle photoboothTools photoboothPreview */

const photoBooth = (function () {
    const PhotoStyle = {
            PHOTO: 'photo',
            COLLAGE: 'collage',
            CHROMA: 'chroma'
        },
        CameraDisplayMode = {
            INIT: 1,
            BACKGROUND: 2,
            COUNTDOWN: 3,
            TEST: 4
        },
        PreviewMode = {
            NONE: 'none',
            DEVICE: 'device_cam',
            URL: 'url'
        },
        PreviewStyle = {
            FILL: 'fill',
            CONTAIN: 'contain',
            COVER: 'cover',
            NONE: 'none',
            SCALE_DOWN: 'scale-down'
        },
        CollageFrameMode = {
            OFF: 'off',
            ALWAYS: 'always',
            ONCE: 'once'
        };

    const api = {},
        loader = $('#loader'),
        startPage = $('#start'),
        gallery = $('#gallery'),
        cheese = $('.cheese'),
        resultPage = $('#result'),
        mySideNav = $('#mySidenav'),
        ipcamView = $('#ipcam--view'),
        galimages = $('#galimages'),
        loading = $('.loading'),
        loaderImage = $('.loaderImage'),
        printBtn = $('.printbtn'),
        deleteBtn = $('.deletebtn'),
        qrBtn = $('.qrbtn'),
        counter = $('#counter'),
        resultInner = $('.resultInner'),
        spinner = $('.spinner'),
        sendMail = $('.send-mail'),
        mailMessageForm = $('#mail-form-message'),
        mailImageForm = $('#mail-form-image'),
        mailSendForm = $('#send-mail-form'),
        blocker = $('#blocker'),
        aperture = $('#aperture'),
        idVideoView = $('#video--view'),
        idVideoSensor = $('#video--sensor'),
        pictureFrame = $('#picture--frame'),
        collageFrame = $('#collage--frame'),
        videoView = idVideoView.get(0),
        videoSensor = document.querySelector('#video--sensor'),
        usesBackgroundPreview =
            config.preview.asBackground &&
            config.preview.mode === PreviewMode.DEVICE.valueOf() &&
            ((config.preview.cmd && !config.preview.bsm) || !config.preview.cmd),
        cheeseTime = config.picture.no_cheese ? 0 : config.picture.cheese_time,
        timeToLive = config.picture.time_to_live * 1000,
        continuousCollageTime = config.collage.continuous_time * 1000,
        retryTimeout = config.picture.retry_timeout * 1000,
        notificationTimeout = config.ui.notification_timeout * 1000;

    let timeOut,
        chromaFile = '',
        currentCollageFile = '',
        imgFilter = config.filters.defaults,
        command,
        startTime,
        endTime,
        totalTime;

    api.takingPic = false;
    api.nextCollageNumber = 0;

    api.isTimeOutPending = function () {
        return typeof timeOut !== 'undefined';
    };

    api.resetTimeOut = function () {
        clearTimeout(timeOut);

        photoboothTools.console.log('Timeout for auto reload cleared.');

        if (!api.takingPic) {
            photoboothTools.console.logDev('Timeout for auto reload set to' + timeToLive + ' milliseconds.');
            timeOut = setTimeout(function () {
                photoboothTools.reloadPage();
            }, timeToLive);
        }
    };

    api.reset = function () {
        loader.css('background', config.colors.background_countdown);
        loader.css('background-color', config.colors.background_countdown);
        loader.removeClass('open');
        loader.removeClass('error');
        loading.text('');
        spinner.hide();
        resultPage.hide();
        photoboothTools.modal.empty('#qrCode');
        qrBtn.removeClass('active').attr('style', '');
        api.resetMailForm();
        sendMail.hide();
        gallery.removeClass('gallery--open');
        gallery.find('.gallery__inner').hide();
        idVideoView.hide();
        collageFrame.hide();
        pictureFrame.hide();
        idVideoView.css('z-index', 0);
        idVideoSensor.hide();
        ipcamView.hide();
    };

    api.init = function () {
        api.reset();

        startPage.addClass('open');
        if (usesBackgroundPreview) {
            photoboothPreview.startVideo(CameraDisplayMode.BACKGROUND);
            photoboothTools.console.logDev('Preview: core: start video (BACKGROUND) from api.init.');
        } else if (config.preview.cmd && !config.preview.bsm) {
            photoboothTools.console.logDev('Preview: core: start video (INIT) from api.init.');
            photoboothPreview.startVideo(CameraDisplayMode.INIT);
        }

        initRemoteBuzzerFromDOM();
        rotaryController.focusSet('#start');

        initPhotoSwipeFromDOM('#galimages');

        if (config.ui.shutter_animation && config.ui.shutter_cheese_img !== '') {
            blocker.css('background-image', 'url(' + config.ui.shutter_cheese_img + ')');
        }
    };

    api.navbar = {
        open: function () {
            mySideNav.addClass('sidenav--open');
            rotaryController.focusSet('#mySidenav');
        },
        close: function () {
            mySideNav.removeClass('sidenav--open');
        },
        toggle: function () {
            mySideNav.toggleClass('sidenav--open');

            if (mySideNav.hasClass('sidenav--open')) {
                rotaryController.focusSet('#mySidenav');
            }
        }
    };

    api.stopPreviewAndCaptureFromVideo = function () {
        if (config.preview.camTakesPic) {
            if (photoboothPreview.stream) {
                videoSensor.width = videoView.videoWidth;
                videoSensor.height = videoView.videoHeight;
                videoSensor.getContext('2d').drawImage(videoView, 0, 0);
            }
        }
        if (!config.preview.killcmd || config.preview.camTakesPic) {
            photoboothTools.console.logDev('Preview: core: stopping preview from stopPreviewAndCaptureFromVideo.');
            photoboothPreview.stopPreview();
        }
    };

    api.shutter = {
        start: function () {
            blocker.fadeTo(500, 1);
            setTimeout(
                () => {
                    api.shutter.stop();
                },
                config.picture.no_cheese ? 500 : cheeseTime
            );
        },
        stop: function () {
            aperture.show();
            aperture.animate(
                {
                    width: 0,
                    'padding-bottom': 0
                },
                500,
                function () {
                    blocker.css('opacity', '0');
                    blocker.hide();
                }
            );
            aperture.fadeTo(1000, 0, function () {
                aperture.css('opacity', '1');
                aperture.css('width', '150%');
                aperture.css('padding-bottom', '150%');
                aperture.hide();
            });
        }
    };

    api.showResultInner = function (flag) {
        if (flag) {
            resultInner.addClass('show');
        } else {
            resultInner.removeClass('show');
        }
    };

    api.shellCommand = function (cmd, file = '') {
        command = {
            mode: cmd,
            filename: file
        };

        photoboothTools.console.log('Run', cmd);

        jQuery
            .post('api/shellCommand.php', command)
            .done(function (result) {
                photoboothTools.console.log(cmd, 'result: ', result);
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log(cmd, 'result: ', result);
            });
    };

    api.thrill = function (photoStyle, retry = 0) {
        if (api.takingPic) {
            photoboothTools.console.logDev('ERROR: Taking picture in progress already!');

            return;
        }
        api.navbar.close();
        api.reset();
        api.closeGallery();
        api.showResultInner(false);

        remoteBuzzerClient.inProgress(true);
        api.takingPic = true;
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        if (api.isTimeOutPending()) {
            api.resetTimeOut();
        }

        if (config.pre_photo.cmd) {
            api.shellCommand('pre-command');
        }

        if (currentCollageFile && api.nextCollageNumber) {
            photoStyle = PhotoStyle.COLLAGE;
        }

        if (chromaFile) {
            photoStyle = PhotoStyle.CHROMA;
        }

        photoboothTools.console.log('PhotoStyle: ' + photoStyle);

        photoboothPreview.startVideo(CameraDisplayMode.COUNTDOWN, retry);

        if (
            config.preview.mode !== PreviewMode.NONE.valueOf() &&
            (config.preview.style === PreviewStyle.CONTAIN.valueOf() ||
                config.preview.style === PreviewStyle.SCALE_DOWN.valueOf()) &&
            config.preview.showFrame
        ) {
            if (photoStyle === PhotoStyle.PHOTO && config.picture.take_frame) {
                pictureFrame.show();
            } else if (
                photoStyle === PhotoStyle.COLLAGE &&
                config.collage.take_frame === CollageFrameMode.ALWAYS.valueOf()
            ) {
                collageFrame.show();
            }
        }

        loader.addClass('open');

        if (config.get_request.countdown) {
            const getMode =
                photoStyle === PhotoStyle.PHOTO || photoStyle === PhotoStyle.CHROMA
                    ? config.get_request.picture
                    : config.get_request.collage;
            const getUrl = config.get_request.server + '/' + getMode;
            photoboothTools.getRequest(getUrl);
        }

        api.startCountdown(
            photoStyle === PhotoStyle.COLLAGE ? config.collage.cntdwn_time : config.picture.cntdwn_time,
            counter,
            () => {
                if (config.picture.no_cheese) {
                    photoboothTools.console.log('Cheese is disabled.');
                } else {
                    api.cheese(photoStyle);
                }
            }
        );

        const triggerCnt =
            (photoStyle === PhotoStyle.COLLAGE ? config.collage.cntdwn_time : config.picture.cntdwn_time) -
            config.picture.cntdwn_offset;
        photoboothTools.console.log('Capture image in ' + triggerCnt + ' seconds.');
        setTimeout(() => {
            if (config.preview.camTakesPic && !photoboothPreview.stream && !config.dev.demo_images) {
                api.errorPic({
                    error: 'No preview by device cam available!'
                });
            } else {
                photoboothTools.console.logDev('Capture image.');
                api.takePic(photoStyle, retry);
            }
        }, triggerCnt * 1000);
    };

    api.cheese = function (photoStyle) {
        cheese.empty();
        if (config.ui.shutter_animation && config.ui.shutter_cheese_img !== '') {
            return;
        } else if (photoStyle === PhotoStyle.PHOTO || photoStyle === PhotoStyle.CHROMA) {
            cheese.text(photoboothTools.getTranslation('cheese'));
        } else {
            cheese.text(photoboothTools.getTranslation('cheeseCollage'));
            $('<p>')
                .text(`${api.nextCollageNumber + 1} / ${config.collage.limit}`)
                .appendTo('.cheese');
        }
        setTimeout(() => {
            cheese.empty();
        }, cheeseTime);
    };

    api.takePic = function (photoStyle, retry) {
        remoteBuzzerClient.inProgress(true);

        api.stopPreviewAndCaptureFromVideo();

        const data = {
            filter: imgFilter,
            style: photoStyle.valueOf(),
            canvasimg: videoSensor.toDataURL('image/jpeg')
        };

        if (photoStyle === PhotoStyle.COLLAGE) {
            data.file = currentCollageFile;
            data.collageNumber = api.nextCollageNumber;
        }

        if (photoStyle === PhotoStyle.CHROMA) {
            data.file = chromaFile;
        }

        loader.css('background', config.colors.panel);
        loader.css('background-color', config.colors.panel);

        api.callTakePicApi(data, retry);
    };

    api.retryTakePic = function (photoStyle, retry) {
        api.takingPic = false;
        retry += 1;
        loading.append(
            $('<p class="text-muted">').text(
                photoboothTools.getTranslation('retry_message') + ' ' + retry + '/' + config.picture.retry_on_error
            )
        );
        photoboothTools.console.logDev('Retry to capture image: ' + retry);
        setTimeout(() => {
            api.thrill(photoStyle, retry);
        }, retryTimeout);
    };

    api.callTakePicApi = function (data, retry = 0) {
        if (config.ui.shutter_animation) {
            api.shutter.start();
        }
        startTime = new Date().getTime();
        jQuery
            .post('api/takePic.php', data)
            .done(function (result) {
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.log('Took ' + data.style, result);
                photoboothTools.console.logDev('Taking picture took ' + totalTime + 'ms');
                imgFilter = config.filters.defaults;
                $('#mySidenav .activeSidenavBtn').removeClass('activeSidenavBtn');
                $('#' + imgFilter).addClass('activeSidenavBtn');

                if (result.error) {
                    photoboothTools.console.logDev('Error while taking picture.');
                    if (config.picture.retry_on_error > 0 && retry < config.picture.retry_on_error) {
                        api.retryTakePic(data.style, retry);
                    } else {
                        api.errorPic(result);
                    }
                } else if (result.success === PhotoStyle.COLLAGE) {
                    currentCollageFile = result.file;
                    api.nextCollageNumber = result.current + 1;

                    spinner.hide();
                    loading.empty();
                    idVideoSensor.hide();
                    idVideoView.hide();
                    collageFrame.hide();
                    pictureFrame.hide();

                    let imageUrl = config.foldersJS.tmp + '/' + result.collage_file;
                    const preloadImage = new Image();
                    const picdate = Date.now().toString();
                    preloadImage.onload = () => {
                        loaderImage.css({
                            'background-image': `url(${imageUrl}?filter=${imgFilter}&v=${picdate})`
                        });
                        loaderImage.attr('data-img', picdate);
                    };

                    preloadImage.src = imageUrl;

                    loaderImage.show();

                    photoboothTools.console.logDev(
                        'Taken collage photo number: ' + (result.current + 1) + ' / ' + result.limit
                    );

                    if (result.current + 1 < result.limit) {
                        photoboothTools.console.logDev('core: initialize Media.');
                        photoboothPreview.initializeMedia();
                        api.takingPic = false;
                    }

                    if (config.collage.continuous) {
                        loading.append($('<p>').text(photoboothTools.getTranslation('wait_message')));
                        if (result.current + 1 < result.limit) {
                            setTimeout(() => {
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.thrill(PhotoStyle.COLLAGE);
                            }, continuousCollageTime);
                        } else {
                            currentCollageFile = '';
                            api.nextCollageNumber = 0;
                            setTimeout(() => {
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.processPic(data.style, result);
                            }, continuousCollageTime);
                        }
                    } else {
                        // collage with interruption
                        if (result.current + 1 < result.limit) {
                            $(
                                '<a class="btn rotaryfocus" href="#" id="btnCollageNext">' +
                                    photoboothTools.getTranslation('nextPhoto') +
                                    '</a>'
                            )
                                .appendTo('.loading')
                                .click((ev) => {
                                    ev.stopPropagation();
                                    ev.preventDefault();

                                    loaderImage.css('background-image', 'none');
                                    imageUrl = '';
                                    loaderImage.css('display', 'none');
                                    api.thrill(PhotoStyle.COLLAGE);
                                });

                            remoteBuzzerClient.collageWaitForNext();
                        } else {
                            $(
                                '<a class="btn rotaryfocus" href="#" id="btnCollageProcess">' +
                                    photoboothTools.getTranslation('processPhoto') +
                                    '</a>'
                            )
                                .appendTo('.loading')
                                .click((ev) => {
                                    ev.stopPropagation();
                                    ev.preventDefault();

                                    loaderImage.css('background-image', 'none');
                                    imageUrl = '';
                                    loaderImage.css('display', 'none');
                                    currentCollageFile = '';
                                    api.nextCollageNumber = 0;

                                    api.processPic(data.style, result);
                                });

                            remoteBuzzerClient.collageWaitForProcessing();
                        }

                        $(
                            '<a class="btn rotaryfocus" style="margin-left:2px" href="#">' +
                                photoboothTools.getTranslation('retakePhoto') +
                                '</a>'
                        )
                            .appendTo('.loading')
                            .click((ev) => {
                                ev.stopPropagation();
                                ev.preventDefault();
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.deleteImage(result.collage_file, () => {
                                    setTimeout(function () {
                                        api.nextCollageNumber = result.current;
                                        api.thrill(PhotoStyle.COLLAGE);
                                    }, notificationTimeout);
                                });
                            });

                        loading
                            .append(
                                $('<a class="btn rotaryfocus" style="margin-left:2px" href="#">').text(
                                    photoboothTools.getTranslation('abort')
                                )
                            )
                            .click(() => {
                                location.assign('./');
                            });

                        rotaryController.focusSet('.loading.rotarygroup');
                    }
                } else if (result.success === PhotoStyle.CHROMA) {
                    chromaFile = result.file;
                    api.processPic(data.style, result);
                } else {
                    currentCollageFile = '';
                    api.nextCollageNumber = 0;

                    api.processPic(data.style, result);
                }
            })
            .fail(function (xhr, status, result) {
                cheese.empty();

                if (config.picture.retry_on_error > 0 && retry < config.picture.retry_on_error) {
                    photoboothTools.console.logDev(
                        'ERROR: Taking picture failed. Retrying. Retry: ' +
                            retry +
                            ' / ' +
                            config.picture.retry_on_error
                    );
                    api.retryTakePic(data.style, retry);
                } else {
                    api.errorPic(result);
                }
            });
    };

    api.errorPic = function (data) {
        setTimeout(function () {
            spinner.hide();
            loading.empty();
            cheese.empty();
            idVideoView.hide();
            idVideoSensor.hide();
            collageFrame.hide();
            pictureFrame.hide();
            loader.addClass('error');
            loading.append($('<p>').text(photoboothTools.getTranslation('error')));
            photoboothTools.console.log('An error occurred:', data.error);
            if (config.dev.loglevel > 1) {
                loading.append($('<p class="text-muted">').text(data.error));
            }
            api.takingPic = false;
            remoteBuzzerClient.inProgress(false);
            photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);
            if (config.dev.reload_on_error) {
                loading.append($('<p>').text(photoboothTools.getTranslation('auto_reload')));
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            } else {
                loading.append($('<a class="btn" href="./">').text(photoboothTools.getTranslation('reload')));
            }
        }, 500);
    };

    api.processPic = function (photoStyle, result) {
        startTime = new Date().getTime();
        spinner.show();
        loading.text(
            photoStyle === PhotoStyle.PHOTO || photoStyle === PhotoStyle.CHROMA
                ? photoboothTools.getTranslation('busy')
                : photoboothTools.getTranslation('busyCollage')
        );

        if (photoStyle === PhotoStyle.PHOTO && config.picture.preview_before_processing) {
            const tempImageUrl = config.foldersJS.tmp + '/' + result.file;
            const preloadImage = new Image();
            preloadImage.onload = () => {
                loader.css('background-image', `url(${tempImageUrl})`);
                loader.addClass('showBackgroundImage');
            };
            preloadImage.src = tempImageUrl;
        }

        $.ajax({
            method: 'POST',
            url: 'api/applyEffects.php',
            data: {
                file: result.file,
                filter: imgFilter,
                style: photoStyle
            },
            success: (data) => {
                photoboothTools.console.log(photoStyle + ' processed', data);
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.logDev('Processing ' + photoStyle + ' took ' + totalTime + 'ms');
                photoboothTools.console.logDev('Images:', data.images);

                if (config.get_request.processed) {
                    const getUrl = config.get_request.server + '/' + photoStyle;
                    photoboothTools.getRequest(getUrl);
                }

                if (data.error) {
                    api.errorPic(data);
                } else if (photoStyle === PhotoStyle.CHROMA) {
                    api.renderChroma(data.file);
                } else {
                    api.renderPic(data.file, data.images);
                }
            },
            error: (jqXHR, textStatus) => {
                api.errorPic({
                    error: 'Request failed: ' + textStatus
                });
            }
        });
    };

    api.renderChroma = function (filename) {
        if (config.live_keying.show_all) {
            api.addImage(filename);
        }
        const imageUrl = config.live_keying.show_all
            ? config.foldersJS.images + '/' + filename
            : config.foldersJS.keying + '/' + filename;
        const preloadImage = new Image();

        preloadImage.onload = function () {
            $('body').attr('data-main-image', filename);
            photoboothTools.console.log('Chroma image: ' + config.foldersJS.keying + '/' + filename);
            const chromaimage = config.foldersJS.keying + '/' + filename;

            loader.hide();
            api.chromaimage = filename;
            setMainImage(chromaimage);
        };

        preloadImage.src = imageUrl;

        api.takingPic = false;
        remoteBuzzerClient.inProgress(false);
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        api.resetTimeOut();
    };

    api.showQr = function (modal, filename) {
        photoboothTools.modal.empty(modal);

        const qrHelpText = config.qr.custom_text
            ? config.qr.text
            : photoboothTools.getTranslation('qrHelp') + '</br><b>' + config.webserver.ssid + '</b>';
        const body = $(modal).find('.modal__body');

        $('<button>')
            .on('click touchstart', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();

                photoboothTools.modal.close(modal);
            })
            .append('<i class="' + config.icons.close + '"></i>')
            .css('float', 'right')
            .appendTo(body);
        $('<img src="api/qrcode.php?filename=' + filename + '" alt="qr code"/>')
            .on('load', function () {
                $('<p>')
                    .css('max-width', this.width + 'px')
                    .html(qrHelpText)
                    .appendTo(body);
            })
            .appendTo(body);
        $(modal).addClass('shape--' + config.ui.style);
    };

    api.renderPic = function (filename, files) {
        api.showQr('#qrCode', filename);

        $(document).off('click touchstart', '.printbtn');
        $(document).on('click', '.printbtn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            photoboothTools.printImage(filename, () => {
                remoteBuzzerClient.inProgress(false);
                printBtn.blur();
            });
        });

        if (config.print.auto) {
            setTimeout(function () {
                photoboothTools.printImage(filename, () => {
                    remoteBuzzerClient.inProgress(false);
                });
            }, config.print.auto_delay);
        }

        resultPage
            .find('.deletebtn')
            .off('click')
            .on('click', (ev) => {
                ev.preventDefault();

                const really = config.delete.no_request
                    ? true
                    : confirm(filename + ' ' + photoboothTools.getTranslation('really_delete_image'));
                if (really) {
                    files.forEach(function (file, index, array) {
                        photoboothTools.console.logDev('Index:', index);
                        photoboothTools.console.logDev('Array:', array);
                        api.deleteImage(file, () => {});
                    });
                    setTimeout(function () {
                        photoboothTools.reloadPage();
                    }, notificationTimeout);
                } else {
                    deleteBtn.blur();
                }
            });

        api.addImage(filename);

        const imageUrl = config.foldersJS.images + '/' + filename;
        const preloadImage = new Image();
        preloadImage.onload = () => {
            resultPage.css({
                'background-image': `url(${imageUrl}?filter=${imgFilter})`
            });
            resultPage.attr('data-img', filename);

            startPage.hide();
            resultPage.show();

            api.showResultInner(config.ui.result_buttons);

            loader.removeClass('open');

            loader.css('background-image', 'url()');
            loader.removeClass('showBackgroundImage');

            if (!mySideNav.hasClass('sidenav--open')) {
                rotaryController.focusSet('#result');
            }
        };

        preloadImage.src = imageUrl;

        if (config.post_photo.cmd) {
            api.shellCommand('post-command', filename);
        }

        api.takingPic = false;
        remoteBuzzerClient.inProgress(false);
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        api.resetTimeOut();

        if (config.preview.cmd && !config.preview.bsm) {
            photoboothTools.console.logDev('Preview: core: start video from api.renderPic');
            photoboothPreview.startVideo(CameraDisplayMode.INIT);
        }
    };

    api.addImage = function (imageName) {
        const thumbImg = new Image();
        const bigImg = new Image();
        let thumbSize = '';
        let bigSize = '';

        let imgtoLoad = 2;

        thumbImg.onload = function () {
            thumbSize = this.width + 'x' + this.height;
            if (--imgtoLoad === 0) {
                allLoaded();
            }
        };

        bigImg.onload = function () {
            bigSize = this.width + 'x' + this.height;
            if (--imgtoLoad === 0) {
                allLoaded();
            }
        };

        bigImg.src = config.foldersJS.images + '/' + imageName;
        thumbImg.src = config.foldersJS.thumbs + '/' + imageName;

        function allLoaded() {
            const linkElement = $('<a>').html(thumbImg);

            linkElement.attr('class', 'gallery__img rotaryfocus');
            linkElement.attr('data-size', bigSize);
            linkElement.attr('href', config.foldersJS.images + '/' + imageName);
            linkElement.attr('data-med', config.foldersJS.thumbs + '/' + imageName);
            linkElement.attr('data-med-size', thumbSize);

            if (config.gallery.newest_first) {
                linkElement.prependTo(galimages);
            } else {
                linkElement.appendTo(galimages);
            }

            galimages.children().not('a').remove();
        }
    };

    api.openGallery = function () {
        if (config.gallery.scrollbar) {
            gallery.addClass('scrollbar');
        }

        gallery.addClass('gallery--open');

        setTimeout(() => {
            gallery.find('.gallery__inner').show();
            rotaryController.focusSet('#gallery');
        }, 300);
    };

    api.closeGallery = function () {
        if (typeof globalGalleryHandle !== 'undefined') {
            globalGalleryHandle.close();
        }

        gallery.find('.gallery__inner').hide();
        gallery.removeClass('gallery--open');

        api.showResultInner(true);

        if (resultPage.is(':visible')) {
            rotaryController.focusSet('#result');
        } else if (startPage.is(':visible')) {
            rotaryController.focusSet('#start');
        }
    };

    api.resetMailForm = function () {
        mailSendForm.trigger('reset');
        mailMessageForm.empty();
    };

    api.startCountdown = function (start, element, cb) {
        let count = 0;
        let current = start;
        const stop =
            start > parseInt(config.preview.stop_time, 10) ? start - parseInt(config.preview.stop_time, 10) : start;

        photoboothTools.console.log('Countdown started. Set to ' + current + ' seconds.');

        function timerFunction() {
            element.text(Number(current));
            current--;

            element.removeClass('tick');

            if (count === stop && config.preview.killcmd && !config.preview.camTakesPic) {
                photoboothTools.console.logDev('Preview: core: stopping preview at countdown.');
                photoboothPreview.stopPreview();
            }
            if (count < start) {
                window.setTimeout(() => element.addClass('tick'), 50);
                window.setTimeout(timerFunction, 1000);
            } else {
                element.empty();
                cb();
            }
            count++;
        }

        timerFunction();
    };

    api.deleteImage = function (imageName, cb) {
        const errorMsg =
            photoboothTools.getTranslation('error') + '</br>' + photoboothTools.getTranslation('auto_reload');
        $.ajax({
            url: 'api/deletePhoto.php',
            method: 'POST',
            data: {
                file: imageName
            },
            success: (data) => {
                if (data.success) {
                    const msg =
                        data.file +
                        ' ' +
                        photoboothTools.getTranslation('deleted_successfully') +
                        '</br>' +
                        photoboothTools.getTranslation('auto_reload');
                    photoboothTools.console.log('Deleted ' + data.file);
                    photoboothTools.modalMesg.showSuccess('#modal_mesg', msg);
                } else {
                    photoboothTools.console.log('Error while deleting ' + data.file);
                    photoboothTools.console.log('Failed: ' + data.failed);
                    photoboothTools.modalMesg.showError('#modal_mesg', errorMsg);
                }
                setTimeout(function () {
                    photoboothTools.modalMesg.reset('#modal_mesg');
                }, notificationTimeout);
                cb(data);
            },
            error: (jqXHR, textStatus) => {
                photoboothTools.console.log('Error while deleting image: ', textStatus);
                photoboothTools.modalMesg.showError('#modal_mesg', errorMsg);

                setTimeout(function () {
                    photoboothTools.modalMesg.reset('#modal_mesg');
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            }
        });
    };

    api.toggleMailDialog = function (img) {
        if (sendMail.hasClass('mail-active')) {
            api.resetMailForm();
            sendMail.removeClass('mail-active').fadeOut('fast');
        } else {
            mailImageForm.val(img);
            sendMail.addClass('mail-active').fadeIn('fast');
        }
    };

    $('.imageFilter').on('click', function (e) {
        e.preventDefault();
        api.navbar.toggle();
    });

    $('.sidenav > div').on('click', function () {
        $('.sidenav > div').removeAttr('class');
        $(this).addClass('activeSidenavBtn');

        imgFilter = $(this).attr('id');
        const result = {file: resultPage.attr('data-img')};

        photoboothTools.console.logDev('Applying filter: ' + imgFilter, result);

        api.processPic(imgFilter, result);

        rotaryController.focusSet('#mySidenav');
    });

    $('.takePic, .newpic').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.PHOTO);
        $(this).blur();
    });

    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.COLLAGE);
        $(this).blur();
    });

    $('#mySidenav .closebtn').on('click', function (e) {
        e.preventDefault();

        api.navbar.close();
        rotaryController.focusSet('#result');
    });

    $('.gallery-button, .gallerybtn').on('click', function (e) {
        e.preventDefault();

        api.navbar.close();
        api.openGallery($(this));
    });

    $('.gallery__close').on('click', function (e) {
        e.preventDefault();

        api.closeGallery();
    });

    $('.mailbtn').on('click touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const img = resultPage.attr('data-img');

        api.toggleMailDialog(img);
    });

    mailSendForm.on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('.btn');

        mailMessageForm.empty();
        submitButton.html('<i class="' + config.icons.mail_submit + '"></i>');

        $.ajax({
            url: 'api/sendPic.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            cache: false,
            success: function (result) {
                submitButton.empty();
                submitButton.hide();
                if (result.success) {
                    if (result.saved) {
                        mailMessageForm
                            .fadeIn()
                            .html(
                                '<span style="color:green">' + photoboothTools.getTranslation('mailSaved') + '</span>'
                            );
                    } else {
                        mailMessageForm
                            .fadeIn()
                            .html(
                                '<span style="color:green">' + photoboothTools.getTranslation('mailSent') + '</span>'
                            );
                    }
                } else {
                    mailMessageForm.fadeIn().html('<span style="color:red">' + result.error + '</span>');
                }
            },
            error: function () {
                mailMessageForm
                    .fadeIn('fast')
                    .html('<span style="color: red;">' + photoboothTools.getTranslation('mailError') + '</span>');
            }
        });

        setTimeout(function () {
            submitButton.show();
            if (config.mail.send_all_later) {
                submitButton.html('<span>' + photoboothTools.getTranslation('add') + '</span>');
            } else {
                submitButton.html('<span>' + photoboothTools.getTranslation('send') + '</span>');
            }
        }, notificationTimeout);
    });

    $('#send-mail-close').on('click', function () {
        api.resetMailForm();
        sendMail.removeClass('mail-active').fadeOut('fast');
    });

    resultPage.on('click', function () {
        if (!mySideNav.hasClass('sidenav--open')) {
            rotaryController.focusSet('#result');
        }
    });

    qrBtn.on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        photoboothTools.modal.open('#qrCode');
    });

    $('.homebtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        photoboothTools.reloadPage();

        rotaryController.focusSet('#start');
    });

    $('#cups-button').on('click', function (ev) {
        ev.preventDefault();

        const url = `http://${location.hostname}:631/jobs/`;
        const features = 'width=1024,height=600,left=0,top=0,screenX=0,screenY=0,resizable=NO,scrollbars=NO';

        window.open(url, 'newwin', features);
    });

    $('#fs-button').on('click', function (e) {
        e.preventDefault();
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.body.requestFullscreen();
        }
        $('#fs-button').blur();
    });

    api.handleButtonPressWhileTakingPic = function () {
        if (api.nextCollageNumber > 0) {
            const btnCollageNext = $('#btnCollageNext');
            const btnCollageProcess = $('#btnCollageProcess');
            if (btnCollageNext.length) {
                photoboothTools.console.logDev('Next collage image triggered by keypress.');
                btnCollageNext.trigger('click');
            } else if (btnCollageProcess.length) {
                photoboothTools.console.logDev('Processing collage triggered by keypress.');
                btnCollageProcess.trigger('click');
            } else {
                photoboothTools.console.logDev('Taking picture already in progress!');
            }
        } else {
            photoboothTools.console.logDev('Taking picture already in progress!');
        }
    };

    $(document).on('keyup', function (ev) {
        if (api.isTimeOutPending()) {
            if (typeof onStandaloneGalleryView !== 'undefined' || startPage.is(':visible')) {
                clearTimeout(timeOut);
                photoboothTools.console.logDev('Timeout for auto reload cleared.');
            } else {
                api.resetTimeOut();
            }
        }

        if (typeof onStandaloneGalleryView === 'undefined' && typeof onLiveChromaKeyingView === 'undefined') {
            if (
                (config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) ||
                (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode)
            ) {
                if (api.takingPic) {
                    api.handleButtonPressWhileTakingPic();

                    return;
                }
                api.closeGallery();
            } else if (config.print.from_result && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
                if (photoboothTools.isPrinting) {
                    photoboothTools.console.log('Printing already in progress!');
                } else {
                    printBtn.trigger('click');
                }

                return;
            } else {
                return;
            }

            // picture
            if (config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) {
                if (config.picture.enabled) {
                    api.thrill(PhotoStyle.PHOTO);
                } else {
                    photoboothTools.console.logDev(
                        'Picture key pressed, but taking pictures disabled. Please enable picture in your config.'
                    );
                }
            }

            // collage
            if (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) {
                if (config.collage.enabled) {
                    api.thrill(PhotoStyle.COLLAGE);
                } else {
                    photoboothTools.console.logDev(
                        'Collage key pressed, but Collage disabled. Please enable collage in your config.'
                    );
                }
            }
        }
    });

    $(document).on('click', function () {
        if (api.isTimeOutPending()) {
            if (typeof onStandaloneGalleryView !== 'undefined' || startPage.is(':visible')) {
                clearTimeout(timeOut);
                photoboothTools.console.logDev('Timeout for auto reload cleared.');
            } else {
                api.resetTimeOut();
            }
        }
    });

    // Disable Right-Click
    if (config.dev.loglevel > 0) {
        $(this).on('contextmenu', function (e) {
            e.preventDefault();
        });
    }

    idVideoView.on('loadedmetadata', function (ev) {
        const videoEl = ev.target;
        let newWidth = videoEl.offsetWidth;
        let newHeight = videoEl.offsetHeight;
        if (config.preview.style === PreviewStyle.SCALE_DOWN.valueOf()) {
            newWidth = videoEl.videoWidth;
            newHeight = videoEl.videoHeight;
        }
        if (newWidth !== 0 && newHeight !== 0) {
            pictureFrame.css('width', newWidth);
            pictureFrame.css('height', newHeight);
            collageFrame.css('width', newWidth);
            collageFrame.css('height', newHeight);
        }
    });

    return api;
})();

$(function () {
    photoBooth.init();
});
