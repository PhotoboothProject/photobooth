/* globals photoBooth photoboothTools */
const photoboothPreview = (function () {
    // vars
    const CameraDisplayMode = {
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
        webcamConstraints = {
            audio: false,
            video: {
                width: config.preview.videoWidth,
                height: config.preview.videoHeight,
                facingMode: config.preview.camera_mode
            }
        },
        api = {};

    let pid, video, loader, wrapper, url;

    api.changeVideoMode = function (mode) {
        if (mode === CameraDisplayMode.BACKGROUND) {
            video.css('z-index', 0);
            wrapper.css('background-image', 'none');
            wrapper.css('background-color', 'transparent');
        } else {
            wrapper.css('background-color', config.colors.panel);
            loader.css('background-color', 'transparent');
            video.css('z-index', 99);
        }
        video.show();
    };

    api.initializeMedia = function (cb = () => {}, retry = 0) {
        if (
            !navigator.mediaDevices ||
            config.preview.mode === PreviewMode.NONE.valueOf() ||
            config.preview.mode === PreviewMode.URL.valueOf()
        ) {
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

        getMedia
            .call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                api.stream = stream;
                video.get(0).srcObject = stream;
                cb();
            })
            .catch(function (error) {
                photoboothTools.console.log('Could not get user media: ', error);
                if (retry < 3) {
                    photoboothTools.console.logDev('Getting user media failed. Retrying. Retry: ' + retry);
                    retry += 1;
                    setTimeout(function () {
                        api.initializeMedia(cb, retry);
                    }, 1000);
                } else {
                    photoboothTools.console.logDev('Unable to get user media. Failed retries: ' + retry);
                }
            });
    };

    api.getAndDisplayMedia = function (mode) {
        if (api.stream) {
            api.changeVideoMode(mode);
        } else {
            api.initializeMedia(() => {
                api.changeVideoMode(mode);
            });
        }
    };

    api.startWebcam = function () {
        const dataVideo = {
            play: 'true'
        };

        jQuery
            .post('api/takeVideo.php', dataVideo)
            .done(function (result) {
                photoboothTools.console.log('Start webcam', result);
                pid = result.pid;
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Could not start webcam', result);
            });
    };

    api.startVideo = function (mode, retry = 0) {
        if (config.preview.mode !== PreviewMode.URL.valueOf()) {
            if (!navigator.mediaDevices || config.preview.mode === PreviewMode.NONE.valueOf()) {
                return;
            }
        }

        switch (mode) {
            case CameraDisplayMode.INIT:
                api.startWebcam();
                break;
            case CameraDisplayMode.BACKGROUND:
                if (config.preview.mode === PreviewMode.DEVICE.valueOf() && config.preview.cmd && !config.preview.bsm) {
                    api.startWebcam();
                }
                api.getAndDisplayMedia(CameraDisplayMode.BACKGROUND);
                break;
            case CameraDisplayMode.COUNTDOWN:
                switch (config.preview.mode) {
                    case PreviewMode.DEVICE.valueOf():
                        photoboothTools.console.logDev('Preview at countdown from device cam.');
                        if (
                            config.preview.cmd &&
                            (config.preview.bsm ||
                                (!config.preview.bsm && retry > 0) ||
                                photoBooth.nextCollageNumber > 0)
                        ) {
                            api.startWebcam();
                        }
                        api.getAndDisplayMedia(CameraDisplayMode.COUNTDOWN);
                        break;
                    case PreviewMode.URL.valueOf():
                        photoboothTools.console.logDev('Preview at countdown from URL.');
                        url.show();
                        url.addClass('streaming');
                        break;
                    default:
                        photoboothTools.console.log('Call for unexpected preview mode.');
                        break;
                }
                break;
            case CameraDisplayMode.Test:
                switch (config.preview.mode) {
                    case PreviewMode.DEVICE.valueOf():
                        photoboothTools.console.logDev('Preview from device cam.');
                        if (config.preview.cmd) {
                            api.startWebcam();
                        }
                        api.getAndDisplayMedia(CameraDisplayMode.Test);
                        break;
                    case PreviewMode.URL.valueOf():
                        photoboothTools.console.logDev('Preview from URL.');
                        url.show();
                        url.addClass('streaming');
                        break;
                    default:
                        photoboothTools.console.log('Call for unexpected preview mode.');
                        break;
                }
                break;
            default:
                photoboothTools.console.log('Call for unexpected video mode.');
                break;
        }
    };

    api.stopPreview = function () {
        if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
            if (config.preview.killcmd) {
                api.stopPreviewVideo();
            } else {
                api.stopVideo();
            }
        } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
            url.removeClass('streaming');
            url.hide();
        }
    };

    api.stopVideo = function () {
        wrapper.css('background-color', config.colors.panel);
        loader.css('background', config.colors.panel);
        loader.css('background-color', config.colors.panel);
        if (api.stream) {
            api.stream.getTracks()[0].stop();
            api.stream = null;
        }
        video.hide();
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
                    api.stopVideo();
                })
                .fail(function (xhr, status, result) {
                    photoboothTools.console.log('Could not stop webcam', result);
                });
        }
    };

    api.setElements = function (
        videoSelector = '#video--view',
        loaderSelector = '#loader',
        wrapperSelector = '#wrapper',
        urlSelector = '#ipcam--view'
    ) {
        video = $(videoSelector);
        loader = $(loaderSelector);
        wrapper = $(wrapperSelector);
        url = $(urlSelector);
    };

    api.init = function () {
        api.setElements();
    };

    return api;
})();

$(function () {
    photoboothPreview.init();
    photoboothTools.console.log('Preview functions available.');
});
