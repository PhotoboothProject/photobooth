/* globals photoboothTools photoboothPreview */
const photoboothPreviewTest = (function () {
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
        PreviewStyle = {
            FILL: 'fill',
            CONTAIN: 'contain',
            COVER: 'cover',
            NONE: 'none',
            SCALE_DOWN: 'scale-down'
        };

    const api = {},
        url = $('#ipcam--view'),
        video = $('#video--view'),
        pictureFrame = $('#picture--frame'),
        collageFrame = $('#collage--frame');

    let pid;

    api.init = function () {
        video.hide();
        video.css('z-index', 0);
        url.hide();
        $('#no_preview').show();
        $('.stopPreview').hide();
        pictureFrame.hide();
        collageFrame.hide();
        $('.hideFrame').hide();
    };

    api.runCmd = function (mode) {
        const dataVideo = {
            play: mode,
            pid: pid
        };

        jQuery
            .post('../api/previewCamera.php', dataVideo)
            .done(function (result) {
                photoboothTools.console.log(dataVideo.play + ' webcam successfully.');
                pid = result.pid;
            })
            // eslint-disable-next-line no-unused-vars
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Failed to ' + dataVideo.play + ' webcam!');
            });
    };

    $('.startPreview').on('click', function (e) {
        e.preventDefault();

        photoboothTools.console.log('Starting preview...');
        $('.startPreview').hide();
        if (config.preview.cmd) {
            photoboothTools.console.logDev('Running preview cmd (TEST).');
            api.runCmd('start');
        }
        photoboothPreview.startVideo(CameraDisplayMode.TEST);

        setTimeout(() => {
            if (photoboothPreview.stream) {
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
        collageFrame.hide();
        pictureFrame.hide();
        if (config.preview.killcmd) {
            api.runCmd('stop');
        }
        if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
            photoboothPreview.stopVideo();
        } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
            url.removeClass('streaming');
            url.hide();
        }

        setTimeout(() => {
            if (photoboothPreview.stream) {
                $('.stopPreview').show();
            } else {
                $('#no_preview').show();
                $('.startPreview').show();
            }
        }, 4000);
    });

    $('.showPictureFrame').on('click', function (e) {
        e.preventDefault();
        photoboothTools.console.log('Showing picture frame over the preview...');
        pictureFrame.show();
        collageFrame.hide();
        $('.hideFrame').show();
    });

    $('.showCollageFrame').on('click', function (e) {
        e.preventDefault();
        photoboothTools.console.log('Showing collage frame over the preview...');
        collageFrame.show();
        pictureFrame.hide();
        $('.hideFrame').show();
    });

    $('.hideFrame').on('click', function (e) {
        e.preventDefault();
        photoboothTools.console.log('Hiding frames...');
        collageFrame.hide();
        pictureFrame.hide();
        $('.hideFrame').hide();
    });

    video.on('loadedmetadata', function (ev) {
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
    photoboothPreviewTest.init();
});
