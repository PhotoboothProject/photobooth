/* globals remoteBuzzerClient */
const photoboothTools = (function () {
    // vars
    const notificationTimeout = config.ui.notification_timeout * 1000,
        api = {};

    api.translations = null;
    api.isPrinting = false;

    api.initialize = async function () {
        const result = await fetch(
            config.photobooth.basePath + 'api/translations.php',
            {
                cache: 'no-store'
            }
        );
        this.translations = await result.json();
        this.registerEvents();
    };

    api.registerEvents = () => {

        document.querySelectorAll('[data-command]').forEach((button) => {
            button.addEventListener('click', (event) => {
                const target = event.currentTarget;
                const data = target.dataset;

                // Check if command is in list of supported events
                // This can be dropped after all actions are migrated
                if (!['remotebuzzer', 'reload'].includes(data.command)) {
                    api.console.log('not supported command: ' + name);
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const name = 'photobooth.' + data.command;
                const detail = {
                    trigger: target,
                    data: Object.assign({}, data)
                };

                api.console.log('dispatch: ' + name);
                const customEvent = new CustomEvent(name , { detail: detail });
                document.dispatchEvent(customEvent);
            });
        });

        document.addEventListener('photobooth.remotebuzzer', (event) => {
            api.getRequest(window.location.protocol + '//' + config.remotebuzzer.serverip + ':' + config.remotebuzzer.port + '/commands/' + event.detail.data.action);
        });

        document.addEventListener('photobooth.reload', () => {
            api.reloadPage();
        });

    };

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
        if (!this.translations[key]) {
            this.console.logDev('translation key not found: ' + key);

            return key;
        }

        return this.translations[key];
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
        api.console.log('Sending GET request to: ' + url);
        fetch(new Request(url), {
            method: 'GET',
            mode: 'cors',
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (response.status === 200) {
                    return response.text();
                } else if (response.status === 404) {
                    throw new Error('No records found');
                } else {
                    throw new Error('Unhandled request status: ' + response.status);
                }
            })
            .then(function (data) {
                api.console.log(data);
            })
            .catch(function (error) {
                api.console.log('Error occurred: ' + error.message);
            });
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
                url: config.foldersPublic.api + '/print.php',
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
    photoboothTools.initialize().then(() => {
        photoboothTools.console.log('PhotoboothTools: initialized');
        photoboothTools.console.log('Loglevel: ' + config.dev.loglevel);
    });
});
