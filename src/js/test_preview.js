/* globals photoboothTools photoboothPreview */
const photoboothPreviewTest = (function () {
    const CameraDisplayMode = {
            INIT: 1,
            BACKGROUND: 2,
            COUNTDOWN: 3,
            TEST: 3
        },
        PreviewStyle = {
            FILL: 'fill',
            CONTAIN: 'contain',
            COVER: 'cover',
            NONE: 'none',
            SCALE_DOWN: 'scale-down'
        };

    const api = {},
        ipcamView = $('#ipcam--view'),
        idVideoView = $('#video--view'),
        pictureFrame = $('#picture--frame'),
        collageFrame = $('#collage--frame');

    api.init = function () {
        idVideoView.hide();
        idVideoView.css('z-index', 0);
        ipcamView.hide();
        $('#no_preview').show();
        $('.stopPreview').hide();
        pictureFrame.hide();
        collageFrame.hide();
        $('.hideFrame').hide();
    };

    $('.startPreview').on('click', function (e) {
        e.preventDefault();

        photoboothTools.console.log('Starting preview...');
        $('.startPreview').hide();
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
        photoboothPreview.stopPreview();

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

    idVideoView.on('loadedmetadata', function (ev) {
        const videoEl = ev.target;
        let newWidth = videoEl.offsetWidth;
        let newHeight = videoEl.offsetHeight;
        if (config.preview.style === PreviewStyle.SCALE_DOWN) {
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
