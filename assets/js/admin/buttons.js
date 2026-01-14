/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools shellCommand csrf */

/**
 * Saves the admin settings via the API.
 * Displays a loader during the saving process.
 *
 * @param {object} [options] - Configuration options for the save operation.
 * @param {boolean} [options.reloadOnSuccess=false] - If true, reloads the page on successful save.
 * @param {boolean} [options.reloadOnError=true] - If true, reloads the page on save failure.
 * @returns {Promise<Object>} A Promise that resolves with the API response data on success,
 *                            or rejects with an Error object on failure.
 */
function saveAdminSettings(options = {}) {
    if (!hasPendingAdminChanges()) {
        photoboothTools.console.logDev('No changes detected in admin settings. Save operation skipped.');
        return new Promise(() => {}); // Exit if no changes
    }

    const defaultOptions = {
        reloadOnSuccess: true,
        reloadOnError: false
    };
    const currentOptions = { ...defaultOptions, ...options }; // Merge default with provided options

    // Show loader
    $('.pageLoader').addClass('isActive');
    $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

    const data = new FormData(document.querySelector('form'));
    data.append('type', 'config');
    if (typeof csrf !== 'undefined') {
        data.append(csrf.key, csrf.token);
    }

    return fetch('../api/admin.php', {
        method: 'POST',
        body: data
    })
        .then((response) => {
            // Hide loader after the fetch request completes, regardless of success or failure
            $('.pageLoader').removeClass('isActive');

            if (!response.ok) {
                // If the HTTP response is not OK (e.g., 404, 500), throw an error
                return response
                    .json()
                    .then((errorData) => {
                        const errorMessage = errorData.message || `HTTP error! status: ${response.status}`;
                        photoboothTools.console.logDev(errorMessage);
                        throw new Error(errorMessage);
                    })
                    .catch(() => {
                        // Handle cases where response is not JSON or parsing fails
                        const errorMessage = `HTTP error! status: ${response.status}`;
                        photoboothTools.console.logDev(errorMessage);
                        throw new Error(errorMessage);
                    });
            }
            return response.json(); // Parse JSON from the response
        })
        .then((responseData) => {
            // Process the JSON response data
            if (responseData.status === 'success') {
                // After successful save, if the form was dirty, reset it to clean state.
                $('#save-admin-btn').removeClass('isDirty');
                // Also, update the initial serialized state to the newly saved state
                // to correctly detect future changes without a full page reload.
                $('form').data('initialSerialized', $('form').serialize());
                if (currentOptions.reloadOnSuccess) {
                    window.location.reload();
                    // We return a pending Promise here, as reload will prevent subsequent .then() from running
                    // eslint-disable-next-line no-empty-function
                    return new Promise(() => {});
                }
                return responseData; // Saving successful, resolve with response data
            } else {
                // API returned a non-success status, but HTTP fetch was successful
                const errorMessage = responseData.message || 'Saving failed with API error';
                photoboothTools.console.logDev(errorMessage);
                throw new Error(errorMessage); // Reject with a specific error
            }
        })
        .catch((error) => {
            // Catch any errors during the fetch, JSON parsing, or from API non-success status
            photoboothTools.console.logDev('Error during admin settings save:', error);

            // Ensure loader is hidden in case of unexpected errors (already done above, but good safeguard)
            $('.pageLoader').removeClass('isActive');

            if (currentOptions.reloadOnError) {
                window.location.reload();
                // We return a pending Promise here, as reload will prevent subsequent .catch() from running
                // eslint-disable-next-line no-empty-function
                return new Promise(() => {});
            }
            throw error; // Re-throw the error to be caught by the calling handlers
        });
}

/**
 * Checks if the admin settings form has pending changes that need to be saved.
 * Relies on the 'isDirty' class being added to the #save-admin-btn by the form change listener.
 *
 * @returns {boolean} True if there are unsaved changes (i.e., the save button has 'isDirty' class), false otherwise.
 */
