/* globals photoboothTools */
const photoboothPreviewTest = (function () {
    const PreviewMode = {
        NONE: 'none',
        DEVICE: 'device_cam',
        URL: 'url'
    };

    const api = {},
        ipcamView = $('#ipcam--view'),
        idVideoView = $('#video--view'),
        webcamConstraints = {
            audio: false,
            video: {
                width: config.preview.videoWidth,
                height: config.preview.videoHeight,
                facingMode: config.preview.camera_mode
            }
        },
        videoView = idVideoView.get(0);

    let pid;

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
                videoView.srcObject = stream;
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

    api.startVideo = function () {
        if (config.preview.mode !== PreviewMode.URL.valueOf()) {
            if (!navigator.mediaDevices || config.preview.mode === PreviewMode.NONE.valueOf()) {
                return;
            }
        }
        switch (config.preview.mode) {
            case PreviewMode.NONE.valueOf():
                photoboothTools.console.logDev('Preview at countdown disabled.');
                break;
            case PreviewMode.DEVICE.valueOf():
                photoboothTools.console.logDev('Preview at countdown from device cam.');
                if (config.preview.cmd) {
                    api.startWebcam();
                }
                api.initializeMedia();
                idVideoView.css('z-index', 99);
                idVideoView.show();
                break;
            case PreviewMode.URL.valueOf():
                photoboothTools.console.logDev('Preview at countdown from URL.');
                ipcamView.show();
                ipcamView.addClass('streaming');
                break;
            default:
                photoboothTools.console.log('Call for unexpected preview mode.');
                break;
        }
    };

    api.stopVideo = function () {
        if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
            if (config.preview.killcmd) {
                api.stopPreviewVideo();
            }

            if (api.stream) {
                api.stream.getTracks()[0].stop();
                api.stream = null;
            }
            idVideoView.css('z-index', 0);
            idVideoView.hide();
        } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
            ipcamView.removeClass('streaming');
            ipcamView.hide();
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
                    api.stopVideo();
                })
                .fail(function (xhr, status, result) {
                    photoboothTools.console.log('Could not stop webcam', result);
                });
        }
    };

    api.init = function () {
        idVideoView.hide();
        idVideoView.css('z-index', 0);
        ipcamView.hide();
        $('#no_preview').show();
        $('.stopPreview').hide();
    };

    $('.startPreview').on('click', function (e) {
        e.preventDefault();

        photoboothTools.console.log('Starting preview...');
        $('.startPreview').hide();
        api.startVideo();

        setTimeout(() => {
            if (api.stream) {
                $('#no_preview').hide();
                $('.stopPreview').show();
            } else {
                $('.startPreview').show();
            }
        }, 4000);
    });

    $('.stopPreview').on('click', function (e) {
        e.preventDefault();

        photoboothTools.console.log('Stopping preview...');
        $('.stopPreview').hide();
        api.stopVideo();

        setTimeout(() => {
            if (api.stream) {
                $('.stopPreview').show();
            } else {
                $('#no_preview').show();
                $('.startPreview').show();
            }
        }, 4000);
    });

    return api;
})();

$(function () {
    photoboothPreviewTest.init();
});
