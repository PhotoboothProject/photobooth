/* globals photoboothTools */

const updater = (function () {
    // vars
    const api = {};

    let command;

    // init
    api.init = function () {
        $('.gitCommit').hide();
        $('.updateDev').hide();
        $('.updateStable').hide();
        api.checkOS();
    };

    api.checkOS = function () {
        jQuery
            .post('api/checkOS.php')
            .done(function (result) {
                photoboothTools.console.log('Operating system: ' + result.os);
                const checkConnection = photoboothTools.getTranslation('check_connection'),
                    unsupportedOs = photoboothTools.getTranslation('unsupported_os');

                if (result.os == 'linux') {
                    $('.white-box').append($('<p style="color:green">').text(result.os));
                    $('.white-box').append($('<p>').text(checkConnection));
                    api.checkConnection();
                } else {
                    $('.white-box').append($('<p style="color:red">').text(unsupportedOs));
                    $('.white-box').append($('<p style="color:red">').text(result.os));
                }
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Operating system check failed: ', result);
            });
    };

    api.checkConnection = function () {
        jQuery
            .post('api/checkConnection.php')
            .done(function (result) {
                photoboothTools.console.log('Connected: ', result);
                const ok = photoboothTools.getTranslation('ok'),
                    noConnection = photoboothTools.getTranslation('no_connection'),
                    updateCheckGit = photoboothTools.getTranslation('update_check_git');

                if (result.connected === true) {
                    $('.white-box').append($('<p style="color:green">').text(ok));
                    $('.white-box').append($('<p>').text(updateCheckGit));
                    api.runCmd('check-git');
                } else {
                    $('.white-box').append($('<p style="color:red">').text(noConnection));
                }
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Checking connection failed: ', result);
            });
    };

    api.runCmd = function ($mode) {
        command = {
            mode: $mode
        };

        photoboothTools.console.log('Run ' + $mode);

        jQuery
            .post('api/update.php', command)
            .done(function (result) {
                photoboothTools.console.log($mode, 'result: ', result);
                const updateCommitBackup = photoboothTools.getTranslation('update_commit_backup'),
                    updateReady = photoboothTools.getTranslation('update_ready'),
                    updateNoGit = photoboothTools.getTranslation('update_no_git'),
                    updateGitCommited = photoboothTools.getTranslation('update_git_commited'),
                    updateDone = photoboothTools.getTranslation('update_done'),
                    updateError = photoboothTools.getTranslation('update_error'),
                    ok = photoboothTools.getTranslation('ok');

                if (result.success) {
                    if ($mode === 'check-git') {
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
                const updateFail = photoboothTools.getTranslation('update_fail');
                $('.white-box').append($('<p style="color:red">').text(updateFail));
                photoboothTools.console.log($mode, 'result: ', result);
            });
    };

    $('.gitCommit').on('click', function (e) {
        e.preventDefault();
        const updateCommitting = photoboothTools.getTranslation('update_committing');
        $('.white-box').append($('<p>').text(updateCommitting));
        api.runCmd('commit');
        $('.gitCommit').blur();
    });

    $('.updateDev').on('click', function (e) {
        e.preventDefault();
        const updateRunning = photoboothTools.getTranslation('update_running');
        $('.white-box').append($('<p>').text(updateRunning));
        api.runCmd('update-dev');
        $('.updateDev').blur();
        $('.updateDev').hide();
        $('.updateStable').hide();
    });

    $('.updateStable').on('click', function (e) {
        e.preventDefault();
        const updateRunning = photoboothTools.getTranslation('update_running');
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
