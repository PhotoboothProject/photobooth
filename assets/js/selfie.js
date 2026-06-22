/* eslint n/no-unsupported-features/node-builtins: "off" */
/* global photoboothTools */

$(document).ready(function () {
    const notificationTimeout = config.ui.notification_timeout * 1000;
    const startPage = $('.stage[data-stage="start"]');
    const loader = $('.stage[data-stage="loader"]');
    const loaderImage = loader.find('.stage-image');
    const loaderMessage = loader.find('.stage-message');

    // Create image preview element
    const imgPreview = $('<img>', {
        id: 'selfie-preview',
        alt: 'Image Preview'
    });
    startPage.find('.stage-inner').first().prepend(imgPreview);
    let previewUrl = '';

    const clearUploadHandoff = () => {
        loader.removeClass('showBackgroundImage');
        loaderImage.hide().css('background-image', '');
        loaderMessage.empty();
    };

    const restoreStartStage = () => {
        clearUploadHandoff();
        loader.removeClass('stage--active');
        startPage.addClass('stage--active');
    };

    const showUploadHandoff = () => {
        if (previewUrl) {
            loaderImage.css('background-image', `url(${previewUrl})`).show();
            loader.addClass('showBackgroundImage');
        }

        loaderMessage.html(
            '<i class="' + config.icons.spinner + '"></i><br>' + photoboothTools.getTranslation('busy')
        );
        startPage.removeClass('stage--active');
        loader.addClass('stage--active');
    };

    const toggleButtonDisplay = (elements, visible) => {
        elements.css('display', visible ? 'flex' : 'none');
    };

    // Event listener for file input change to show image preview
    $('#images').on('change', function (event) {
        const output = $('#selfie-preview');
        const file = event.target.files[0];
        if (file) {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            previewUrl = URL.createObjectURL(file);
            // Display the image preview and show the upload button when an image is selected
            output.attr('src', previewUrl).show();
            toggleButtonDisplay($('#selfieSubmitBtn'), true);
            toggleButtonDisplay($('#selfieAbortBtn'), true);
            toggleButtonDisplay($('.take-selfie-btn'), false);
        } else {
            // Hide the preview and upload button if no image is selected
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            output.hide();
            toggleButtonDisplay($('#selfieSubmitBtn'), false);
            toggleButtonDisplay($('#selfieAbortBtn'), false);
            toggleButtonDisplay($('.take-selfie-btn'), true);
            previewUrl = '';
        }
    });

    $('#selfieAbortBtn').on('click', function () {
        photoboothTools.reloadPage();
    });

    $('#selfieSubmitBtn').on('click', function () {
        const formData = new FormData(document.getElementById('selfieForm'));
        const submitButton = $(this);

        showUploadHandoff();
        submitButton.prop('disabled', true);

        $.ajax({
            url: environment.publicFolders.api + '/selfie.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    photoboothTools.overlay.showSuccess(response.message);
                } else {
                    restoreStartStage();
                    photoboothTools.overlay.showError(response.message);
                }
            },
            error: function () {
                restoreStartStage();
                photoboothTools.overlay.showError(photoboothTools.getTranslation('selfie_upload_error'));
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            },
            complete: function () {
                submitButton.prop('disabled', false);
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            }
        });
    });
});
