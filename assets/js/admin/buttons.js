/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools shellCommand csrf */
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

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

        const data = new FormData(document.querySelector('form'));
        data.append('type', 'config');
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
    });

    $('#screensaver-preview-btn').on('click', function (e) {
        e.preventDefault();
        window.open('../?screensaverPreview=1', '_blank');
        return false;
    });

    $('#layout-generator').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/generator');

        return false;
    });

    // keep the default port in sync with the selected connection type
    $('[name="ftp[type]"]').on('change', function () {
        const defaultPorts = { ftp: '21', sftp: '22' };
        const port = $('[name="ftp[port]"]');
        const newPort = defaultPorts[$(this).val()];
        const current = String(port.val());
        if (newPort && (current === '' || Object.values(defaultPorts).includes(current))) {
            port.val(newPort);
        }
    });

    $('#test-connection').on('click', function (e) {
        e.preventDefault();

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
                let message = photoboothTools.getTranslation(resp.message);
                if (resp.detail) {
                    message += '\n\n' + resp.detail;
                }
                if (resp.path) {
                    message += '\n\n' + photoboothTools.getTranslation('ftp:test_path') + '\n' + resp.path;
                }
                if (resp.link) {
                    message += '\n\n' + photoboothTools.getTranslation('ftp:test_link') + '\n' + resp.link;
                }
                alert(message);
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking FTP connection: ', jqXHR.responseText);
            },

            complete: (jqXHR, textStatus) => {
                const response = jqXHR.responseJSON || {};
                const failed = jqXHR.status != 200 || response.response != 'success' || textStatus != 'success';
                // show the actual outcome: the toast headline defaults to "success"
                const message = response.message || (failed ? 'ftp:no_connection' : 'ftp:connected');

                $('.pageLoader').removeClass('isActive');
                $('.adminToast')
                    .removeClass('isSuccess isError')
                    .addClass(failed ? 'isActive isError' : 'isActive isSuccess');
                $('.adminToast').find('.headline').text(photoboothTools.getTranslation(message));

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

        let soundfile;
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
