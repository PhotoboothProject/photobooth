/* globals photoboothTools */
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
        $('.pageLoader').find('label').html("Wird gespeichert...");

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
        location.assign('/admin/diskusage/');  

        return false;
    });

    $('#databaserebuild-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html("Wird gespeichert...");

        $.ajax({
            url: '../api/rebuildImageDB.php',
            success: function (resp) {
                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isSuccess');
                var msg = elem.find('.success span').html();
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
        $('.pageLoader').find('label').html("Wird geprüft...");

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
                var msg = elem.find('.success span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking Version: ', jqXHR.responseText);

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isError');
                var msg = elem.find('.error span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#debugpanel-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('/admin/debug');

        return false;
    });

    $('#translate-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('https://crowdin.com/project/photobooth');

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