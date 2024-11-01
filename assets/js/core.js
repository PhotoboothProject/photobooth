/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals initPhotoSwipeFromDOM initRemoteBuzzerFromDOM processChromaImage remoteBuzzerClient rotaryController globalGalleryHandle photoboothTools photoboothPreview */

const photoBooth = (function () {
    // ... (previous code remains unchanged)

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
        
        // Reset preview for each collage photo
        if (api.photoStyle === PhotoStyle.COLLAGE) {
            photoboothPreview.stopPreview();
        }
        
        photoboothPreview.startVideo(CameraDisplayMode.COUNTDOWN, retry, maxGetMediaRetry);

        // ... (rest of the function remains unchanged)
    };

    // ... (rest of the file remains unchanged)

    return api;
})();

$(function () {
    photoBooth.init();
});
