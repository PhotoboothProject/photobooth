/* globals initPhotoSwipeFromDOM initRemoteBuzzerFromDOM setMainImage remoteBuzzerClient rotaryController globalGalleryHandle photoboothTools photoboothPreview */

const photoBooth = (function () {
    const PhotoStyle = {
            PHOTO: 'photo',
            COLLAGE: 'collage',
            CHROMA: 'chroma',
            VIDEO: 'video',
            CUSTOM: 'custom'
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
        loader = $('.stage[data-stage="loader"]'),
        startPage = $('.stage[data-stage="start"]'),
        resultPage = $('.stage[data-stage="result"]'),
        gallery = $('#gallery'),
        filternav = $('#filternav'),
        ipcamView = $('#ipcam--view'),
        galimages = $('#galimages'),
        loading = $('.loading'),
        loaderImage = $('.loaderImage'),
        printBtn = $('.printbtn'),
        deleteBtn = $('.deletebtn'),
        qrBtn = $('.qrbtn'),
        resultInner = $('.resultInner'),
        videoAnimation = $('#videoAnimation'),
        resultVideo = $('#resultVideo'),
        resultVideoQR = $('#resultVideoQR'),
        spinner = $('.spinner'),
        sendMail = $('.send-mail'),
        mailMessageForm = $('#mail-form-message'),
        mailImageForm = $('#mail-form-image'),
        mailSendForm = $('#send-mail-form'),
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
    api.chromaimage = '';
    api.filename = '';
    api.photoStyle = '';

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
        loader.css('--stage-background', config.colors.background_countdown);
        loader.removeClass('stage--active');
        loader.removeClass('error');
        loading.text('');
        spinner.hide();

        resultPage.removeClass('stage--active');
        resultPage.attr('style', null);
        resultPage.attr('data-img', null);

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

        startPage.addClass('stage--active');
        if (usesBackgroundPreview) {
            photoboothPreview.startVideo(CameraDisplayMode.BACKGROUND);
            photoboothTools.console.logDev('Preview: core: start video (BACKGROUND) from api.init.');
        } else if (config.preview.cmd && !config.preview.bsm) {
            photoboothTools.console.logDev('Preview: core: start video (INIT) from api.init.');
            photoboothPreview.startVideo(CameraDisplayMode.INIT);
        }

        initRemoteBuzzerFromDOM();
        rotaryController.focusSet(startPage);

        initPhotoSwipeFromDOM('#galimages');
    };

    api.navbar = {
        open: function () {
            filternav.addClass('sidenav--open');
            rotaryController.focusSet(filternav);
        },
        close: function () {
            filternav.removeClass('sidenav--open');
        },
        toggle: function () {
            filternav.toggleClass('sidenav--open');

            if (filternav.hasClass('sidenav--open')) {
                rotaryController.focusSet(filternav);
            }
        }
    };

    api.stopPreviewAndCaptureFromVideo = () => {
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

    api.countdown = {
        element: null,
        create: () => {
            if (api.countdown.element === null) {
                const element = document.createElement('div');
                element.classList.add('countdown');
                document.body.append(element);
                api.countdown.element = element;
            }
        },
        destroy: () => {
            if (api.countdown.element !== null) {
                api.countdown.element.remove();
                api.countdown.element = null;
            }
        },
        start: (seconds) => {
            photoboothTools.console.log('Countdown started. Set to ' + seconds + ' seconds.');
            api.countdown.create();

            return new Promise((resolve) => {
                const stop =
                    seconds > parseInt(config.preview.stop_time, 10)
                        ? seconds - parseInt(config.preview.stop_time, 10)
                        : seconds;
                const interval = setInterval(() => {
                    const numberElement = document.createElement('div');
                    numberElement.classList.add('countdown-number');
                    numberElement.textContent = Number(seconds).toString();
                    api.countdown.element.innerHtml = '';
                    api.countdown.element.appendChild(numberElement);
                    seconds--;

                    if (seconds === stop && config.preview.killcmd && !config.preview.camTakesPic) {
                        photoboothTools.console.logDev('Preview: core: stopping preview at countdown.');
                        photoboothPreview.stopPreview();
                    }

                    if (seconds < 0) {
                        api.countdown.destroy();
                        clearInterval(interval);
                        photoboothTools.console.log('Countdown finished.');
                        resolve();
                    }
                }, 1000);
            });
        }
    };

    api.cheese = {
        element: null,
        create: () => {
            if (api.cheese.element === null) {
                const element = document.createElement('div');
                element.classList.add('cheese');

                if (config.ui.shutter_cheese_img !== '') {
                    const image = document.createElement('img');
                    image.src = config.ui.shutter_cheese_img;
                    const imageElement = document.createElement('div');
                    imageElement.classList.add('cheese-image');
                    imageElement.appendChild(image);
                    element.appendChild(imageElement);
                } else if (api.photoStyle === PhotoStyle.VIDEO) {
                    const labelElement = document.createElement('div');
                    labelElement.classList.add('cheese-label');
                    labelElement.textContent = config.video.cheese;
                    element.appendChild(labelElement);
                } else if (api.photoStyle === PhotoStyle.COLLAGE) {
                    const labelElement = document.createElement('div');
                    labelElement.classList.add('cheese-label');
                    labelElement.textContent =
                        photoboothTools.getTranslation('cheese') +
                        ' ' +
                        (api.nextCollageNumber + 1) +
                        ' / ' +
                        config.collage.limit;
                    element.appendChild(labelElement);
                } else {
                    const labelElement = document.createElement('div');
                    labelElement.classList.add('cheese-label');
                    labelElement.textContent = photoboothTools.getTranslation('cheese');
                    element.appendChild(labelElement);
                }

                document.body.append(element);
                api.cheese.element = element;
            }
        },
        destroy: () => {
            if (api.cheese.element !== null) {
                api.cheese.element.remove();
                api.cheese.element = null;
            }
        },
        start: () => {
            photoboothTools.console.log('Cheese: Start');
            api.cheese.create();

            return new Promise((resolve) => {
                setTimeout(
                    () => {
                        photoboothTools.console.log('Cheese: End');
                        api.cheese.destroy();
                        resolve();
                    },
                    config.picture.no_cheese ? 0 : config.picture.cheese_time
                );
            });
        }
    };

    api.shutter = {
        element: null,
        create: () => {
            if (api.shutter.element === null) {
                const flash = document.createElement('div');
                flash.classList.add('shutter-flash');
                const aperture = document.createElement('div');
                aperture.classList.add('shutter-aperture');
                const element = document.createElement('div');
                element.classList.add('shutter');
                element.appendChild(flash);
                element.appendChild(aperture);
                document.body.append(element);
                api.shutter.element = element;
            }
        },
        destroy: () => {
            if (api.shutter.element !== null) {
                api.shutter.element.remove();
                api.shutter.element = null;
            }
        },
        start: () => {
            api.shutter.create();

            return new Promise((resolve) => {
                photoboothTools.console.log('Shutter: Start');
                const flash = api.shutter.element.querySelector('.shutter-flash');
                flash.style.transition = 'opacity 0.5s';
                const flashAnimation = flash.animate([{}, { opacity: 1 }], {
                    duration: 500,
                    fill: 'forwards'
                });
                flashAnimation.onfinish = () => {
                    resolve();
                };
            });
        },
        stop: () => {
            api.shutter.create();

            return new Promise((resolve) => {
                photoboothTools.console.log('Shutter: Stop');
                const aperture = api.shutter.element.querySelector('.shutter-aperture');
                aperture.style.transition = 'width 0.5s, padding-bottom 0.5s';
                const apertureAnimation = aperture.animate(
                    [
                        {},
                        {
                            width: 0,
                            paddingBottom: 0
                        }
                    ],
                    {
                        duration: 500,
                        fill: 'forwards'
                    }
                );
                apertureAnimation.onfinish = () => {
                    api.shutter.destroy();
                    resolve();
                };
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
            .post(config.foldersJS.api + '/shellCommand.php', command)
            .done(function (result) {
                photoboothTools.console.log(cmd, 'result: ', result);
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log(cmd, 'result: ', result);
            });
    };

    api.thrill = async (photoStyle, retry = 0) => {
        if (api.takingPic) {
            photoboothTools.console.logDev('ERROR: Taking picture in progress already!');

            return;
        }
        api.navbar.close();
        api.reset();
        api.closeGallery();
        api.showResultInner(false);

        remoteBuzzerClient.inProgress(photoStyle);
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
        api.photoStyle = photoStyle;
        photoboothTools.console.log('PhotoStyle: ' + api.photoStyle);

        let countdownTime;
        switch (api.photoStyle) {
            case PhotoStyle.COLLAGE:
                countdownTime = config.collage.cntdwn_time;
                break;
            case PhotoStyle.VIDEO:
                countdownTime = config.video.cntdwn_time;
                break;
            case PhotoStyle.CUSTOM:
                countdownTime = config.custom.cntdwn_time;
                break;
            case PhotoStyle.PHOTO:
            default:
                countdownTime = config.picture.cntdwn_time;
                break;
        }

        const maxGetMediaRetry = countdownTime - (config.picture.cntdwn_offset + 1);
        console.log(maxGetMediaRetry);
        photoboothPreview.startVideo(CameraDisplayMode.COUNTDOWN, retry, maxGetMediaRetry);

        if (
            config.preview.mode !== PreviewMode.NONE.valueOf() &&
            (config.preview.style === PreviewStyle.CONTAIN.valueOf() ||
                config.preview.style === PreviewStyle.SCALE_DOWN.valueOf()) &&
            config.preview.showFrame
        ) {
            if (
                (api.photoStyle === PhotoStyle.PHOTO || api.photoStyle === PhotoStyle.CUSTOM) &&
                config.picture.take_frame
            ) {
                pictureFrame.show();
            } else if (
                api.photoStyle === PhotoStyle.COLLAGE &&
                config.collage.take_frame === CollageFrameMode.ALWAYS.valueOf()
            ) {
                collageFrame.show();
            }
        }

        startPage.removeClass('stage--active');
        loader.addClass('stage--active');

        if (config.get_request.countdown) {
            let getMode;
            switch (api.photoStyle) {
                case PhotoStyle.COLLAGE:
                    getMode = config.get_request.collage;
                    break;
                case PhotoStyle.VIDEO:
                    getMode = config.get_request.video;
                    break;
                case PhotoStyle.CUSTOM:
                    getMode = config.get_request.custom;
                    break;
                case PhotoStyle.PHOTO:
                default:
                    getMode = config.get_request.picture;
                    break;
            }
            const getUrl = config.get_request.server + '/' + getMode;
            photoboothTools.getRequest(getUrl);
        }

        await api.countdown.start(countdownTime);
        if (config.picture.no_cheese) {
            photoboothTools.console.log('Cheese is disabled.');
        } else {
            await api.cheese.start();
        }

        if (config.preview.camTakesPic && !photoboothPreview.stream && !config.dev.demo_images) {
            api.errorPic({
                error: 'No preview by device cam available!'
            });
        } else if (api.photoStyle === PhotoStyle.VIDEO) {
            api.takeVideo(retry);
        } else {
            photoboothTools.console.logDev('Capture image.');
            api.takePic(retry);
        }
    };

    api.takeVideo = function (retry) {
        remoteBuzzerClient.inProgress('in-progress');
        const data = {
            style: api.photoStyle
        };
        api.callTakeVideoApi(data, retry);
    };

    api.takePic = function (retry) {
        remoteBuzzerClient.inProgress('in-progress');

        api.stopPreviewAndCaptureFromVideo();

        const data = {
            filter: imgFilter,
            style: api.photoStyle,
            canvasimg: videoSensor.toDataURL('image/jpeg')
        };

        if (api.photoStyle === PhotoStyle.COLLAGE) {
            data.file = currentCollageFile;
            data.collageNumber = api.nextCollageNumber;
        }

        if (api.photoStyle === PhotoStyle.CHROMA) {
            data.file = chromaFile;
        }

        loader.css('--stage-background', config.colors.panel);

        api.callTakePicApi(data, retry);
    };

    api.retryTakePic = function (retry) {
        api.takingPic = false;
        retry += 1;
        loading.append(
            $('<p class="text-muted">').text(
                photoboothTools.getTranslation('retry_message') + ' ' + retry + '/' + config.picture.retry_on_error
            )
        );
        photoboothTools.console.logDev('Retry to capture image: ' + retry);
        setTimeout(() => {
            api.thrill(api.photoStyle, retry);
        }, retryTimeout);
    };

    api.callTakePicApi = async (data, retry = 0) => {
        if (config.ui.shutter_animation) {
            await api.shutter.start();
        }
        startTime = new Date().getTime();
        jQuery
            .post(config.foldersJS.api + '/capture.php', data)
            .done(async (result) => {
                if (config.ui.shutter_animation) {
                    await api.shutter.stop();
                }
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.log('Took ' + data.style, result);
                photoboothTools.console.logDev('Taking picture took ' + totalTime + 'ms');
                imgFilter = config.filters.defaults;
                $('#filternav .sidenav-list-item--active').removeClass('sidenav-list-item--active');
                $('.sidenav-list-item[data-filter="' + imgFilter + '"]').addClass('sidenav-list-item--active');

                if (result.error) {
                    photoboothTools.console.logDev('Error while taking picture.');
                    if (config.picture.retry_on_error > 0 && retry < config.picture.retry_on_error) {
                        api.retryTakePic(retry);
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
                                api.processPic(result);
                            }, continuousCollageTime);
                        }
                    } else {
                        // collage with interruption
                        if (result.current + 1 < result.limit) {
                            const takePictureButton = $('<button type="button" class="button rotaryfocus" id="btnCollageNext">')
                            takePictureButton.append('<span class="button--icon"><i class="' + config.icons.take_picture + '"></i></span>');
                            takePictureButton.append('<span class="button--label">' + photoboothTools.getTranslation('nextPhoto') + '</span>');
                            takePictureButton.appendTo('.loading').on('click', (event) => {
                                event.stopPropagation();
                                event.preventDefault();
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.thrill(PhotoStyle.COLLAGE);
                            });
                            remoteBuzzerClient.collageWaitForNext();
                        } else {
                            const collageProcessButton = $('<button type="button" class="button rotaryfocus" id="btnCollageProcess">')
                            collageProcessButton.append('<span class="button--icon"><i class="' + config.icons.save + '"></i></span>');
                            collageProcessButton.append('<span class="button--label">' + photoboothTools.getTranslation('processPhoto') + '</span>');
                            collageProcessButton.appendTo('.loading').on('click', (event) => {
                                event.stopPropagation();
                                event.preventDefault();
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                currentCollageFile = '';
                                api.nextCollageNumber = 0;
                                api.processPic(result);
                            });
                            remoteBuzzerClient.collageWaitForProcessing();
                        }

                        const retakeButton = $('<button type="button" class="button rotaryfocus">')
                        retakeButton.append('<span class="button--icon"><i class="' + config.icons.refresh + '"></i></span>');
                        retakeButton.append('<span class="button--label">' + photoboothTools.getTranslation('retakePhoto') + '</span>');
                        retakeButton.appendTo('.loading').on('click', (event) => {
                            event.stopPropagation();
                            event.preventDefault();
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

                        const abortButton = $('<button type="button" class="button rotaryfocus">')
                        abortButton.append('<span class="button--icon"><i class="' + config.icons.delete + '"></i></span>');
                        abortButton.append('<span class="button--label">' + photoboothTools.getTranslation('abort') + '</span>');
                        abortButton.appendTo('.loading').on('click', () => {
                            location.assign('./');
                        });

                        rotaryController.focusSet(loader);
                    }
                } else if (result.success === PhotoStyle.CHROMA) {
                    chromaFile = result.file;
                    api.processPic(result);
                } else {
                    currentCollageFile = '';
                    api.nextCollageNumber = 0;

                    api.processPic(result);
                }
            })
            .fail(async (xhr, status, result) => {
                if (config.ui.shutter_animation) {
                    await api.shutter.stop();
                }
                if (config.picture.retry_on_error > 0 && retry < config.picture.retry_on_error) {
                    photoboothTools.console.logDev(
                        'ERROR: Taking picture failed. Retrying. Retry: ' +
                            retry +
                            ' / ' +
                            config.picture.retry_on_error
                    );
                    api.retryTakePic(retry);
                } else {
                    api.errorPic(result);
                }
            });
    };

    api.callTakeVideoApi = function (data) {
        if (config.video.animation) {
            videoAnimation.show();
        }
        startTime = new Date().getTime();
        jQuery
            .post(config.foldersJS.api + '/capture.php', data)
            .done(function (result) {
                if (config.video.animation) {
                    videoAnimation.hide();
                }
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.log('Took ' + data.style, result);
                photoboothTools.console.logDev('Taking video took ' + totalTime + 'ms');
                imgFilter = config.filters.defaults;
                $('#filternav .sidenav-list-item--active').removeClass('sidenav-list-item--active');
                $('.sidenav-list-item[data-filter="' + imgFilter + '"]').addClass('sidenav-list-item--active');

                if (result.error) {
                    api.errorPic(result);
                } else {
                    api.processVideo(result);
                }
            })
            .fail(function (xhr, status, result) {
                api.errorPic(result);
            });
    };

    api.errorPic = function (data) {
        setTimeout(function () {
            spinner.hide();
            loading.empty();
            idVideoView.hide();
            idVideoSensor.hide();
            collageFrame.hide();
            pictureFrame.hide();
            if (config.video.animation) {
                videoAnimation.hide();
            }
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
                loading.append($('<a class="btn" href="/">').text(photoboothTools.getTranslation('reload')));
            }
        }, 500);
    };

    api.processPic = function (result) {
        startTime = new Date().getTime();
        spinner.show();
        loading.text(
            api.photoStyle === PhotoStyle.COLLAGE
                ? photoboothTools.getTranslation('busyCollage')
                : photoboothTools.getTranslation('busy')
        );

        if (
            (api.photoStyle === PhotoStyle.PHOTO || api.photoStyle === PhotoStyle.CUSTOM) &&
            config.picture.preview_before_processing
        ) {
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
            url: config.foldersJS.api + '/applyEffects.php',
            data: {
                file: result.file,
                filter: imgFilter,
                style: api.photoStyle
            },
            success: (data) => {
                photoboothTools.console.log(api.photoStyle + ' processed', data);
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.logDev('Processing ' + api.photoStyle + ' took ' + totalTime + 'ms');
                photoboothTools.console.logDev('Images:', data.images);

                if (config.get_request.processed) {
                    const getUrl = config.get_request.server + '/' + api.photoStyle;
                    photoboothTools.getRequest(getUrl);
                }

                if (data.error) {
                    api.errorPic(data);
                } else if (api.photoStyle === PhotoStyle.CHROMA) {
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

    api.processVideo = function (result) {
        startTime = new Date().getTime();

        idVideoSensor.hide();
        idVideoView.hide();
        loader.css('--stage-background', config.colors.panel);
        spinner.show();
        loading.text(photoboothTools.getTranslation('busyVideo'));

        $.ajax({
            method: 'POST',
            url: config.foldersJS.api + '/applyVideoEffects.php',
            data: {
                file: result.file
            },
            success: (data) => {
                photoboothTools.console.log('video processed', data);
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.logDev('Processing video took ' + totalTime + 'ms');
                photoboothTools.console.logDev('Video:', data.file);

                if (config.get_request.processed) {
                    const getUrl = config.get_request.server + '/video';
                    photoboothTools.getRequest(getUrl);
                }

                if (data.error) {
                    api.errorPic(data);
                } else {
                    // if collage exists: render the result for the collage image and overlay the video over the image
                    const collage = data.file + '-collage.jpg';
                    const filename = data.images.includes(collage) ? collage : data.file;
                    api.renderPic(filename, data.images);
                    const file = config.foldersJS.images + '/' + data.file;
                    if (!config.video.collage_only) {
                        if (config.video.gif) {
                            resultVideo.attr('src', file);
                        } else {
                            const source = document.createElement('source');
                            source.setAttribute('src', file);
                            source.setAttribute('type', 'video/mp4');
                            resultVideo.append(source);
                            resultVideo.get(0).play();
                        }
                        resultVideo.show();
                        if (config.video.qr) {
                            resultVideoQR.attr('src', config.foldersJS.api + '/qrcode.php?filename=' + data.file);
                            resultVideoQR.show();
                        }
                    }
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
        api.filename = filename;

        if (config.keying.show_all) {
            api.addImage(filename);
        }
        loader.hide();

        const chromaimage = config.foldersJS.keying + '/' + filename;
        setMainImage(chromaimage, true, filename);

        api.takingPic = false;
        remoteBuzzerClient.inProgress(false);
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        api.resetTimeOut();
    };

    api.showQr = function (modal, filename) {
        if (!config.qr.enabled) {
            return;
        }
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
        $(
            '<img src="' +
                config.foldersJS.api +
                '/qrcode.php?filename=' +
                filename +
                '" alt="qr code" style="max-width: 100%;"/>'
        )
            .on('load', function () {
                $('<p>')
                    .css('max-width', this.width + 'px')
                    .html(qrHelpText)
                    .appendTo(body);
            })
            .appendTo(body);
    };

    api.renderPic = function (filename, files) {
        api.filename = filename;
        api.showQr('#qrCode', filename);

        if (config.print.from_result) {
            $(document).off('click touchstart', '.printbtn');
            $(document).on('click', '.printbtn', function (e) {
                e.preventDefault();
                e.stopPropagation();

                photoboothTools.printImage(filename, () => {
                    remoteBuzzerClient.inProgress(false);
                    printBtn.blur();
                });
            });
        }

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
                        api.deleteImage(file, () => {
                            return;
                        });
                    });
                    setTimeout(function () {
                        photoboothTools.reloadPage();
                    }, notificationTimeout);
                } else {
                    deleteBtn.blur();
                }
            });

        // gallery doesn't support videos atm
        if (!photoboothTools.isVideoFile(filename)) {
            api.addImage(filename);
        }

        // if image is a video render the qr code as image (video should be displayed over this)
        const imageUrl = photoboothTools.isVideoFile(filename)
            ? config.foldersJS.api + '/qrcode.php?filename=' + filename
            : config.foldersJS.images + '/' + filename;

        const preloadImage = new Image();
        preloadImage.onload = () => {
            resultPage.css({
                '--stage-background-image': `url(${imageUrl}?filter=${imgFilter})`
            });
            resultPage.attr('data-img', filename);

            startPage.removeClass('stage--active');
            resultPage.addClass('stage--active');

            api.showResultInner(true);

            loader.removeClass('stage--active');
            loader.removeClass('showBackgroundImage');
            loader.css('background-image', null);
            if (config.qr.enabled && config.qr.result != 'hidden') {
                $(
                    '<img src="' +
                        config.foldersJS.api +
                        '/qrcode.php?filename=' +
                        filename +
                        '" alt="qr code" id="resultQR" class="' +
                        config.qr.result +
                        '"/>'
                ).appendTo(resultPage);
            }
            if (!filternav.hasClass('sidenav--open')) {
                rotaryController.focusSet(resultPage);
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
        if (!config.gallery.enabled) {
            return;
        }
        const thumbImg = new Image();
        const bigImg = new Image();
        let thumbSize = '';
        let bigSize = '';
        let bigSizeW = '';
        let bigSizeH = '';

        let imgtoLoad = 2;

        thumbImg.onload = function () {
            thumbSize = this.width + 'x' + this.height;
            if (--imgtoLoad === 0) {
                allLoaded();
            }
        };

        bigImg.onload = function () {
            bigSizeW = this.width;
            bigSizeH = this.height;
            bigSize = bigSizeW + 'x' + bigSizeH;
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
            linkElement.attr('data-pswp-width', bigSizeW);
            linkElement.attr('data-pswp-height', bigSizeH);
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
            rotaryController.focusSet(gallery);
        }, 300);
    };

    api.closeGallery = function () {
        if (typeof globalGalleryHandle !== 'undefined') {
            if (globalGalleryHandle.pswp) {
                globalGalleryHandle.pswp.close();
            }
        }

        gallery.find('.gallery__inner').hide();
        gallery.removeClass('gallery--open');

        api.showResultInner(true);

        if (resultPage.is(':visible')) {
            rotaryController.focusSet(resultPage);
        } else if (startPage.is(':visible')) {
            rotaryController.focusSet(startPage);
        }
    };

    api.resetMailForm = function () {
        mailSendForm.trigger('reset');
        mailMessageForm.empty();
    };

    api.deleteImage = function (imageName, cb) {
        const errorMsg =
            photoboothTools.getTranslation('error') + '</br>' + photoboothTools.getTranslation('auto_reload');
        $.ajax({
            url: config.foldersJS.api + '/deletePhoto.php',
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

    $('.sidenav-list-item[data-filter]').on('click', function () {
        $('.sidenav').find('.sidenav-list-item--active').removeClass('sidenav-list-item--active');
        $(this).addClass('sidenav-list-item--active');

        imgFilter = $(this).data('filter');
        const result = { file: resultPage.attr('data-img') };

        photoboothTools.console.logDev('Applying filter: ' + imgFilter, result);

        api.processPic(result);

        rotaryController.focusSet(filternav);
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

    $('.takeCustom, .newcustom').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.CUSTOM);
        $(this).blur();
    });

    $('.takeVideo, .newVideo').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.VIDEO);
        $(this).blur();
    });

    $('[data-command="sidenav-close"]').on('click', function (e) {
        e.preventDefault();
        api.navbar.close();
        rotaryController.focusSet(resultPage);
    });

    $('.gallery-button, .gallerybtn').on('click', function (e) {
        e.preventDefault();
        api.navbar.close();
        api.openGallery($(this));
    });

    $('[data-command="gallery__refresh"]').on('click', function (e) {
        e.preventDefault();
        photoboothTools.reloadPage();
    });

    $('[data-command="gallery__close"]').on('click', function (e) {
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
            url: config.foldersJS.api + '/sendPic.php',
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
        if (!filternav.hasClass('sidenav--open')) {
            rotaryController.focusSet(resultPage);
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
        rotaryController.focusSet(startPage);
    });

    $('.cups-button').on('click', function (ev) {
        ev.preventDefault();

        const url = `http://${location.hostname}:631/jobs/`;
        const features = 'width=1024,height=600,left=0,top=0,screenX=0,screenY=0,resizable=NO,scrollbars=NO';

        window.open(url, 'newwin', features);
    });

    $('.fs-button').on('click', function (e) {
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

        if (typeof onStandaloneGalleryView === 'undefined' && typeof onCaptureChromaView === 'undefined') {
            if (
                (config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) ||
                (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) ||
                (config.custom.key && parseInt(config.custom.key, 10) === ev.keyCode)
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

            // custom
            if (config.custom.key && parseInt(config.custom.key, 10) === ev.keyCode) {
                if (config.custom.enabled) {
                    api.thrill(PhotoStyle.CUSTOM);
                } else {
                    photoboothTools.console.logDev(
                        'Custom key pressed, but custom action disabled. Please enable custom action in your config.'
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
