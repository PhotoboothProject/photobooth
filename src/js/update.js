/* global i18n */

const updater = (function () {
    // vars
    const api = {};

    let command;

    // init
    api.init = function () {
        $('.checkGit').hide();
        $('.gitCommit').hide();
        $('.updateDev').hide();
        $('.updateStable').hide();
        api.checkOS();
    };

    api.checkOS = function () {
        jQuery
            .post('api/checkOS.php')
            .done(function (result) {
                const updateCheckConnection = api.getTranslation('update_check_connection');
                const updateUnsupportedOs = api.getTranslation('update_unsupported_os');
                console.log('result: ', result);
                if (result.success == 'linux') {
                    $('.white-box').append($('<p style="color:green">').text(result.success));
                    $('.white-box').append($('<p>').text(updateCheckConnection));
                    api.checkConnection();
                } else {
                    $('.white-box').append($('<p style="color:red">').text(updateUnsupportedOs));
                    $('.white-box').append($('<p style="color:red">').text(result.success));
                }
            })
            .fail(function (xhr, status, result) {
                console.log('result: ', result);
            });
    };

    api.checkConnection = function () {
        jQuery
            .post('api/checkConnection.php')
            .done(function (result) {
                const ok = api.getTranslation('ok');
                const updateNoConnection = api.getTranslation('update_no_connection');
                console.log('result: ', result);
                if (result.success === true) {
                    $('.white-box').append($('<p style="color:green">').text(ok));
                    $('.checkGit').show();
                } else {
                    $('.white-box').append($('<p style="color:red">').text(updateNoConnection));
                }
            })
            .fail(function (xhr, status, result) {
                console.log('result: ', result);
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
                const updateCommitBackup = api.getTranslation('update_commit_backup'),
                    updateReady = api.getTranslation('update_ready'),
                    updateNoGit = api.getTranslation('update_no_git'),
                    updateGitCommited = api.getTranslation('update_git_commited'),
                    updateDone = api.getTranslation('update_done'),
                    updateError = api.getTranslation('update_error'),
                    ok = api.getTranslation('ok');

                if (result.success) {
                    if ($mode === 'check-git') {
                        $('.checkGit').hide();
                        if (result.output == 'commit') {
                            $('.white-box').append($('<p style="color:red">').text(updateCommitBackup));
                            $('.gitCommit').show();
                        } else if (result.output == 'true') {
                            $('.white-box').append($('<p style="color:green">').text(ok));
                            $('.white-box').append($('<p>').text(updateReady));
                            $('.updateDev').show();
                            $('.updateStable').show();
                        } else {
                            $('.white-box').append($('<p style="color:red">').text(updateNoGit));
                        }
                    } else if ($mode === 'commit') {
                        $('.checkGit').hide();
                        $('.gitCommit').hide();
                        if (config.dev.enabled) {
                            // eslint-disable-next-line
                            result.output.forEach(function (item, index, array) {
                                $('.white-box').append($('<p>').text(item));
                            });
                        }
                        $('.white-box').append($('<p style="color:green">').text(updateGitCommited));
                        $('.white-box').append($('<p>').text(updateReady));
                        $('.updateDev').show();
                        $('.updateStable').show();
                    } else if ($mode === 'update-dev' || $mode === 'update-stable') {
                        if (config.dev.enabled) {
                            // eslint-disable-next-line
                            result.output.forEach(function (item, index, array) {
                                $('.white-box').append($('<p>').text(item));
                            });
                        }
                        $('.white-box').append($('<h2 style="color:green">').text(updateDone));
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

    $('.checkGit').on('click', function (e) {
        e.preventDefault();
        const updateCheckGit = api.getTranslation('update_check_git');
        $('.white-box').append($('<p>').text(updateCheckGit));
        api.runCmd('check-git');
        $('.checkGit').blur();
    });

    $('.gitCommit').on('click', function (e) {
        e.preventDefault();
        const updateCommitting = api.getTranslation('update_committing');
        $('.white-box').append($('<p>').text(updateCommitting));
        api.runCmd('commit');
        $('.gitCommit').blur();
    });

    $('.updateDev').on('click', function (e) {
        e.preventDefault();
        const updateRunning = api.getTranslation('update_running');
        $('.white-box').append($('<p>').text(updateRunning));
        api.runCmd('update-dev');
        $('.updateDev').blur();
        $('.updateDev').hide();
        $('.updateStable').hide();
    });

    $('.updateStable').on('click', function (e) {
        e.preventDefault();
        const updateRunning = api.getTranslation('update_running');
        $('.white-box').append($('<p>').text(updateRunning));
        api.runCmd('update-stable');
        $('.updateStable').blur();
        $('.updateDev').hide();
        $('.updateStable').hide();
    });

    return api;
})();

// Init on domready
$(function () {
    updater.init();
});
