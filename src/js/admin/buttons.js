/* globals photoboothTools shellCommand */
$(function () {
    $('#reset-btn').on('click', function (e) {
        e.preventDefault();
        const msg = photoboothTools.getTranslation('really_delete');
        const really = confirm(msg);
        const data = 'type=reset&' + $('form').serialize();
        const elem = $(this);
        elem.addClass('saving');
        if (really) {
            $.ajax({
                url: '../api/admin.php',
                data: data,
                dataType: 'json',
                type: 'post',
                success: function (resp) {
                    elem.removeClass('saving');
                    elem.addClass(resp);

                    setTimeout(function () {
                        elem.removeClass('error success');

                        window.location.reload();
                    }, 3000);
                }
            });
        } else {
            elem.removeClass('saving');
        }
    });

    $('#save-admin-btn').on('click', function (e) {
        e.preventDefault();
        const data = 'type=config&' + $('form').serialize();

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

        // ajax
        $.ajax({
            url: '../api/admin.php',
            data: data,
            dataType: 'json',
            type: 'post',
            success: function (resp) {
                setTimeout(function () {
                    if (resp === 'success') {
                        window.location.reload();
                    }
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
                action: 'unlockPrint'
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

    $('#imagesupload-btn').on('click', function (ev) {
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

    $('#nclogin-btn').on('click', function (e) {
        e.preventDefault();

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('ncCredentials'));

        let url = $('input[name="nextcloud[url]"]').val().trim();

        // Process the url input
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            url = 'https://' + url;
        }
        if (url.endsWith('/')) {
            url = url.slice(0, -1);
        }
        $('input[name="nextcloud[url]"]').val(url);

        // Send Nextcloud Login flow v2 request
        $.ajax({
            type: 'POST',
            url: '../api/nclogin.php',
            data: {url: url},
            success: function (response) {
                response = $.parseJSON(response);
                // Open new tab or window
                window.open(response.login);
                // Start polling Poll Endpoint
                $.ajax({
                    type: 'POST',
                    url: '../api/ncpoll.php',
                    data: JSON.stringify(response),
                    contentType: 'application/json',
                    success: function (resp) {
                        response = $.parseJSON(resp);
                        $('.pageLoader').find('div[role="status"]').hide();
                        $('.pageLoader').find('label').html(photoboothTools.getTranslation('ncCredentials'));
                        $('input[name="nextcloud[user]"]').val(response.loginName);
                        $('input[name="nextcloud[pass]"]').val(response.appPassword);
                        setTimeout(function () {
                            $('.pageLoader').removeClass('isActive');
                            $('.pageLoader').find('div[role="status"]').show();
                            $('#save-admin-btn').click();
                        }, 3000);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        $('.pageLoader').find('div[role="status"]').hide();
                        switch (jqXHR.status) {
                            case 400:
                                // Handle 400 Bad Request error
                                $('.pageLoader')
                                    .find('label')
                                    .html('Invalid input: ' + jqXHR.responseText);
                                break;
                            case 500:
                                // Handle 500 Internal Server Error
                                $('.pageLoader').find('label').html('An error occurred on the server.');
                                break;
                            default:
                                // Handle other HTTP error statuses
                                $('.pageLoader')
                                    .find('label')
                                    .html('An error occurred: ' + textStatus + errorThrown);
                                break;
                        }
                        setTimeout(function () {
                            $('.pageLoader').removeClass('isActive');
                            $('.pageLoader').find('div[role="status"]').show();
                            $('#reset-btn').click();
                        }, 3000);
                    }
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $('.pageLoader').find('div[role="status"]').hide();
                switch (jqXHR.status) {
                    case 400:
                        // Handle 400 Bad Request error
                        $('.pageLoader')
                            .find('label')
                            .html('Invalid input: ' + jqXHR.responseText);
                        break;
                    case 500:
                        // Handle 500 Internal Server Error
                        $('.pageLoader')
                            .find('label')
                            .html('An error occurred on the server: ' + jqXHR.responseText);
                        break;
                    default:
                        // Handle other HTTP error statuses
                        $('.pageLoader')
                            .find('label')
                            .html('An error occurred: ' + textStatus + errorThrown);
                        break;
                }
                setTimeout(function () {
                    $('.pageLoader').removeClass('isActive');
                    $('.pageLoader').find('div[role="status"]').show();
                    $('#reset-btn').click();
                }, 3000);
            }
        });

        return false;
    });
});
