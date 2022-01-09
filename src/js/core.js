/* globals initPhotoSwipeFromDOM initRemoteBuzzerFromDOM setMainImage remoteBuzzerClient rotaryController globalGalleryHandle photoboothTools */

const photoBooth = (function () {
    // vars
    const api = {},
        loader = $('#loader'),
        startPage = $('#start'),
        wrapper = $('#wrapper'),
        gallery = $('#gallery'),
        cheese = $('.cheese'),
        resultPage = $('#result'),
        mySideNav = $('#mySidenav'),
        ipcamView = $('#ipcam--view'),
        galimages = $('#galimages'),
        printMesg = $('#print_mesg'),
        loading = $('.loading'),
        loaderImage = $('.loaderImage'),
        triggerPic = $('.triggerPic'),
        triggerCollage = $('.triggerCollage'),
        printBtn = $('.printbtn'),
        deleteBtn = $('.deletebtn'),
        qrCodeModal = $('#qrCode'),
        counter = $('#counter'),
        resultInner = $('.resultInner'),
        spinner = $('.spinner'),
        sendMail = $('.send-mail'),
        idVideoView = $('#video--view'),
        idVideoPreview = $('#video--preview'),
        idVideoSensor = $('#video--sensor'),
        webcamConstraints = {
            audio: false,
            video: {
                width: config.preview.videoWidth,
                height: config.preview.videoHeight,
                facingMode: config.preview.camera_mode
            }
        },
        videoView = $('#video--view').get(0),
        videoPreview = $('#video--preview').get(0),
        videoSensor = document.querySelector('#video--sensor');

    let timeOut,
        isPrinting = false,
        takingPic = false,
        nextCollageNumber = 0,
        chromaFile = '',
        currentCollageFile = '',
        imgFilter = config.filters.defaults,
        pid,
        command,
        startTime,
        endTime,
        totalTime;

    // Returns true when timeOut is pending
    api.isTimeOutPending = function () {
        return typeof timeOut !== 'undefined';
    };

    // timeOut function
    api.resetTimeOut = function () {
        clearTimeout(timeOut);

        photoboothTools.console.log('Timeout for auto reload cleared.');

        if (!takingPic) {
            photoboothTools.console.logDev(
                'Timeout for auto reload set to',
                config.picture.time_to_live * 1000,
                ' seconds.'
            );
            timeOut = setTimeout(function () {
                photoboothTools.reloadPage();
            }, config.picture.time_to_live * 1000);
        }
    };

    // reset whole thing
    api.reset = function () {
        loader.removeClass('open');
        loader.removeClass('error');
        photoboothTools.modal.empty('#qrCode');
        $('.qrbtn').removeClass('active').attr('style', '');
        loading.text('');
        gallery.removeClass('gallery--open');
        gallery.find('.gallery__inner').hide();
        spinner.hide();
        sendMail.hide();
        idVideoView.hide();
        idVideoPreview.hide();
        idVideoSensor.hide();
        ipcamView.hide();
        api.resetMailForm();
        loader.css('background', config.colors.background_countdown);
        loader.css('background-color', config.colors.background_countdown);
    };

    // init
    api.init = function () {
        api.reset();

        initPhotoSwipeFromDOM('#galimages');

        resultPage.hide();
        startPage.addClass('open');
        if (config.preview.asBackground || (config.preview.mode === 'gphoto' && !config.preview.gphoto_bsm)) {
            api.startVideo('preview');
        }

        initRemoteBuzzerFromDOM();
        rotaryController.focusSet('#start');
    };

    api.openNav = function () {
        mySideNav.addClass('sidenav--open');
        rotaryController.focusSet('#mySidenav');
    };

    api.closeNav = function () {
        mySideNav.removeClass('sidenav--open');
    };

    api.toggleNav = function () {
        mySideNav.toggleClass('sidenav--open');

        if (mySideNav.hasClass('sidenav--open')) {
            rotaryController.focusSet('#mySidenav');
        }
    };

    api.startVideo = function (mode) {
        if (config.preview.asBackground) {
            api.stopVideo('preview');
        }

        const dataVideo = {
            play: 'true'
        };

        if (!navigator.mediaDevices) {
            return;
        }

        if (config.preview.mode === 'gphoto') {
            if (!config.preview.gphoto_bsm && mode === 'preview') {
                jQuery
                    .post('api/takeVideo.php', dataVideo)
                    .done(function (result) {
                        photoboothTools.console.log('Start webcam', result);
                        pid = result.pid;
                    })
                    .fail(function (xhr, status, result) {
                        photoboothTools.console.log('Could not start webcam', result);
                    });
            } else if (!config.preview.gphoto_bsm && mode === 'view') {
                const getMedia =
                    navigator.mediaDevices.getUserMedia ||
                    navigator.mediaDevices.webkitGetUserMedia ||
                    navigator.mediaDevices.mozGetUserMedia ||
                    false;

                if (!getMedia) {
                    return;
                }

                if (config.preview.flipHorizontal) {
                    idVideoView.addClass('flip-horizontal');
                    idVideoPreview.addClass('flip-horizontal');
                }

                getMedia
                    .call(navigator.mediaDevices, webcamConstraints)
                    .then(function (stream) {
                        idVideoView.show();
                        videoView.srcObject = stream;
                        api.stream = stream;
                    })
                    .catch(function (error) {
                        photoboothTools.console.log('Could not get user media: ', error);
                    });
            } else {
                jQuery
                    .post('api/takeVideo.php', dataVideo)
                    .done(function (result) {
                        photoboothTools.console.log('Start webcam', result);
                        pid = result.pid;
                        const getMedia =
                            navigator.mediaDevices.getUserMedia ||
                            navigator.mediaDevices.webkitGetUserMedia ||
                            navigator.mediaDevices.mozGetUserMedia ||
                            false;

                        if (!getMedia) {
                            return;
                        }

                        if (config.preview.flipHorizontal) {
                            idVideoView.addClass('flip-horizontal');
                            idVideoPreview.addClass('flip-horizontal');
                        }

                        getMedia
                            .call(navigator.mediaDevices, webcamConstraints)
                            .then(function (stream) {
                                idVideoView.show();
                                videoView.srcObject = stream;
                                api.stream = stream;
                            })
                            .catch(function (error) {
                                photoboothTools.console.log('Could not get user media: ', error);
                            });
                    })
                    .fail(function (xhr, status, result) {
                        photoboothTools.console.log('Could not start webcam', result);
                    });
            }
        } else {
            const getMedia =
                navigator.mediaDevices.getUserMedia ||
                navigator.mediaDevices.webkitGetUserMedia ||
                navigator.mediaDevices.mozGetUserMedia ||
                false;

            if (!getMedia) {
                return;
            }

            if (config.preview.flipHorizontal) {
                idVideoView.addClass('flip-horizontal');
                idVideoPreview.addClass('flip-horizontal');
            }

            getMedia
                .call(navigator.mediaDevices, webcamConstraints)
                .then(function (stream) {
                    if (mode === 'preview') {
                        idVideoPreview.show();
                        videoPreview.srcObject = stream;
                        api.stream = stream;
                        wrapper.css('background-image', 'none');
                        wrapper.css('background-color', 'transparent');
                    } else {
                        idVideoView.show();
                        videoView.srcObject = stream;
                    }
                    api.stream = stream;
                })
                .catch(function (error) {
                    photoboothTools.console.log('Could not get user media: ', error);
                });
        }
    };

    api.stopVideo = function (mode) {
        if (api.stream) {
            const track = api.stream.getTracks()[0];
            track.stop();
            if (mode === 'preview') {
                idVideoPreview.hide();
            } else {
                idVideoView.hide();
            }
        }
    };

    api.stopPreviewVideo = function () {
        if (api.stream) {
            const dataVideo = {
                play: 'false',
                pid: pid
            };

            jQuery
                .post('api/takeVideo.php', dataVideo)
                .done(function (result) {
                    photoboothTools.console.log('Stop webcam', result);
                    const track = api.stream.getTracks()[0];
                    track.stop();
                    idVideoView.hide();
                })
                .fail(function (xhr, status, result) {
                    photoboothTools.console.log('Could not stop webcam', result);
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

    api.shellCommand = function ($mode) {
        command = {
            mode: $mode
        };

        photoboothTools.console.log('Run', $mode);

        jQuery
            .post('api/shellCommand.php', command)
            .done(function (result) {
                photoboothTools.console.log($mode, 'result: ', result);
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log($mode, 'result: ', result);
            });
    };

    api.thrill = function (photoStyle) {
        api.closeNav();
        api.reset();
        api.closeGallery();
        api.showResultInner(false);

        remoteBuzzerClient.inProgress(true);

        takingPic = true;

        if (api.isTimeOutPending()) {
            api.resetTimeOut();
        }

        photoboothTools.console.logDev('Taking photo: ' + takingPic);

        if (config.pre_photo.cmd) {
            api.shellCommand('pre-command');
        }

        if (config.preview.asBackground) {
            wrapper.css('background-color', config.colors.panel);
        }

        if (currentCollageFile && nextCollageNumber) {
            photoStyle = 'collage';
        }

        if (chromaFile) {
            photoStyle = 'chroma';
        }

        if (config.preview.mode === 'device_cam' || config.preview.mode === 'gphoto') {
            api.startVideo('view');
        } else if (config.preview.mode === 'url') {
            ipcamView.show();
            ipcamView.addClass('streaming');
        }

        loader.addClass('open');

        api.startCountdown(nextCollageNumber ? config.collage.cntdwn_time : config.picture.cntdwn_time, counter, () => {
            api.cheese(photoStyle);
        });
    };

    // Cheese
    api.cheese = function (photoStyle) {
        photoboothTools.console.logDev('Photostyle: ' + photoStyle);

        counter.empty();
        cheese.empty();

        if (config.picture.no_cheese) {
            photoboothTools.console.log('Cheese is disabled.');
        } else if (photoStyle === 'photo' || photoStyle === 'chroma') {
            const cheesemsg = photoboothTools.getTranslation('cheese');
            cheese.text(cheesemsg);
        } else {
            const cheesemsg = photoboothTools.getTranslation('cheeseCollage');
            cheese.text(cheesemsg);
            $('<p>')
                .text(`${nextCollageNumber + 1} / ${config.collage.limit}`)
                .appendTo('.cheese');
        }

        if (config.preview.mode === 'gphoto' && !config.picture.no_cheese) {
            api.stopPreviewVideo();
        }

        if (
            config.preview.mode === 'device_cam' &&
            config.preview.camTakesPic &&
            !api.stream &&
            !config.dev.demo_images
        ) {
            photoboothTools.console.log('No preview by device cam available!');

            api.errorPic({
                error: 'No preview by device cam available!'
            });
        } else if (config.picture.no_cheese) {
            api.takePic(photoStyle);
        } else {
            setTimeout(() => {
                api.takePic(photoStyle);
            }, config.picture.cheese_time);
        }
    };

    // take Picture
    api.takePic = function (photoStyle) {
        photoboothTools.console.log('Take Picture:', photoStyle);

        remoteBuzzerClient.inProgress(true);

        if (config.preview.mode === 'device_cam' || config.preview.mode === 'gphoto') {
            if (config.preview.camTakesPic && !config.dev.demo_images) {
                videoSensor.width = videoView.videoWidth;
                videoSensor.height = videoView.videoHeight;
                videoSensor.getContext('2d').drawImage(videoView, 0, 0);
            }
            if (config.preview.mode === 'device_cam') {
                api.stopVideo('view');
            }
        } else if (config.preview.mode === 'url') {
            ipcamView.removeClass('streaming');
            ipcamView.hide();
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

        if (photoStyle === 'chroma') {
            data.file = chromaFile;
        }

        loader.css('background', config.colors.panel);
        loader.css('background-color', config.colors.panel);
        api.callTakePicApi(data);
    };

    api.callTakePicApi = function (data) {
        startTime = new Date().getTime();
        jQuery
            .post('api/takePic.php', data)
            .done(function (result) {
                endTime = new Date().getTime();
                totalTime = endTime - startTime;
                photoboothTools.console.log('took ' + data.style, result);
                photoboothTools.console.logDev('Taking picture took ' + totalTime + 'ms');
                cheese.empty();
                if (config.preview.flipHorizontal) {
                    idVideoView.removeClass('flip-horizontal');
                    idVideoPreview.removeClass('flip-horizontal');
                }

                // reset filter (selection) after picture was taken
                imgFilter = config.filters.defaults;
                $('#mySidenav .activeSidenavBtn').removeClass('activeSidenavBtn');
                $('#' + imgFilter).addClass('activeSidenavBtn');

                if (result.error) {
                    api.errorPic(result);
                } else if (result.success === 'collage') {
                    currentCollageFile = result.file;
                    nextCollageNumber = result.current + 1;

                    spinner.hide();
                    loading.empty();
                    idVideoSensor.hide();

                    let imageUrl = config.foldersRoot.tmp + '/' + result.collage_file;
                    const preloadImage = new Image();
                    const picdate = Date.now;
                    const waitmsg = photoboothTools.getTranslation('wait_message');
                    loading.append($('<p>').text(waitmsg));
                    preloadImage.onload = () => {
                        loaderImage.css({
                            'background-image': `url(${imageUrl}?filter=${imgFilter})`
                        });
                        loaderImage.attr('data-img', picdate);
                    };

                    preloadImage.src = imageUrl;

                    loaderImage.show();

                    photoboothTools.console.logDev(
                        'Taken collage photo number: ' + (result.current + 1) + ' / ' + result.limit
                    );

                    if (config.collage.continuous) {
                        if (result.current + 1 < result.limit) {
                            setTimeout(() => {
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.thrill('collage');
                            }, config.collage.continuous_time * 1000);
                        } else {
                            currentCollageFile = '';
                            nextCollageNumber = 0;
                            setTimeout(() => {
                                loaderImage.css('background-image', 'none');
                                imageUrl = '';
                                loaderImage.css('display', 'none');
                                api.processPic(data.style, result);
                            }, config.collage.continuous_time * 1000);
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
                                    api.thrill('collage');
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
                                    nextCollageNumber = 0;

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
                                api.deleteTmpImage(result.collage_file);
                                nextCollageNumber = result.current;
                                api.thrill('collage');
                            });

                        const abortmsg = photoboothTools.getTranslation('abort');
                        loading
                            .append($('<a class="btn rotaryfocus" style="margin-left:2px" href="#">').text(abortmsg))
                            .click(() => {
                                location.assign('./');
                            });

                        rotaryController.focusSet('.loading.rotarygroup');
                    }
                } else if (result.success === 'chroma') {
                    chromaFile = result.file;
                    api.processPic(data.style, result);
                } else {
                    currentCollageFile = '';
                    nextCollageNumber = 0;

                    api.processPic(data.style, result);
                }
            })
            .fail(function (xhr, status, result) {
                api.errorPic(result);
            });
    };

    // Show error Msg and reset
    api.errorPic = function (data) {
        setTimeout(function () {
            spinner.hide();
            loading.empty();
            cheese.empty();
            idVideoView.hide();
            idVideoSensor.hide();
            loader.addClass('error');
            const errormsg = photoboothTools.getTranslation('error');
            loading.append($('<p>').text(errormsg));
            if (config.dev.error_messages) {
                loading.append($('<p class="text-muted">').text(data.error));
            }
            takingPic = false;
            remoteBuzzerClient.inProgress(false);
            photoboothTools.console.logDev('Taking photo: ' + takingPic);
            if (config.dev.reload_on_error) {
                const reloadmsg = photoboothTools.getTranslation('auto_reload');
                loading.append($('<p>').text(reloadmsg));
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, 5000);
            } else {
                const reloadmsg = photoboothTools.getTranslation('reload');
                loading.append($('<a class="btn" href="./">').text(reloadmsg));
            }
        }, 500);
    };

    api.processPic = function (photoStyle, result) {
        startTime = new Date().getTime();
        const tempImageUrl = config.foldersRoot.tmp + '/' + result.file;

        spinner.show();
        loading.text(
            photoStyle === 'photo' || photoStyle === 'chroma'
                ? photoboothTools.getTranslation('busy')
                : photoboothTools.getTranslation('busyCollage')
        );

        if (photoStyle === 'photo' && config.picture.preview_before_processing) {
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
                    const request = new XMLHttpRequest();
                    photoboothTools.console.log('Sending GET request to: ' + getUrl);
                    request.open('GET', getUrl);
                    request.send();
                }

                if (data.error) {
                    api.errorPic(data);
                } else if (photoStyle === 'chroma') {
                    api.renderChroma(data.file);
                } else {
                    api.renderPic(data.file, data.images);
                }
            },
            error: (jqXHR, textStatus) => {
                photoboothTools.console.log('An error occurred', textStatus);

                api.errorPic({
                    error: 'Request failed: ' + textStatus
                });
            }
        });
    };

    // Render Chromaimage after taking
    api.renderChroma = function (filename) {
        if (config.live_keying.show_all) {
            // Add Image to gallery and slider
            api.addImage(filename);
        }
        const imageUrl = config.live_keying.show_all
            ? config.foldersRoot.images + '/' + filename
            : config.foldersRoot.keying + '/' + filename;
        const preloadImage = new Image();

        preloadImage.onload = function () {
            $('body').attr('data-main-image', filename);
            photoboothTools.console.log(config.foldersRoot.keying + '/' + filename);
            const chromaimage = config.foldersRoot.keying + '/' + filename;

            loader.hide();
            api.chromaimage = filename;
            setMainImage(chromaimage);
        };

        preloadImage.src = imageUrl;

        takingPic = false;
        remoteBuzzerClient.inProgress(false);

        api.resetTimeOut();
        photoboothTools.console.logDev('Taking photo: ' + takingPic);
    };

    // Render Picture after taking
    api.renderPic = function (filename, files) {
        // Add QR Code Image
        const qrHelpText = config.qr.custom_text
            ? config.qr.text
            : photoboothTools.getTranslation('qrHelp') + '</br><b>' + config.webserver.ssid + '</b>';
        photoboothTools.modal.empty(qrCodeModal);
        const body = qrCodeModal.find('.modal__body');
        $('<button>')
            .on('click touchstart', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();

                photoboothTools.modal.close('#qrCode');
            })
            .append($('<i>').addClass('fa fa-times'))
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

        // Add Print Link
        $(document).off('click touchstart', '.printbtn');
        $(document).on('click', '.printbtn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            api.printImage(filename, () => {
                printBtn.blur();
            });
        });

        // If autoprint is activated the picture will immediately printed after taken.
        if (config.print.auto) {
            setTimeout(function () {
                api.printImage(filename);
            }, config.print.auto_delay);
        }

        resultPage
            .find('.deletebtn')
            .off('click')
            .on('click', (ev) => {
                ev.preventDefault();

                const msg = photoboothTools.getTranslation('really_delete_image');
                const really = config.delete.no_request ? true : confirm(filename + ' ' + msg);
                if (really) {
                    files.forEach(function (file, index, array) {
                        photoboothTools.console.logDev('Index:', index);
                        photoboothTools.console.logDev('Array:', array);
                        api.deleteImage(file, (data) => {
                            if (data.success) {
                                photoboothTools.console.log('Deleted ' + file);
                            } else {
                                photoboothTools.console.log('Error while deleting ' + file);
                                if (data.error) {
                                    photoboothTools.console.log(data.error);
                                }
                                setTimeout(function () {
                                    photoboothTools.reloadPage();
                                }, 5000);
                            }
                        });
                    });
                } else {
                    deleteBtn.blur();
                }
                photoboothTools.reloadPage();
            });

        // Add Image to gallery and slider
        api.addImage(filename);

        const imageUrl = config.foldersRoot.images + '/' + filename;

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
            api.shellCommand('post-command');
        }

        takingPic = false;
        remoteBuzzerClient.inProgress(false);

        api.resetTimeOut();

        photoboothTools.console.logDev('Taking photo: ' + takingPic);

        if (config.preview.mode === 'gphoto' && !config.preview.gphoto_bsm) {
            api.startVideo('preview');
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

        bigImg.src = config.foldersRoot.images + '/' + imageName;
        thumbImg.src = config.foldersRoot.thumbs + '/' + imageName;

        function allLoaded() {
            const linkElement = $('<a>').html(thumbImg);

            linkElement.attr('class', 'gallery__img rotaryfocus');
            linkElement.attr('data-size', bigSize);
            linkElement.attr('href', config.foldersRoot.images + '/' + imageName);
            linkElement.attr('data-med', config.foldersRoot.thumbs + '/' + imageName);
            linkElement.attr('data-med-size', thumbSize);

            if (config.gallery.newest_first) {
                linkElement.prependTo(galimages);
            } else {
                linkElement.appendTo(galimages);
            }

            galimages.children().not('a').remove();
        }
    };

    // Open Gallery Overview
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

    // close Gallery Overview and Slideshow if visible
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
        $('#send-mail-form').trigger('reset');
        $('#mail-form-message').html('');
    };

    // Countdown Function
    api.startCountdown = function (start, element, cb) {
        let count = 0;
        let current = start;
        const stop = start > 2 ? start - 2 : start;

        if (config.get_request.countdown) {
            const getMode =
                start === config.picture.cntdwn_time ? config.get_request.picture : config.get_request.collage;
            const getUrl = config.get_request.server + '/' + getMode;
            const request = new XMLHttpRequest();
            photoboothTools.console.log('Sending GET request to: ' + getUrl);
            request.open('GET', getUrl);
            request.send();
        }

        function timerFunction() {
            element.text(Number(current) + Number(config.picture.cntdwn_offset));
            current--;

            element.removeClass('tick');

            if (count < start) {
                window.setTimeout(() => element.addClass('tick'), 50);
                window.setTimeout(timerFunction, 1000);
            } else {
                cb();
            }
            count++;
            if (config.preview.mode === 'gphoto' && config.picture.no_cheese && count === stop) {
                api.stopPreviewVideo();
            }
        }
        timerFunction();
    };

    api.printImage = function (imageSrc, cb) {
        const errormsg = photoboothTools.getTranslation('error');

        if (isPrinting) {
            photoboothTools.console.log('Printing already: ' + isPrinting);
        } else {
            photoboothTools.modal.open('#print_mesg');
            isPrinting = true;

            remoteBuzzerClient.inProgress(true);

            setTimeout(function () {
                $.ajax({
                    method: 'GET',
                    url: 'api/print.php',
                    data: {
                        filename: imageSrc
                    },
                    success: (data) => {
                        photoboothTools.console.log('Picture processed: ', data);

                        if (data.error) {
                            photoboothTools.console.log('An error occurred: ', data.error);
                            printMesg.empty();
                            printMesg.html(
                                '<div class="modal__body"><span style="color:red">' + data.error + '</span></div>'
                            );
                        }

                        setTimeout(function () {
                            photoboothTools.modal.close('#print_mesg');
                            if (data.error) {
                                printMesg.empty();
                                printMesg.html(
                                    '<div class="modal__body"><span>' +
                                        photoboothTools.getTranslation('printing') +
                                        '</span></div>'
                                );
                            }
                            cb();
                            isPrinting = false;
                            remoteBuzzerClient.inProgress(false);
                        }, config.print.time);
                    },
                    error: (jqXHR, textStatus) => {
                        photoboothTools.console.log('An error occurred: ', textStatus);
                        printMesg.empty();
                        printMesg.html(
                            '<div class="modal__body"><span style="color:red">' + errormsg + '</span></div>'
                        );

                        setTimeout(function () {
                            photoboothTools.modal.close('#print_mesg');
                            printMesg.empty();
                            printMesg.html(
                                '<div class="modal__body"><span>' +
                                    photoboothTools.getTranslation('printing') +
                                    '</span></div>'
                            );
                            cb();
                            isPrinting = false;
                            remoteBuzzerClient.inProgress(false);
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
                    photoboothTools.console.log('Error while deleting image');
                }
                cb(data);
            },
            error: (jqXHR, textStatus) => {
                photoboothTools.console.log('Error while deleting image: ', textStatus);
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, 5000);
            }
        });
    };

    api.deleteTmpImage = function (imageName) {
        $.ajax({
            url: 'api/deleteTmpPhoto.php',
            method: 'POST',
            data: {
                file: imageName
            },
            success: (data) => {
                if (data.error) {
                    photoboothTools.console.log('Error while deleting image');
                }
            },
            error: (jqXHR, textStatus) => {
                photoboothTools.console.log('Error while deleting image: ', textStatus);
                setTimeout(function () {
                    photoboothTools.reloadPage();
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
    $('.imageFilter').on('click', function (e) {
        e.preventDefault();
        api.toggleNav();
    });

    $('.sidenav > div').on('click', function () {
        $('.sidenav > div').removeAttr('class');
        $(this).addClass('activeSidenavBtn');

        imgFilter = $(this).attr('id');
        const result = {file: resultPage.attr('data-img')};

        photoboothTools.console.logDev('Applying filter', imgFilter, result);

        api.processPic(imgFilter, result);

        rotaryController.focusSet('#mySidenav');
    });

    // Take Picture Button
    $('.takePic, .newpic').on('click', function (e) {
        e.preventDefault();
        if (config.remotebuzzer.usesoftbtn) {
            remoteBuzzerClient.startPicture();
        } else {
            api.thrill('photo');
        }
        $('.newpic').blur();
    });

    // Take Collage Button
    $('.takeCollage, .newcollage').on('click', function (e) {
        e.preventDefault();

        if (config.remotebuzzer.usesoftbtn) {
            remoteBuzzerClient.startCollage();
        } else {
            api.thrill('collage');
        }

        $('.newcollage').blur();
    });

    $('#mySidenav .closebtn').on('click', function (e) {
        e.preventDefault();

        api.closeNav();
        rotaryController.focusSet('#result');
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

        api.closeGallery();
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
                        message
                            .fadeIn()
                            .html(
                                '<span style="color:green">' + photoboothTools.getTranslation('mailSaved') + '</span>'
                            );
                    } else {
                        message
                            .fadeIn()
                            .html(
                                '<span style="color:green">' + photoboothTools.getTranslation('mailSent') + '</span>'
                            );
                    }
                } else {
                    message.fadeIn().html('<span style="color:red">' + result.error + '</span>');
                }
            },
            error: function () {
                message
                    .fadeIn('fast')
                    .html('<span style="color: red;">' + photoboothTools.getTranslation('mailError') + '</span>');
            }
        });
    });

    $('#send-mail-close').on('click', function () {
        api.resetMailForm();
        $('.send-mail').removeClass('mail-active').fadeOut('fast');
    });

    resultPage.on('click', function () {
        if (!mySideNav.hasClass('sidenav--open')) {
            rotaryController.focusSet('#result');
        }
    });

    // Show QR Code
    $('.qrbtn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        photoboothTools.modal.open('#qrCode');
        rotaryController.focusSet('#qrCode');
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
    triggerPic.on('click', function (e) {
        e.preventDefault();

        if (config.remotebuzzer.usehid) {
            remoteBuzzerClient.startPicture();
        } else {
            api.thrill('photo');
        }

        $('.newpic').blur();
    });

    triggerCollage.on('click', function (e) {
        e.preventDefault();

        if (config.remotebuzzer.usehid) {
            remoteBuzzerClient.startCollage();
        } else {
            api.thrill('collage');
        }

        $('.newcollage').blur();
    });

    $(document).on('keyup', function (ev) {
        if (triggerPic[0] || triggerCollage[0]) {
            if (config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) {
                if (takingPic) {
                    photoboothTools.console.logDev('Taking photo already in progress!');
                } else {
                    $('.closeGallery').trigger('click');
                    if (config.collage.enabled && config.collage.only) {
                        photoboothTools.console.logDev(
                            'Picture key pressed, but only collage allowed. Triggering collage now.'
                        );
                        triggerCollage.trigger('click');
                    } else {
                        triggerPic.trigger('click');
                    }
                }
            }

            if (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) {
                if (takingPic) {
                    photoboothTools.console.logDev('Taking photo already in progress!');
                } else {
                    $('.closeGallery').trigger('click');
                    if (config.collage.enabled) {
                        triggerCollage.trigger('click');
                    } else {
                        photoboothTools.console.logDev(
                            'Collage key pressed. Please enable collage in your config. Triggering photo now.'
                        );
                        triggerPic.trigger('click');
                    }
                }
            }

            if (config.print.from_result && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
                if (isPrinting) {
                    photoboothTools.console.log('Printing already in progress!');
                } else {
                    printBtn.trigger('click');
                    printBtn.blur();
                }
            }
        }
    });

    // clear Timeout
    $(document).on('click', function () {
        if (api.isTimeOutPending()) {
            if (typeof onStandaloneGalleryView !== 'undefined') {
                clearTimeout(timeOut);
                photoboothTools.console.logDev('Standalone Gallery: Timeout for auto reload cleared.');
            } else if (startPage.is(':visible')) {
                clearTimeout(timeOut);
                photoboothTools.console.logDev('Timeout for auto reload cleared.');
            } else {
                // if !startPage.is(':visible')
                api.resetTimeOut();
            }
        }
    });

    // Disable Right-Click
    if (!config.dev.enabled) {
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
