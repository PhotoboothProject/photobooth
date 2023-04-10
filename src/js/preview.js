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

    let pid, video, loader, wrapper, url, pictureFrame, collageFrame;

    api.changeVideoMode = function (mode) {
        photoboothTools.console.logDev('Changing video mode: ' + mode);
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
        photoboothTools.console.logDev('Trying to initialize media');
        if (
            !navigator.mediaDevices ||
            config.preview.mode === PreviewMode.NONE.valueOf() ||
            config.preview.mode === PreviewMode.URL.valueOf()
        ) {
            photoboothTools.console.logDev('No preview from device cam or no webcam available');

            return;
        }
        const getMedia =
            navigator.mediaDevices.getUserMedia ||
            navigator.mediaDevices.webkitGetUserMedia ||
            navigator.mediaDevices.mozGetUserMedia ||
            false;

        if (!getMedia) {
            photoboothTools.console.logDev('Could not get media!');

            return;
        }

        getMedia
            .call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                photoboothTools.console.logDev('getMedia done!');
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

    api.runCmd = function (mode) {
        const dataVideo = {
            play: mode,
            pid: pid
        };

        jQuery
            .post('api/previewCamera.php', dataVideo)
            .done(function (result) {
                photoboothTools.console.log(dataVideo.play + ' webcam successfully.');
                pid = result.pid;
            })
            // eslint-disable-next-line no-unused-vars
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Failed to ' + dataVideo.play + ' webcam!');
            });
    };

    api.startVideo = function (mode, retry = 0) {
        photoboothTools.console.log('startVideo mode: ' + mode);
        if (config.preview.mode !== PreviewMode.URL.valueOf()) {
            if (!navigator.mediaDevices || config.preview.mode === PreviewMode.NONE.valueOf()) {
                return;
            }
        }

        switch (mode) {
            case CameraDisplayMode.INIT:
                photoboothTools.console.logDev('Running preview cmd (INIT).');
                api.runCmd('start');
                break;
            case CameraDisplayMode.BACKGROUND:
                if (config.preview.mode === PreviewMode.DEVICE.valueOf() && config.preview.cmd && !config.preview.bsm) {
                    photoboothTools.console.logDev('Running preview cmd (BACKGROUND).');
                    api.runCmd('start');
                }
                api.getAndDisplayMedia(CameraDisplayMode.BACKGROUND);
                break;
            case CameraDisplayMode.COUNTDOWN:
                if (config.preview.cmd) {
                    if (
                        config.preview.bsm ||
                        (!config.preview.bsm && retry > 0) ||
                        (typeof photoBooth !== 'undefined' && photoBooth.nextCollageNumber > 0)
                    ) {
                        photoboothTools.console.logDev('Running preview cmd (COUNTDOWN).');
                        api.runCmd('start');
                    }
                }
                switch (config.preview.mode) {
                    case PreviewMode.DEVICE.valueOf():
                        photoboothTools.console.logDev('Preview at countdown from device cam.');
                        api.getAndDisplayMedia(CameraDisplayMode.COUNTDOWN);
                        break;
                    case PreviewMode.URL.valueOf():
                        photoboothTools.console.logDev('Preview at countdown from URL.');
                        setTimeout(function () {
                            url.show();
                            url.addClass('streaming');
                        }, config.preview.url_delay);
                        break;
                    default:
                        photoboothTools.console.log('Call for unexpected preview mode.');
                        break;
                }
                break;
            case CameraDisplayMode.Test:
                if (config.preview.cmd) {
                    photoboothTools.console.logDev('Running preview cmd (TEST).');
                    api.runCmd('start');
                }
                switch (config.preview.mode) {
                    case PreviewMode.DEVICE.valueOf():
                        photoboothTools.console.logDev('Preview from device cam.');
                        api.getAndDisplayMedia(CameraDisplayMode.Test);
                        break;
                    case PreviewMode.URL.valueOf():
                        photoboothTools.console.logDev('Preview from URL.');
                        setTimeout(function () {
                            url.show();
                            url.addClass('streaming');
                        }, config.preview.url_delay);
                        break;
                    default:
                        photoboothTools.console.log('Call for unexpected preview mode.');
                        break;
                }
                break;
            default:
                photoboothTools.console.log('Call for unexpected video mode: ' + mode);
                break;
        }
    };

    api.stopPreview = function () {
        if (config.preview.killcmd) {
            api.runCmd('stop');
        }
        if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
            setTimeout(function () {
                api.stopVideo();
            }, config.picture.cntdwn_offset * 1000);
        } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
            setTimeout(function () {
                url.removeClass('streaming');
                url.hide();
            }, config.picture.cntdwn_offset * 1000);
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
        pictureFrame.hide();
        collageFrame.hide();
    };

    api.setElements = function (
        videoSelector = '#video--view',
        loaderSelector = '#loader',
        wrapperSelector = '#wrapper',
        urlSelector = '#ipcam--view',
        pictureFrameSelector = '#picture--frame',
        collageFrameSelector = '#collage--frame'
    ) {
        video = $(videoSelector);
        loader = $(loaderSelector);
        wrapper = $(wrapperSelector);
        url = $(urlSelector);
        pictureFrame = $(pictureFrameSelector);
        collageFrame = $(collageFrameSelector);
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
