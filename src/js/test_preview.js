/* globals photoboothTools photoboothPreview */
const photoboothPreviewTest = (function () {
    const CameraDisplayMode = {
        INIT: 1,
        BACKGROUND: 2,
        COUNTDOWN: 3,
        TEST: 3
    };

    const api = {},
        ipcamView = $('#ipcam--view'),
        idVideoView = $('#video--view');

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

    return api;
})();

$(function () {
    photoboothPreviewTest.init();
});
