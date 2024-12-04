/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals initPhotoSwipeFromDOM initRemoteBuzzerFromDOM processChromaImage remoteBuzzerClient rotaryController globalGalleryHandle photoboothTools photoboothPreview */

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
        startPage = $('.stage[data-stage="start"]'),
        loader = $('.stage[data-stage="loader"]'),
        loaderButtonBar = loader.find('.buttonbar'),
        loaderMessage = loader.find('.stage-message'),
        loaderImage = loader.find('.stage-image'),
        resultPage = $('.stage[data-stage="result"]'),
        previewIpcam = $('#preview--ipcam'),
        previewVideo = $('#preview--video'),
        previewFramePicture = $('#previewframe--picture'),
        previewFrameCollage = $('#previewframe--collage'),
        videoBackground = $('#video-background'),
        videoSensor = $('#video--sensor'),
        buttonDelete = $('[data-command="deletebtn"]'),
        buttonPrint = $('[data-command="printbtn"]'),
        gallery = $('#gallery'),
        filternav = $('#filternav'),
        galimages = $('#galimages'),
        videoAnimation = $('#videoAnimation'),
        resultVideo = $('#resultVideo'),
        resultVideoQR = $('#resultVideoQR'),
        usesBackgroundPreview =
            config.preview.asBackground &&
            config.preview.mode === PreviewMode.DEVICE.valueOf() &&
            ((config.commands.preview && !config.preview.bsm) || !config.commands.preview),
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
        if (timeToLive == 0) {
            return;
        }
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
        loaderButtonBar.empty();
        loaderMessage.empty();
        loaderMessage.removeClass('stage-message--error');
        resultPage.removeAttr('style data-img');
        resultPage.removeClass('stage--active');
        gallery.removeClass('gallery--open');
        gallery.find('.gallery__inner').hide();
        previewVideo.hide();
        previewFrameCollage.hide();
        previewFramePicture.hide();
        previewVideo.css('z-index', 0);
        videoSensor.hide();
        previewIpcam.hide();

        photoboothTools.overlay.close();
        photoboothTools.modal.close();
    };

    api.init = function () {
        api.reset();

        startPage.addClass('stage--active');
        if (usesBackgroundPreview) {
            photoboothPreview.startVideo(CameraDisplayMode.BACKGROUND);
            photoboothTools.console.logDev('Preview: core: start video (BACKGROUND) from api.init.');
        } else if (config.commands.preview && !config.preview.bsm) {
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
                videoSensor.get(0).width = previewVideo.get(0).videoWidth;
                videoSensor.get(0).height = previewVideo.get(0).videoHeight;
                videoSensor.get(0).getContext('2d').drawImage(previewVideo.get(0), 0, 0);
            }
        }
        if (!config.commands.preview_kill || config.preview.camTakesPic) {
            photoboothTools.console.logDev('Preview: core: stopping preview from stopPreviewAndCaptureFromVideo.');
            photoboothPreview.stopPreview();
        }
    };

    api.countdown = {
        element: null,
        audioElement: null,
        create: () => {
            if (api.countdown.element === null) {
                const element = document.createElement('div');
                element.classList.add('countdown');
                document.body.append(element);
                api.countdown.element = element;
            }

            if (api.countdown.audioElement === null) {
                const audioElement = document.createElement('audio');
                document.body.append(audioElement);
                api.countdown.audioElement = audioElement;
            }
        },
        destroy: () => {
            if (api.countdown.element !== null) {
                api.countdown.element.remove();
                api.countdown.element = null;
            }
            if (api.countdown.audioElement !== null) {
                api.countdown.audioElement.remove();
                api.countdown.audioElement = null;
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

                    if (config.sound.enabled && config.sound.countdown_enabled) {
                        const soundfile = photoboothTools.getSound('counter-' + Number(seconds).toString());
                        if (soundfile !== null) {
                            api.countdown.audioElement.src = soundfile;
                            api.countdown.audioElement.play().catch((error) => {
                                photoboothTools.console.log('Error with audio.play: ' + error);
                            });
                        }
                    }

                    seconds--;

                    if (seconds === stop && config.commands.preview_kill && !config.preview.camTakesPic) {
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
        audioElement: null,
        create: () => {
            if (api.cheese.audioElement === null) {
                const audioElement = document.createElement('audio');
                document.body.append(audioElement);
                api.cheese.audioElement = audioElement;
            }

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
            if (api.cheese.audioElement !== null) {
                api.cheese.audioElement.remove();
                api.cheese.audioElement = null;
            }
            if (api.cheese.element !== null) {
                api.cheese.element.remove();
                api.cheese.element = null;
            }
        },
        start: () => {
            photoboothTools.console.log('Cheese: Start');
            api.cheese.create();

            return new Promise((resolve) => {
                if (config.sound.enabled && config.sound.cheese_enabled) {
                    const soundfile = photoboothTools.getSound('cheese');
                    if (soundfile !== null) {
                        api.cheese.audioElement.src = soundfile;
                        api.cheese.audioElement.play().catch((error) => {
                            photoboothTools.console.log('Error with audio.play: ' + error);
                        });
                    }
                }
                setTimeout(() => {
                    photoboothTools.console.log('Cheese: End');
                    resolve();
                }, config.picture.cheese_time);
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

    api.clearLoaderImage = () => {
        loaderImage.css({ display: 'none', 'background-image': '' });
        loaderImage.removeAttr('data-img');
    };

    api.shellCommand = function (cmd, file = '') {
        command = {
            mode: cmd,
            filename: file
        };

        photoboothTools.console.log('Run', cmd);

        jQuery
            .post(environment.publicFolders.api + '/shellCommand.php', command)
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

        if (config.selfie_mode) {
            photoboothTools.console.logDev('ERROR: Taking picture unsupported on selfie mode!');

            return;
        }
        api.navbar.close();
        api.reset();
        api.closeGallery();

        remoteBuzzerClient.inProgress(photoStyle);
        api.takingPic = true;
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        if (api.isTimeOutPending()) {
            api.resetTimeOut();
        }

        if (config.commands.pre_photo) {
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

        let maxGetMediaRetry = Math.max(countdownTime - 1, 0);
        if (config.commands.preview_kill && maxGetMediaRetry > 0) {
            maxGetMediaRetry = Math.max(countdownTime - parseInt(config.preview.stop_time, 10), 0);
        }
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
                previewFramePicture.show();
            } else if (
                api.photoStyle === PhotoStyle.COLLAGE &&
                config.collage.take_frame === CollageFrameMode.ALWAYS.valueOf()
            ) {
                previewFrameCollage.show();
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
        await api.cheese.start();

        if (config.preview.camTakesPic && !photoboothPreview.stream && !config.dev.demo_images) {
            api.errorPic({
                error: 'No preview by device cam available!'
            });
        } else if (api.photoStyle === PhotoStyle.VIDEO) {
            api.takeVideo(retry);
        } else {
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
            canvasimg: videoSensor.get(0).toDataURL('image/jpeg')
        };

        if (api.photoStyle === PhotoStyle.COLLAGE) {
            data.file = currentCollageFile;
            data.collageNumber = api.nextCollageNumber;
        }

        if (api.photoStyle === PhotoStyle.CHROMA) {
            data.file = chromaFile;
        }

        loader.css('--stage-background', config.colors.background_countdown);

        api.callTakePicApi(data, retry);
    };

    api.retryTakePic = function (retry) {
        api.takingPic = false;
        retry += 1;
        loaderMessage.text(
            photoboothTools.getTranslation('retry_message') + ' ' + retry + '/' + config.picture.retry_on_error
        );
        photoboothTools.console.logDev('Retry to capture image: ' + retry);
        setTimeout(() => {
            api.thrill(api.photoStyle, retry);
        }, retryTimeout);
    };

    api.callTakePicApi = async (data, retry = 0) => {
        startTime = new Date().getTime();
        photoboothTools.console.logDev('Capture image.');
        jQuery
            .post({
                url: environment.publicFolders.api + '/capture.php',
                data: data,
                timeout: 15000
            })
            .done(async (result) => {
                api.cheese.destroy();
                if (config.ui.shutter_animation) {
                    await api.shutter.start();
                    await api.shutter.stop();
                }
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.log('Took ' + data.style, result);
                photoboothTools.console.logDev('Taking picture took ' + totalTime + 'ms');
                imgFilter = config.filters.defaults;
                $('#filternav .sidenav-list-item--active').removeClass('sidenav-list-item--active');
                $('.sidenav-list-item[data-filter="' + imgFilter + '"]').addClass('sidenav-list-item--active');
                previewFrameCollage.hide();
                previewFramePicture.hide();
                videoBackground.hide();
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

                    loaderButtonBar.empty();
                    loaderMessage.empty();
                    videoSensor.hide();
                    previewVideo.hide();

                    let imageUrl = environment.publicFolders.tmp + '/' + result.collage_file;
                    const preloadImage = new Image();
                    const picdate = Date.now().toString();
                    preloadImage.onload = () => {
                        loaderImage.attr('data-img', picdate);
                        loaderImage.css('background-image', `url(${imageUrl}?filter=${imgFilter}&v=${picdate})`);
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
                        loaderMessage.append($('<p>').text(photoboothTools.getTranslation('wait_message')));
                        setTimeout(() => {
                            api.clearLoaderImage();
                            imageUrl = '';
                            if (result.current + 1 < result.limit) {
                                api.thrill(PhotoStyle.COLLAGE);
                            } else {
                                currentCollageFile = '';
                                api.nextCollageNumber = 0;
                                api.processPic(result);
                            }
                        }, continuousCollageTime);
                    } else {
                        // collage with interruption
                        if (result.current + 1 < result.limit) {
                            const takePictureButton = $(
                                '<button type="button" class="button rotaryfocus" id="btnCollageNext">'
                            );
                            takePictureButton.append(
                                '<span class="button--icon"><i class="' + config.icons.take_picture + '"></i></span>'
                            );
                            takePictureButton.append(
                                '<span class="button--label">' + photoboothTools.getTranslation('nextPhoto') + '</span>'
                            );
                            takePictureButton.appendTo(loaderButtonBar).on('click', (event) => {
                                event.stopPropagation();
                                event.preventDefault();
                                api.clearLoaderImage();
                                imageUrl = '';
                                api.thrill(PhotoStyle.COLLAGE);
                            });
                            remoteBuzzerClient.collageWaitForNext();
                        } else {
                            const collageProcessButton = $(
                                '<button type="button" class="button rotaryfocus" id="btnCollageProcess">'
                            );
                            collageProcessButton.append(
                                '<span class="button--icon"><i class="' + config.icons.save + '"></i></span>'
                            );
                            collageProcessButton.append(
                                '<span class="button--label">' +
                                    photoboothTools.getTranslation('processPhoto') +
                                    '</span>'
                            );
                            collageProcessButton.appendTo(loaderButtonBar).on('click', (event) => {
                                event.stopPropagation();
                                event.preventDefault();
                                api.clearLoaderImage();
                                imageUrl = '';
                                currentCollageFile = '';
                                api.nextCollageNumber = 0;
                                api.processPic(result);
                            });
                            remoteBuzzerClient.collageWaitForProcessing();
                        }

                        const retakeButton = $('<button type="button" class="button rotaryfocus">');
                        retakeButton.append(
                            '<span class="button--icon"><i class="' + config.icons.refresh + '"></i></span>'
                        );
                        retakeButton.append(
                            '<span class="button--label">' + photoboothTools.getTranslation('retakePhoto') + '</span>'
                        );
                        retakeButton.appendTo(loaderButtonBar).on('click', (event) => {
                            event.stopPropagation();
                            event.preventDefault();
                            api.clearLoaderImage();
                            imageUrl = '';
                            api.deleteImage(result.collage_file, () => {
                                setTimeout(function () {
                                    api.nextCollageNumber = result.current;
                                    api.thrill(PhotoStyle.COLLAGE);
                                }, notificationTimeout);
                            });
                        });

                        const abortButton = $('<button type="button" class="button rotaryfocus">');
                        abortButton.append(
                            '<span class="button--icon"><i class="' + config.icons.delete + '"></i></span>'
                        );
                        abortButton.append(
                            '<span class="button--label">' + photoboothTools.getTranslation('abort') + '</span>'
                        );
                        abortButton.appendTo(loaderButtonBar).on('click', () => {
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
                try {
                    endTime = new Date().getTime();
                    totalTime = endTime - startTime;
                    api.cheese.destroy();
                    if (result === null || result === undefined || typeof result === 'string') {
                        result = { error: result || 'Unexpected error: result is null or undefined' };
                    } else if (!result.error) {
                        result.error = 'Unknown error occurred';
                    }
                    photoboothTools.console.log('Took ' + data.style, result);
                    photoboothTools.console.logDev('Failed after ' + totalTime + 'ms');
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
                } catch (error) {
                    photoboothTools.console.log('Unexpected error in .fail block', error);
                    api.errorPic({ error: error.message || 'An unexpected error occurred during failure handling' });
                }
            });
    };

    api.callTakeVideoApi = function (data) {
        if (config.video.animation) {
            videoAnimation.show();
        }
        startTime = new Date().getTime();
        jQuery
            .post(environment.publicFolders.api + '/capture.php', data)
            .done(async (result) => {
                api.cheese.destroy();
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
                try {
                    api.cheese.destroy();
                    if (result === null || result === undefined || typeof result === 'string') {
                        result = { error: result || 'Unexpected error: result is null or undefined' };
                    } else if (!result.error) {
                        result.error = 'Unknown error occurred';
                    }
                    api.errorPic(result);
                } catch (error) {
                    photoboothTools.console.log('Unexpected error in .fail block', error);
                    api.errorPic({ error: error.message || 'An unexpected error occurred during failure handling' });
                }
            });
    };

    api.errorPic = function (data) {
        setTimeout(function () {
            api.cheese.destroy();
            api.shutter.destroy();

            loaderMessage.empty();
            loaderButtonBar.empty();
            previewVideo.hide();
            videoSensor.hide();
            previewFrameCollage.hide();
            previewFramePicture.hide();
            if (config.video.animation) {
                videoAnimation.hide();
            }
            loaderMessage.addClass('stage-message--error');
            loaderMessage.append($('<p>').text(photoboothTools.getTranslation('error')));
            photoboothTools.console.log('An error occurred:', data.error);
            if (config.dev.loglevel > 1) {
                loaderMessage.append($('<p>').text(data.error));
            }
            api.takingPic = false;
            remoteBuzzerClient.inProgress(false);
            photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);
            if (config.dev.reload_on_error) {
                loaderMessage.append($('<p>').text(photoboothTools.getTranslation('auto_reload')));
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            } else {
                const reloadButton = $('<button type="button" class="button rotaryfocus">');
                reloadButton.append('<span class="button--icon"><i class="' + config.icons.refresh + '"></i></span>');
                reloadButton.append(
                    '<span class="button--label">' + photoboothTools.getTranslation('reload') + '</span>'
                );
                reloadButton.appendTo(loaderButtonBar).on('click', () => {
                    photoboothTools.reloadPage();
                });
            }
        }, 500);
    };

    api.processPic = function (result) {
        startTime = new Date().getTime();
        loaderMessage.html(
            '<i class="' +
                config.icons.spinner +
                '"></i><br>' +
                (api.photoStyle === PhotoStyle.COLLAGE
                    ? photoboothTools.getTranslation('busyCollage')
                    : photoboothTools.getTranslation('busy'))
        );

        if (
            (api.photoStyle === PhotoStyle.PHOTO || api.photoStyle === PhotoStyle.CUSTOM) &&
            config.picture.preview_before_processing
        ) {
            const tempImageUrl = environment.publicFolders.tmp + '/' + result.file;
            const preloadImage = new Image();
            preloadImage.onload = () => {
                loader.css('background-image', `url(${tempImageUrl})`);
                loader.addClass('showBackgroundImage');
            };
            preloadImage.src = tempImageUrl;
        }

        $.ajax({
            method: 'POST',
            url: environment.publicFolders.api + '/applyEffects.php',
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

        videoSensor.hide();
        previewVideo.hide();
        videoBackground.hide();
        loader.css('--stage-background', config.colors.background_countdown);
        loaderMessage.html(
            '<i class="' + config.icons.spinner + '"></i><br>' + photoboothTools.getTranslation('busyVideo')
        );

        $.ajax({
            method: 'POST',
            url: environment.publicFolders.api + '/applyVideoEffects.php',
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
                    const file = environment.publicFolders.images + '/' + data.file;
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
                            resultVideoQR.attr(
                                'src',
                                environment.publicFolders.api + '/qrcode.php?filename=' + data.file
                            );
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

        startPage.removeClass('stage--active');
        loader.removeClass('stage--active');
        resultPage.addClass('stage--active');

        const chromaimage = environment.publicFolders.keying + '/' + filename;
        processChromaImage(chromaimage, true, filename);
        rotaryController.focusSet(resultPage);

        api.takingPic = false;
        remoteBuzzerClient.inProgress(false);
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        api.resetTimeOut();
    };

    api.showMailForm = function (image) {
        photoboothTools.modal.open('mail');
        const body = photoboothTools.modal.element.querySelector('.modal-body');
        const buttonbar = photoboothTools.modal.element.querySelector('.modal-buttonbar');

        // Text
        const text = document.createElement('p');
        text.textContent = config.mail.send_all_later
            ? photoboothTools.getTranslation('insertMailToDB')
            : photoboothTools.getTranslation('insertMail');
        body.appendChild(text);

        // Form
        const form = document.createElement('form');
        form.id = 'send-mail-form';
        form.classList.add('form');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (document.querySelector('#send-mail-message')) {
                document.querySelector('#send-mail-message').remove();
            }
            const message = document.createElement('div');
            message.id = 'send-mail-message';
            message.classList.add('form-message');
            form.appendChild(message);
            const submitButton = document.querySelector('#send-mail-submit');
            submitButton.disabled = true;
            fetch(environment.publicFolders.api + '/sendPic.php', {
                method: 'post',
                body: new FormData(form)
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.querySelector('#send-mail-recipient').value = '';
                        message.classList.add('text-success');
                        if (data.saved) {
                            message.textContent = photoboothTools.getTranslation('mailSaved');
                        } else {
                            message.textContent = photoboothTools.getTranslation('mailSent');
                        }
                    } else {
                        message.classList.add('text-danger');
                        message.textContent = data.error;
                    }
                    submitButton.disabled = false;
                })
                .catch(() => {
                    message.classList.add('text-danger');
                    message.textContent = photoboothTools.getTranslation('mailError');
                    submitButton.disabled = false;
                });
        });
        body.appendChild(form);

        // Image
        const imageInput = document.createElement('input');
        imageInput.type = 'hidden';
        imageInput.name = 'image';
        imageInput.value = image;
        form.appendChild(imageInput);

        // Recipient
        const recipientInput = document.createElement('input');
        recipientInput.classList.add('form-input');
        recipientInput.id = 'send-mail-recipient';
        recipientInput.type = 'email';
        recipientInput.name = 'recipient';
        recipientInput.addEventListener('focusin', (event) => {
            // workaround for photoswipe blocking input
            event.stopImmediatePropagation();
        });
        form.appendChild(recipientInput);

        // Submit
        const submitLabel = config.mail.send_all_later
            ? photoboothTools.getTranslation('add')
            : photoboothTools.getTranslation('send');
        const submitButton = photoboothTools.button.create(submitLabel, 'fa fa-check', 'primary', 'modal-');
        submitButton.id = 'send-mail-submit';
        submitButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            form.requestSubmit();
        });
        buttonbar.insertBefore(submitButton, buttonbar.firstChild);
    };

    api.showQrCode = function (filename) {
        if (!config.qr.enabled) {
            return;
        }

        photoboothTools.modal.open();
        const body = photoboothTools.modal.element.querySelector('.modal-body');

        const image = document.createElement('img');
        image.src = environment.publicFolders.api + '/qrcode.php?filename=' + filename;
        body.appendChild(image);

        const qrHelpText = config.qr.custom_text
            ? config.qr.text
            : photoboothTools.getTranslation('qrHelp') + '<br><b>' + config.webserver.ssid + '</b>';
        const text = document.createElement('p');
        text.innerHTML = qrHelpText;
        body.appendChild(text);
    };

    api.renderPic = function (filename, files) {
        api.filename = filename;

        if (config.print.auto && config.filters.enabled === false) {
            setTimeout(function () {
                photoboothTools.printImage(filename, () => {
                    remoteBuzzerClient.inProgress(false);
                });
            }, config.print.auto_delay);
        }

        buttonPrint.off('click').on('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            photoboothTools.printImage(filename, () => {
                remoteBuzzerClient.inProgress(false);
                buttonPrint.trigger('blur');
            });
        });

        resultPage
            .find('[data-command="qrbtn"]')
            .off('click')
            .on('click', (event) => {
                event.preventDefault();
                api.showQrCode(filename);
            });

        resultPage
            .find('.deletebtn')
            .off('click')
            .on('click', async (ev) => {
                ev.preventDefault();

                const really = config.delete.no_request
                    ? true
                    : await photoboothTools.confirm(
                          filename + ' ' + photoboothTools.getTranslation('really_delete_image')
                      );
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
                    buttonDelete.trigger('blur');
                }
            });

        // gallery doesn't support videos atm
        if (!photoboothTools.isVideoFile(filename)) {
            api.addImage(filename);
        }

        // if image is a video render the qr code as image (video should be displayed over this)
        const imageUrl = photoboothTools.isVideoFile(filename)
            ? environment.publicFolders.api + '/qrcode.php?filename=' + filename
            : environment.publicFolders.images + '/' + filename;

        const preloadImage = new Image();
        preloadImage.onload = () => {
            startPage.removeClass('stage--active');

            resultPage.css({
                '--stage-background-image': `url(${imageUrl}?filter=${imgFilter})`
            });
            resultPage.attr('data-img', filename);
            resultPage.addClass('stage--active');

            loader.removeClass('stage--active showBackgroundImage');
            loader.css('background-image', '');

            if (config.qr.enabled && config.qr.result != 'hidden') {
                if (document.getElementById('resultQR')) {
                    document.getElementById('resultQR').remove();
                }
                const qrResultImage = document.createElement('img');
                qrResultImage.src = environment.publicFolders.api + '/qrcode.php?filename=' + filename;
                qrResultImage.alt = 'qr code';
                qrResultImage.id = 'resultQR';
                qrResultImage.setAttribute('class', 'stage-code ' + config.qr.result);
                resultPage.append(qrResultImage);
            }

            if (!filternav.hasClass('sidenav--open')) {
                rotaryController.focusSet(resultPage);
            }
        };

        preloadImage.src = imageUrl;

        api.takingPic = false;
        remoteBuzzerClient.inProgress(false);
        photoboothTools.console.logDev('Taking picture in progress: ' + api.takingPic);

        api.resetTimeOut();

        if (config.commands.preview && !config.preview.bsm) {
            photoboothTools.console.logDev('Preview: core: start video from api.renderPic');
            photoboothPreview.startVideo(CameraDisplayMode.INIT);
        }

        if (config.commands.post_photo) {
            api.shellCommand('post-command', filename);
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

        bigImg.src = environment.publicFolders.images + '/' + imageName;
        thumbImg.src = environment.publicFolders.thumbs + '/' + imageName;

        function allLoaded() {
            const linkElement = $('<a>').html(thumbImg);

            linkElement.attr('class', 'gallery-list-item rotaryfocus');
            linkElement.attr('data-size', bigSize);
            linkElement.attr('data-pswp-width', bigSizeW);
            linkElement.attr('data-pswp-height', bigSizeH);
            linkElement.attr('href', environment.publicFolders.images + '/' + imageName);
            linkElement.attr('data-med', environment.publicFolders.thumbs + '/' + imageName);
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

        if (resultPage.is(':visible')) {
            rotaryController.focusSet(resultPage);
        } else if (startPage.is(':visible')) {
            rotaryController.focusSet(startPage);
        }
    };

    api.deleteImage = function (imageName, cb) {
        const errorMsg =
            photoboothTools.getTranslation('error') + '</br>' + photoboothTools.getTranslation('auto_reload');
        $.ajax({
            url: environment.publicFolders.api + '/deletePhoto.php',
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
                    photoboothTools.overlay.showSuccess(msg);
                } else {
                    photoboothTools.console.log('Error while deleting ' + data.file);
                    photoboothTools.console.log('Failed: ' + data.failed);
                    photoboothTools.overlay.showError(errorMsg);
                }
                setTimeout(() => photoboothTools.overlay.close(), notificationTimeout);
                cb(data);
            },
            error: (jqXHR, textStatus) => {
                photoboothTools.console.log('Error while deleting image: ', textStatus);
                photoboothTools.overlay.showError(errorMsg);
                setTimeout(() => photoboothTools.reloadPage(), notificationTimeout);
            }
        });
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
        $(this).trigger('blur');
    });

    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.COLLAGE);
        $(this).trigger('blur');
    });

    $('.takeCustom, .newcustom').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.CUSTOM);
        $(this).trigger('blur');
    });

    $('.takeVideo, .newVideo').on('click', function (e) {
        e.preventDefault();
        api.thrill(PhotoStyle.VIDEO);
        $(this).trigger('blur');
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
        api.showMailForm(img);
    });

    resultPage.on('click', function () {
        if (!filternav.hasClass('sidenav--open')) {
            rotaryController.focusSet(resultPage);
        }
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

    $('.print-unlock-button').on('click', function (e) {
        e.preventDefault();
        photoboothTools.overlay.showWaiting(photoboothTools.getTranslation('wait_message'));
        $.ajax({
            method: 'GET',
            url: environment.publicFolders.api + '/printDB.php',
            data: {
                action: 'unlockPrint'
            },
            success: (data) => {
                if (data.success) {
                    photoboothTools.overlay.showSuccess(photoboothTools.getTranslation('success'));
                    $(this).addClass('hidden');
                } else {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                }
                setTimeout(function () {
                    photoboothTools.overlay.close();
                }, 2000);
            }
        });
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

        if (config.selfie_mode) {
            return;
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
                    buttonPrint.trigger('click');
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

    previewVideo.on('loadedmetadata', function (ev) {
        const videoEl = ev.target;
        let newWidth = videoEl.offsetWidth;
        let newHeight = videoEl.offsetHeight;
        if (config.preview.style === PreviewStyle.SCALE_DOWN.valueOf()) {
            newWidth = videoEl.videoWidth;
            newHeight = videoEl.videoHeight;
        }
        if (newWidth !== 0 && newHeight !== 0) {
            previewFramePicture.css({ width: newWidth, height: newHeight });
            previewFrameCollage.css({ width: newWidth, height: newHeight });
        }
    });

    return api;
})();

$(function () {
    photoBooth.init();
});
