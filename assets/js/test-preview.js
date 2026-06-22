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
        previewNone = $('#preview--none'),
        previewIpcam = $('#preview--ipcam'),
        previewVideo = $('#preview--video'),
        previewFramePicture = $('#previewframe--picture'),
        previewFrameCollage = $('#previewframe--collage'),
        buttonStartPreview = $('[data-command="startPreview"]'),
        buttonStopPreview = $('[data-command="stopPreview"]'),
        buttonShowFramePicture = $('[data-command="showPictureFrame"]'),
        buttonShowFrameCollage = $('[data-command="showCollageFrame"]'),
        buttonHideFrame = $('[data-command="hideFrame"]');

    let pid;

    api.init = function () {
        previewNone.show();
        previewVideo.hide();
        previewIpcam.hide();
        previewFramePicture.hide();
        previewFrameCollage.hide();
        buttonStopPreview.hide();
        buttonHideFrame.hide();
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

    buttonStartPreview.on('click', (event) => {
        event.preventDefault();

        photoboothTools.console.log('Starting preview...');
        buttonStartPreview.hide();
        previewNone.hide();
        if (config.commands.preview && !(config.preview.mode === PreviewMode.DEVICE.valueOf() && config.preview.camTakesPic)) {
            photoboothTools.console.logDev('Running preview cmd (TEST).');
            api.runCmd('start');
        }
        photoboothPreview.startVideo(CameraDisplayMode.TEST);

        setTimeout(() => {
            if (photoboothPreview.hasLiveFrame() || config.preview.mode === PreviewMode.URL.valueOf()) {
                buttonStopPreview.show();
            } else {
                buttonStartPreview.show();
            }
        }, 4000);
    });

    buttonStopPreview.on('click', (event) => {
        event.preventDefault();

        photoboothTools.console.log('Stopping preview...');
        buttonStopPreview.hide();
        previewFrameCollage.hide();
        previewFramePicture.hide();
        previewNone.show();
        photoboothPreview.stopPreview();

        setTimeout(() => {
            if (photoboothPreview.hasLiveFrame()) {
                buttonStopPreview.show();
            } else {
                buttonStartPreview.show();
            }
        }, 4000);
    });

    buttonShowFramePicture.on('click', (event) => {
        event.preventDefault();
        photoboothTools.console.log('Showing picture frame over the preview...');
        previewFramePicture.show();
        previewFrameCollage.hide();
        buttonShowFramePicture.hide();
        buttonShowFrameCollage.hide();
        buttonHideFrame.show();
    });

    buttonShowFrameCollage.on('click', (event) => {
        event.preventDefault();
        photoboothTools.console.log('Showing collage frame over the preview...');
        previewFrameCollage.show();
        previewFramePicture.hide();
        buttonShowFramePicture.hide();
        buttonShowFrameCollage.hide();
        buttonHideFrame.show();
    });

    buttonHideFrame.on('click', (event) => {
        event.preventDefault();
        photoboothTools.console.log('Hiding frames...');
        previewFrameCollage.hide();
        previewFramePicture.hide();
        buttonShowFramePicture.show();
        buttonShowFrameCollage.show();
        buttonHideFrame.hide();
    });

    previewVideo.on('loadedmetadata', (event) => {
        const videoEl = event.target;
        let newWidth = videoEl.offsetWidth;
        let newHeight = videoEl.offsetHeight;
        if (config.preview.style === PreviewStyle.SCALE_DOWN.valueOf()) {
            newWidth = videoEl.videoWidth;
            newHeight = videoEl.videoHeight;
        }
        if (newWidth !== 0 && newHeight !== 0) {
            previewFramePicture.css('width', newWidth);
            previewFramePicture.css('height', newHeight);
            previewFrameCollage.css('width', newWidth);
            previewFrameCollage.css('height', newHeight);
        }
    });

    return api;
})();

$(function () {
    photoboothPreviewTest.init();
});
