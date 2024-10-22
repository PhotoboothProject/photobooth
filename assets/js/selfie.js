/* eslint n/no-unsupported-features/node-builtins: "off" */
/* global photoboothTools */

$(document).ready(function () {
    const notificationTimeout = config.ui.notification_timeout * 1000;

    // Create image preview element
    const imgPreview = $('<img>', {
        id: 'selfie-preview',
        alt: 'Image Preview'
    });
    $('.stage-inner').prepend(imgPreview);

    // Event listener for file input change to show image preview
    $('#images').on('change', function (event) {
        const output = $('#selfie-preview');
        const file = event.target.files[0];
        if (file) {
            // Display the image preview and show the upload button when an image is selected
            output.attr('src', URL.createObjectURL(file)).show();
            $('#selfieSubmitBtn').show();
            $('#selfieAbortBtn').show();
            $('.take-selfie-btn').hide();
        } else {
            // Hide the preview and upload button if no image is selected
            output.hide();
            $('#selfieSubmitBtn').hide();
        }
        output.on('load', function () {
            URL.revokeObjectURL(output.attr('src')); // Free up memory
        });
    });

    $('#selfieAbortBtn').on('click', function () {
        photoboothTools.reloadPage();
    });

    $('#selfieSubmitBtn').on('click', function () {
        const formData = new FormData(document.getElementById('selfieForm'));

        photoboothTools.overlay.showWaiting(photoboothTools.getTranslation('wait_message'));
        $(this).prop('disabled', true);

        $.ajax({
            url: environment.publicFolders.api + '/selfie.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                photoboothTools.overlay.close();
                if (response.success) {
                    photoboothTools.overlay.showSuccess(response.message);
                } else {
                    photoboothTools.overlay.showError(response.message);
                }
            },
            error: function () {
                photoboothTools.overlay.showError('An error occurred while uploading the selfie.');
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            },
            complete: function () {
                $(this).prop('disabled', false);
                setTimeout(function () {
                    photoboothTools.reloadPage();
                }, notificationTimeout);
            }
        });
    });
});