function hasPendingAdminChanges() {
    return $('#save-admin-btn').hasClass('isDirty');
}
$(function () {
    // Highlight save button on form changes
    const $saveButton = $('#save-admin-btn');
    const initialSerialized = $('form').serialize();

    $(document).on('change input', 'form :input', function () {
        const currentSerialized = $('form').serialize();

        if (currentSerialized !== initialSerialized) {
            $saveButton.addClass('isDirty');
        } else {
            $saveButton.removeClass('isDirty');
        }
    });

    $('#reset-btn').on('click', function (e) {
        e.preventDefault();
        const msg = photoboothTools.getTranslation('really_delete');
        const really = confirm(msg);
        const elem = $(this);
        elem.addClass('saving');
        if (really) {
            // show loader
            $('.pageLoader').addClass('isActive');
            $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

            const data = new FormData(document.querySelector('form'));
            data.append('type', 'reset');
            if (typeof csrf !== 'undefined') {
                data.append(csrf.key, csrf.token);
            }

            fetch('../api/admin.php', {
                method: 'POST',
                body: data
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        photoboothTools.console.logDev(data.message);
                        window.location.reload();
                    }
                })
                .catch((error) => {
                    photoboothTools.console.logDev('Error:', error);
                });
        } else {
            elem.removeClass('saving');
        }
    });

    $('#save-admin-btn').on('click', function (e) {
        e.preventDefault();

        // The admin save button should always reload the page on success or failure
        saveAdminSettings({ reloadOnSuccess: true, reloadOnError: true })
            .then(() => {
                // This block will theoretically not be reached due to reloadOnSuccess,
                // but is kept for structural completeness if options change.
                console.log('Admin settings saved successfully via button (page reloaded).');
            })
            .catch((error) => {
                // This block will theoretically not be reached due to reloadOnError,
                // but is kept for structural completeness if options change.
                console.error('Failed to save admin settings via button (page reloaded):', error);
            });
    });

    $('#screensaver-preview-btn').on('click', function (e) {
        e.preventDefault();
        window.open('../?screensaverPreview=1', '_blank');
        return false;
    });

    $('#collage-designer').on('click', function (ev) {
        ev.preventDefault();

        saveAdminSettings({ reloadOnSuccess: false, reloadOnError: false }) // No reload here
            .then(() => {
                // Saving successful: Navigate to the Collage Designer
                const designerUrl = '../admin/collage-designer';
                const currentHash = window.location.hash ? window.location.hash.substring(1) : '';
                let targetUrl = designerUrl;
                if (currentHash) {
                    targetUrl += '?from=' + currentHash;
                }
                window.location.href = targetUrl;
            })
            .catch((error) => {
                // Saving failed: Handle error (e.g., display a toast, do not navigate)
                console.error('Failed to save admin settings before navigating to Collage Designer:', error);
                photoboothTools.console.logDev('Saving failed, not navigating to Collage Designer.');
                // Optional: photoboothTools.openToast(photoboothTools.getTranslation('saving_failed_before_designer'), 'error', 5000);
                // We do not navigate to the designer if saving fails.
            });

        return false;
    });

    $('#test-connection').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('checking'));

        $.ajax({
            url: '../api/testFtpConnection.php',
            dataType: 'json',
            data: (function () {
                const formData = $('form').serializeArray();
                if (typeof csrf !== 'undefined') {
                    formData.push({ name: csrf.key, value: csrf.token });
                }
                return formData;
            })(),
            type: 'post',
            success: (resp) => {
                photoboothTools.console.log('resp', resp);

                resp.missing.forEach((el) => {
                    photoboothTools.console.log(el);
                    $('#ftp\\:' + el).addClass('required');
                });
                alert(photoboothTools.getTranslation(resp.message));
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking FTP connection: ', jqXHR.responseText);
            },

            complete: (jqXHR, textStatus) => {
                const status = jqXHR.status;
                let classes = 'isActive isSuccess';
                let findClasses = '.success span';
                if (status != 200 || jqXHR.responseJSON.response != 'success' || textStatus != 'success') {
                    classes = 'isActive isError';
                    findClasses = '.error span';
                }

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass(classes);
                const msg = elem.find(findClasses).html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#diskusage-btn').on('click', function (e) {
        e.preventDefault();
        location.assign('../admin/diskusage');

        return false;
    });

    $('#databaserebuild-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('busy'));

        $.ajax({
            url: '../api/rebuildImageDB.php',
            data: { [csrf.key]: csrf.token },
            // eslint-disable-next-line no-unused-vars
            success: function (resp) {
                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isSuccess');
                const msg = elem.find('.success span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 3000);
            }
        });
    });

    $('#checkversion-btn').on('click', function (ev) {
        ev.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('checking'));

        $.ajax({
            url: '../api/checkVersion.php',
            method: 'GET',
            data: { [csrf.key]: csrf.token },
            success: (data) => {
                $('#checkVersion').empty();
                photoboothTools.console.log('data', data);
                if (!data.updateAvailable) {
                    $('#current_version_text').text(photoboothTools.getTranslation('using_latest_version'));
                } else if (/^\d+\.\d+\.\d+$/u.test(data.availableVersion)) {
                    $('#current_version_text').text(photoboothTools.getTranslation('current_version'));
                    $('#current_version').text(data.currentVersion);
                    $('#available_version_text').text(photoboothTools.getTranslation('available_version'));
                    $('#available_version').text(data.availableVersion);
                } else {
                    $('#current_version_text').text(photoboothTools.getTranslation('test_update_available'));
                }

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isSuccess');
                const msg = elem.find('.success span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking Version: ', jqXHR.responseText);

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isError');
                const msg = elem.find('.error span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#reset-print-lock-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('busy'));

        $.ajax({
            method: 'GET',
            url: '../api/printDB.php',
            data: {
                action: 'unlockPrint',
                [csrf.key]: csrf.token
            },
            success: (data) => {
                $('.pageLoader').removeClass('isActive');
                if (data.success) {
                    $('.adminToast').addClass('isActive isSuccess');
                    const msg = elem.find('.success span').html();
                    $('.adminToast').find('.headline').html(msg);
                } else {
                    $('.adminToast').addClass('isActive isError');
                    const msg = elem.find('.error span').html();
                    $('.adminToast').find('.headline').html(msg);
                }
                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#soundtest-btn').on('click', function (ev) {
        ev.preventDefault();

        let audioElement = document.getElementById('testaudio');
        if (audioElement === null) {
            audioElement = document.createElement('audio');
            audioElement.id = 'testaudio';
            document.body.append(audioElement);
        }

        let soundfile = null;
        if ($('[name="sound[voice]"]').val() === 'custom') {
            soundfile =
                '/private/sounds/' +
                $('[name="sound[voice]"]').val() +
                '/counter-' +
                Math.floor(Math.random() * 10 + 1) +
                '.mp3';
        } else {
            soundfile =
                '/resources/sounds/' +
                $('[name="sound[voice]"]').val() +
                '/' +
                $('[name="ui[language]"]').val() +
                '/counter-' +
                Math.floor(Math.random() * 10 + 1) +
                '.mp3';
        }
        audioElement.src = soundfile;
        audioElement.play().catch((error) => {
            photoboothTools.console.log('Error with audio.play: ' + error);
        });
        return false;
    });

    $('#debugpanel-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/debug');

        return false;
    });

    $('#translate-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('https://crowdin.com/project/photobooth');

        return false;
    });

    $('#filesupload-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/upload');

        return false;
    });

    $('#reboot-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('reboot');

        return false;
    });

    $('#shutdown-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('shutdown');

        return false;
    });
});
