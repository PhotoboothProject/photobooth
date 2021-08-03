/* global i18n */

const dependencies = (function () {
    // vars
    const api = {};

    let command;

    // init
    api.init = function () {
        api.checkOS();
    };

    api.checkOS = function () {
        jQuery
            .post('api/checkOS.php')
            .done(function (result) {
                console.log('Operating system: ', result);
                const checkDependencies = api.getTranslation('check_dependencies'),
                    unsupportedOs = api.getTranslation('unsupported_os');

                if (result.os == 'linux') {
                    $('.white-box').append($('<p style="color:green">').text(result.os));
                    $('.white-box').append($('<p>').text(checkDependencies));
                    api.runCmd('check-deps');
                } else {
                    $('.white-box').append($('<p style="color:red">').text(unsupportedOs));
                    $('.white-box').append($('<p style="color:red">').text(result.os));
                }
            })
            .fail(function (xhr, status, result) {
                console.log('Operating system check failed: ', result);
            });
    };

    api.getTranslation = function (key) {
        const translation = i18n(key, config.ui.language);
        const fallbackTranslation = i18n(key, 'en');
        if (translation) {
            return translation;
        } else if (fallbackTranslation) {
            return fallbackTranslation;
        }

        return key;
    };

    api.runCmd = function ($mode) {
        command = {
            mode: $mode
        };

        console.log('Run', $mode);

        jQuery
            .post('api/update.php', command)
            .done(function (result) {
                console.log($mode, 'result: ', result);
                const updateError = api.getTranslation('update_error');

                if (result.success) {
                    if ($mode == 'check-deps') {
                        // eslint-disable-next-line
                        result.output.forEach(function (item, index, array) {
                            $('.white-box').append($('<p>').text(item));
                        });
                    }
                } else {
                    $('.white-box').append($('<p style="color:red">').text(updateError));
                }
            })
            .fail(function (xhr, status, result) {
                const updateFail = api.getTranslation('update_fail');
                $('.white-box').append($('<p style="color:red">').text(updateFail));
                console.log($mode, 'result: ', result);
            });
    };

    return api;
})();

// Init on domready
$(function () {
    dependencies.init();
});
