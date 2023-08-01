/* globals i18n remoteBuzzerClient */
const photoboothTools = (function () {
    // vars
    const notificationTimeout = config.ui.notification_timeout * 1000,
        api = {};

    api.isPrinting = false;

    api.console = {
        log: function (...content) {
            console.log('[', new Date().toISOString(), ']: ' + JSON.stringify(content));
        },
        logDev: function (...content) {
            if (config.dev.loglevel > 0) {
                console.log('[', new Date().toISOString(), ']: ' + JSON.stringify(content));
            }
        }
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

    api.modal = {
        open: function (selector) {
            $(selector).addClass('modal--show');
        },
        close: function (selector) {
            if ($(selector).hasClass('modal--show')) {
                $(selector).removeClass('modal--show');

                return true;
            }

            return false;
        },
        toggle: function (selector) {
            $(selector).toggleClass('modal--show');
        },
        empty: function (selector) {
            api.modal.close(selector);

            $(selector).find('.modal__body').empty();
        }
    };

    api.modalMesg = {
        showSuccess: function (selector, successMsg) {
            $(selector).empty();
            $(selector).html('<div class="modal__body success"><span>' + successMsg + '</span></div>');
            api.modal.open($(selector));
        },
        showWarn: function (selector, warnMsg) {
            $(selector).empty();
            $(selector).html('<div class="modal__body warning"><span>' + warnMsg + '</span></div>');
            api.modal.open($(selector));
        },
        showError: function (selector, errorMsg) {
            $(selector).empty();
            $(selector).html('<div class="modal__body error"><span>' + errorMsg + '</span></div>');
            api.modal.open($(selector));
        },
        reset: function (selector) {
            api.modal.close($(selector));
            $(selector).empty();
        }
    };

    api.reloadPage = function () {
        window.location.reload();
    };

    api.getRequest = function (url) {
        const request = new XMLHttpRequest();
        api.console.log('Sending GET request to: ' + url);

        request.onload = function () {
            if (request.status === 200) {
                // parse JSON data
                const responseData = request.responseText;
                api.console.log(responseData);
            } else if (request.status === 404) {
                api.console.log('No records found');
            } else {
                api.console.log('Unhandled request status: ' + request.status);
            }
        };

        request.onerror = function () {
            api.console.log('Network error occurred');
        };

        request.open('GET', url);
        request.send();
    };

    api.isVideoFile = function (filename) {
        const extension = api.getFileExtension(filename);

        return extension === 'mp4' || extension === 'gif';
    };

    api.getFileExtension = function (filename) {
        const parts = filename.split('.');

        return parts[parts.length - 1];
    };

    api.resetPrintErrorMessage = function (cb, to) {
        setTimeout(function () {
            api.modalMesg.reset('#modal_mesg');
            cb();
            api.isPrinting = false;
        }, to);
    };

    api.printImage = function (imageSrc, cb) {
        if (api.isVideoFile(imageSrc)) {
            api.console.log('ERROR: An error occurred: attempt to print non printable file.');
            api.modalMesg.showError('#modal_mesg', api.getTranslation('no_printing'));
            setTimeout(function () {
                api.modalMesg.reset('#modal_mesg');
            }, notificationTimeout);
        } else if (api.isPrinting) {
            api.console.log('Printing in progress: ' + api.isPrinting);
        } else {
            api.modal.open('#print_mesg');
            api.isPrinting = true;
            if (typeof remoteBuzzerClient !== 'undefined') {
                remoteBuzzerClient.inProgress('print');
            }
            $.ajax({
                method: 'GET',
                url: config.foldersJS.api + '/print.php',
                data: {
                    filename: imageSrc
                },
                success: (data) => {
                    api.console.log('Picture processed: ', data);

                    if (data.status == 'locking') {
                        api.modal.close('#print_mesg');
                        api.modalMesg.showWarn(
                            '#modal_mesg',
                            config.print.locking_msg + ' (' + api.getTranslation('printed') + ' ' + data.count + ')'
                        );
                        api.resetPrintErrorMessage(cb, config.print.time);
                    } else if (data.error) {
                        api.console.log('ERROR: An error occurred: ', data.error);
                        api.modal.close('#print_mesg');
                        api.modalMesg.showError('#modal_mesg', data.error);
                        api.resetPrintErrorMessage(cb, config.print.time);
                    } else {
                        setTimeout(function () {
                            api.modal.close('#print_mesg');
                            cb();
                            api.isPrinting = false;
                        }, config.print.time);
                    }
                },
                error: (jqXHR, textStatus) => {
                    api.console.log('ERROR: An error occurred: ', textStatus);
                    api.modal.close('#print_mesg');
                    api.modalMesg.showError('#modal_mesg', api.getTranslation('error'));
                    api.resetPrintErrorMessage(cb, notificationTimeout);
                }
            });
        }
    };

    $(document).on('keyup', function (ev) {
        if (config.reload.key && parseInt(config.reload.key, 10) === ev.keyCode) {
            api.reloadPage();
        }
    });

    return api;
})();

// Init on domready
$(function () {
    photoboothTools.console.log('Loglevel: ' + config.dev.loglevel);
});
