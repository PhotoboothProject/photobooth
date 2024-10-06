/* eslint n/no-unsupported-features/node-builtins: "off" */
/* global photoboothTools */

$(document).ready(function () {
    const notificationTimeout = config.ui.notification_timeout * 1000;

    // Create image preview element
    const imgPreview = $('<img>', {
        id: 'output',
        alt: 'Image Preview',
        style: 'display: none; max-width: 40vw; max-height: 40vh; margin: 15px auto;'
    });
    $('.buttonbar').prepend(imgPreview);

    // Event listener for file input change to show image preview
    $('#images').on('change', function (event) {
        const output = $('#output');
        const file = event.target.files[0];
        if (file) {
            // Display the image preview and show the upload button when an image is selected
            output.attr('src', URL.createObjectURL(file)).show();
            $('#submitBtn').show();
            $('.take-selfie-btn').hide();
        } else {
            // Hide the preview and upload button if no image is selected
            output.hide();
            $('#submitBtn').hide();
        }
        output.on('load', function () {
            URL.revokeObjectURL(output.attr('src')); // Free up memory
        });
    });

    $('#submitBtn').on('click', function () {
        const formData = new FormData(document.getElementById('selfieForm'));

        $(this).prop('disabled', true);

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
