/* eslint n/no-unsupported-features/node-builtins: "off" */

/* globals photoBooth photoboothTools */

function getPreviewUrlWithCacheBusting() {
    const url = getBasePreviewUrl();
    const timestamp = new Date().getTime();

    if (url.includes('?')) {
        return `${url}&t=${timestamp}`;
    }

    return `${url}?t=${timestamp}`;
}

function getBasePreviewUrl() {
    if (!config.preview || !config.preview.url) {
        return '';
    }

    const raw = config.preview.url;
    //remove url("") if present
    const match = raw.match(/^url\((['"]?)(.+?)\1\)$/);

    return match ? match[2] : raw;
}

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
        api = {};

    let pid,
        video,
        loader,
        url,
        pictureFrame,
        collageFrame,
        retryGetMedia = 3,
        screenshotPreviewCommandStarted = false;

    function isDeviceCamScreenshotMode() {
        return config.preview.mode === PreviewMode.DEVICE.valueOf() && config.preview.camTakesPic;
    }

    function resumeVideoElement(cb = null) {
        const videoEl = video && video.get(0);
        const done =
            typeof cb === 'function'
                ? cb
                : function () {
                      return undefined;
                  };

        if (!videoEl) {
            done();
            return;
        }

        const playPromise = videoEl.play();
        if (playPromise && typeof playPromise.finally === 'function') {
            playPromise
                .catch(function (error) {
                    photoboothTools.console.log('ERROR: Preview: Could not resume video playback: ', error);
                })
                .finally(done);
            return;
        }

        done();
    }

    function maybeStartPreviewCommandForScreenshotWarmup() {
        if (
            !isDeviceCamScreenshotMode() ||
            !config.commands.preview ||
            screenshotPreviewCommandStarted ||
            api.hasLiveFrame()
        ) {
            return;
        }

        photoboothTools.console.logDev('Preview: Running preview cmd (SCREENSHOT warmup).');
        screenshotPreviewCommandStarted = true;
        api.runCmd('start');
    }

    function onWarmDeviceCamReady(mode = null) {
        if (mode === null) {
            api.hidePreviewDisplay();
            return;
        }

        api.changeVideoMode(mode);
    }

    api.hasLiveFrame = function () {
        const videoEl = video && video.get(0);

        return !!(
            api.stream &&
            api.stream.active &&
            videoEl &&
            videoEl.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA &&
            videoEl.videoWidth > 0 &&
            videoEl.videoHeight > 0
        );
    };

    api.changeVideoMode = function (mode) {
        photoboothTools.console.logDev('Preview: Changing video mode: ' + mode);
        if (mode !== CameraDisplayMode.BACKGROUND) {
            loader.css('--stage-background', 'transparent');
        }
        video.show();
    };

    api.initializeMedia = function (
        cb = () => {
            return;
        },
        retry = 0
    ) {
        photoboothTools.console.logDev('Preview: Trying to initialize media...');
        if (
            !navigator.mediaDevices ||
            config.preview.mode === PreviewMode.NONE.valueOf() ||
            config.preview.mode === PreviewMode.URL.valueOf()
        ) {
            photoboothTools.console.logDev('Preview: No preview from device cam or no webcam available!');

            return;
        }
        const getMedia =
            navigator.mediaDevices.getUserMedia ||
            navigator.mediaDevices.webkitGetUserMedia ||
            navigator.mediaDevices.mozGetUserMedia ||
            false;

        if (!getMedia) {
            photoboothTools.console.logDev('Preview: Could not get media!');

            return;
        }

        const videoWidthDefault = config.preview.videoWidth;
        const videoHeightDefault = config.preview.videoHeight;
        let videoWidth = videoWidthDefault;
        let videoHeight = videoHeightDefault;

        // Support diffrent preview video sizes for collage mode
        if (photoBooth.photoStyle === 'collage') {
            if (config.preview.videoWidth_collage > 0) {
                videoWidth = config.preview.videoWidth_collage;
            }
            if (config.preview.videoHeight_collage > 0) {
                videoHeight = config.preview.videoHeight_collage;
            }
        }

        const webcamConstraints = {
            audio: false,
            video: {
                width: videoWidth,
                height: videoHeight,
                facingMode: config.preview.camera_mode
            }
        };

        getMedia
            .call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                photoboothTools.console.logDev('Preview: getMedia done!');
                api.stream = stream;
                const videoEl = video.get(0);
                videoEl.srcObject = stream;
                resumeVideoElement(cb);
            })
            .catch(function (error) {
                photoboothTools.console.log('ERROR: Preview: Could not get user media: ', error);
                if (retry < retryGetMedia) {
                    photoboothTools.console.logDev(
                        'Preview: Retrying to get user media. Retry ' + retry + ' / ' + retryGetMedia
                    );
                    retry += 1;
                    setTimeout(function () {
                        api.initializeMedia(cb, retry);
                    }, 1000);
                } else {
                    photoboothTools.console.logDev(
                        'ERROR: Preview: Unable to get user media. Failed retries: ' + retry
                    );
                }
            });
    };

    api.ensureWarmDeviceCamStream = function (mode = null) {
        maybeStartPreviewCommandForScreenshotWarmup();

        if (api.hasLiveFrame()) {
            onWarmDeviceCamReady(mode);
            return;
        }

        if (api.stream && api.stream.active) {
            resumeVideoElement(function () {
                if (api.hasLiveFrame()) {
                    onWarmDeviceCamReady(mode);
                    return;
                }

                api.initializeMedia(function () {
                    onWarmDeviceCamReady(mode);
                });
            });
            return;
        }

        api.initializeMedia(function () {
            onWarmDeviceCamReady(mode);
        });
    };

    api.getAndDisplayMedia = function (mode) {
        if (isDeviceCamScreenshotMode()) {
            api.ensureWarmDeviceCamStream(mode);
            return;
        }

        if (api.hasLiveFrame()) {
            api.changeVideoMode(mode);
            return;
        }

        if (api.stream && api.stream.active) {
            resumeVideoElement(function () {
                if (api.hasLiveFrame()) {
                    api.changeVideoMode(mode);
                    return;
                }

                api.initializeMedia(() => {
                    api.changeVideoMode(mode);
                });
            });
            return;
        }

        api.initializeMedia(() => {
            api.changeVideoMode(mode);
        });
    };

    api.warmBackground = function () {
        if (config.preview.mode !== PreviewMode.DEVICE.valueOf()) {
            return;
        }

        if (isDeviceCamScreenshotMode()) {
            photoboothTools.console.logDev('Preview: Keep screenshot mode warm in background.');
            api.ensureWarmDeviceCamStream();
            return;
        }

        if (config.commands.preview && !config.preview.bsm) {
            photoboothTools.console.logDev('Preview: Running preview cmd (BACKGROUND warmup).');
            api.runCmd('start');
        }

        if (api.hasLiveFrame()) {
            return;
        }

        photoboothTools.console.logDev('Preview: Warm hidden device cam stream.');

        if (api.stream && api.stream.active) {
            resumeVideoElement();
            return;
        }

        api.initializeMedia();
    };

    api.runCmd = function (mode) {
        const dataVideo = {
            play: mode,
            pid: pid
        };

        photoboothTools
            .ajaxWithCsrf({
                url: 'api/previewCamera.php',
                method: 'POST',
                data: dataVideo
            })
            .done(function (result) {
                photoboothTools.console.log('Preview: ' + dataVideo.play + ' webcam successfully.');
                pid = result.pid;
            })
            // eslint-disable-next-line no-unused-vars
            .fail(function (xhr, status, result) {
                if (photoboothTools.isCsrfErrorResponse(xhr)) {
                    photoboothTools.handleCsrfMismatch('api/previewCamera.php');
                    return;
                }
                photoboothTools.console.log('ERROR: Preview: Failed to ' + dataVideo.play + ' webcam!');
            });
    };

    api.startVideo = function (mode, retry = 0, maxGetMediaRetry = 3) {
        retryGetMedia = maxGetMediaRetry;
        photoboothTools.console.log('Preview: startVideo mode: ' + mode);
        if (config.preview.mode !== PreviewMode.URL.valueOf()) {
            if (!navigator.mediaDevices || config.preview.mode === PreviewMode.NONE.valueOf()) {
                return;
            }
        }

        switch (mode) {
            case CameraDisplayMode.INIT:
                if (isDeviceCamScreenshotMode()) {
                    photoboothTools.console.logDev('Preview: Ensure warm screenshot mode (INIT).');
                    api.ensureWarmDeviceCamStream();
                    break;
                }
                photoboothTools.console.logDev('Preview: Running preview cmd (INIT).');
                api.runCmd('start');
                break;
            case CameraDisplayMode.BACKGROUND:
                if (isDeviceCamScreenshotMode()) {
                    photoboothTools.console.logDev('Preview: Show warm screenshot mode as background.');
                    api.ensureWarmDeviceCamStream(CameraDisplayMode.BACKGROUND);
                    break;
                }
                if (
                    config.preview.mode === PreviewMode.DEVICE.valueOf() &&
                    config.commands.preview &&
                    !config.preview.bsm
                ) {
                    photoboothTools.console.logDev('Preview: Running preview cmd (BACKGROUND).');
                    api.runCmd('start');
                }
                api.getAndDisplayMedia(CameraDisplayMode.BACKGROUND);
                break;
            case CameraDisplayMode.COUNTDOWN:
                if (isDeviceCamScreenshotMode()) {
                    photoboothTools.console.logDev('Preview: Use warm screenshot mode at countdown.');
                    api.ensureWarmDeviceCamStream(CameraDisplayMode.COUNTDOWN);
                    break;
                }
                if (config.commands.preview) {
                    if (
                        config.preview.bsm ||
                        (!config.preview.bsm && retry > 0) ||
                        (typeof photoBooth !== 'undefined' && photoBooth.nextCollageNumber > 0)
                    ) {
                        photoboothTools.console.logDev('Preview: Running preview cmd (COUNTDOWN).');
                        api.runCmd('start');
                    }
                }
                if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview at countdown from device cam.');
                    api.getAndDisplayMedia(CameraDisplayMode.COUNTDOWN);
                } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview at countdown from URL.');
                    setTimeout(function () {
                        url.css('background-image', 'url("' + getPreviewUrlWithCacheBusting() + '")');
                        url.show();
                        url.addClass('streaming');
                    }, config.preview.url_delay);
                }
                break;
            case CameraDisplayMode.TEST:
                if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview from device cam.');
                    if (isDeviceCamScreenshotMode()) {
                        api.ensureWarmDeviceCamStream(CameraDisplayMode.TEST);
                    } else {
                        api.getAndDisplayMedia(CameraDisplayMode.TEST);
                    }
                } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview from URL.');
                    setTimeout(function () {
                        url.css('background-image', 'url("' + getPreviewUrlWithCacheBusting() + '")');
                        url.show();
                        url.addClass('streaming');
                    }, config.preview.url_delay);
                }
                break;
            default:
                photoboothTools.console.log('ERROR: Preview: Call for unexpected video mode: ' + mode);
                break;
        }
    };

    api.hidePreviewDisplay = function () {
        loader.css('--stage-background', 'var(--background-countdown-color)');
        url.removeClass('streaming');
        url.hide();
        url.css('background-image', 'none');
        video.hide();
        pictureFrame.hide();
        collageFrame.hide();
    };

    api.teardownPreview = function () {
        api.hidePreviewDisplay();

        if (api.stream) {
            const tracks = api.stream.getTracks();
            tracks.forEach((track) => track.stop());
            api.stream = null;
        }

        const videoEl = video && video.get(0);
        if (videoEl) {
            videoEl.srcObject = null;
        }

        screenshotPreviewCommandStarted = false;
    };

    api.stopPreview = function () {
        if (config.commands.preview_kill) {
            api.runCmd('stop');
        }

        if (config.preview.mode === PreviewMode.DEVICE.valueOf() || config.preview.mode === PreviewMode.URL.valueOf()) {
            api.teardownPreview();
        }
    };

    api.stopVideo = function () {
        api.teardownPreview();
    };

    api.setElements = () => {
        video = $('#preview--video');
        loader = $('.stage[data-stage="loader"]');
        url = $('#preview--ipcam');
        pictureFrame = $('#previewframe--picture');
        collageFrame = $('#previewframe--collage');
    };

    api.init = function () {
        api.setElements();

        window.addEventListener('pagehide', function () {
            api.stopPreview();
        });
    };

    return api;
})();

$(function () {
    photoboothPreview.init();
    photoboothTools.console.log('Preview: Preview functions available.');
});
