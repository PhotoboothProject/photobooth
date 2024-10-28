/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals remoteBuzzerClient */
const photoboothTools = (function () {
    // vars
    const notificationTimeout = config.ui.notification_timeout * 1000,
        api = {};

    api.translations = null;
    api.sounds = null;
    api.isPrinting = false;

    api.initialize = async function () {
        const resultTranslations = await fetch(environment.publicFolders.api + '/translations.php', {
            cache: 'no-store'
        });
        this.translations = await resultTranslations.json();
        const resultSounds = await fetch(environment.publicFolders.api + '/sounds.php', {
            cache: 'no-store'
        });
        this.sounds = await resultSounds.json();
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
                const customEvent = new CustomEvent(name, { detail: detail });
                document.dispatchEvent(customEvent);
            });
        });

        document.addEventListener('photobooth.remotebuzzer', (event) => {
            api.getRequest(
                window.location.protocol +
                    '//' +
                    config.remotebuzzer.serverip +
                    ':' +
                    config.remotebuzzer.port +
                    '/commands/' +
                    event.detail.data.action
            );
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

    api.getSound = function (key) {
        if (!this.sounds[key]) {
            this.console.logDev('sound key not found: ' + key);

            return null;
        }

        return this.sounds[key];
    };

    api.overlay = {
        element: null,
        show: (message, type = 'default') => {
            if (api.overlay.element === null) {
                const element = document.createElement('div');
                element.classList.add('overlay');
                document.body.append(element);
                api.overlay.element = element;
            }
            api.overlay.element.innerHTML = message;
            api.overlay.element.dataset.type = type;
        },
        showWaiting: (message) => {
            api.overlay.show(
                '<div><i class="' + config.icons.spinner + '"></i></div><div>' + message + '</div>',
                'progress'
            );
        },
        showSuccess: (message) => {
            api.overlay.show(message, 'success');
        },
        showWarning: (message) => {
            api.overlay.show(message, 'warning');
        },
        showError: (message) => {
            api.overlay.show(message, 'error');
        },
        close: () => {
            if (api.overlay.element !== null) {
                api.overlay.element.remove();
                api.overlay.element = null;
            }
        }
    };

    api.button = {
        create: (label, iconClass, severity = 'default', prefix = '') => {
            const button = document.createElement('button');
            button.classList.add(prefix + 'button');
            button.classList.add('rotaryfocus');
            button.dataset.severity = severity;

            const iconWrap = document.createElement('span');
            iconWrap.classList.add(prefix + 'button--icon');
            const icon = document.createElement('i');
            icon.classList = iconClass;
            iconWrap.appendChild(icon);
            button.appendChild(iconWrap);

            const labelWrap = document.createElement('span');
            labelWrap.classList.add(prefix + 'button--label');
            labelWrap.innerHTML = api.getTranslation(label);
            button.appendChild(labelWrap);

            return button;
        }
    };

    api.modal = {
        element: null,
        open: (type = 'default') => {
            if (api.modal.element === null) {
                const element = document.createElement('div');
                element.dataset.type = type;
                element.classList.add('modal');
                element.classList.add('rotarygroup');

                const inner = document.createElement('div');
                inner.classList.add('modal-inner');
                element.appendChild(inner);

                const body = document.createElement('div');
                body.classList.add('modal-body');
                inner.appendChild(body);

                const buttonbar = document.createElement('div');
                buttonbar.classList.add('modal-buttonbar');
                const closeButton = api.button.create('close', 'fa fa-times', 'default', 'modal-');
                closeButton.addEventListener('click', () => api.modal.close());
                buttonbar.appendChild(closeButton);
                inner.appendChild(buttonbar);

                document.body.append(element);
                api.modal.element = element;
            }
        },
        close: () => {
            if (api.modal.element !== null) {
                api.modal.element.remove();
                api.modal.element = null;
            }
        }
    };

    api.confirm = async (confirmationText) => {
        return new Promise((resolve) => {
            const element = document.createElement('dialog');
            element.classList.add('dialog');
            element.classList.add('rotarygroup');

            const message = document.createElement('div');
            message.classList.add('dialog-message');
            message.textContent = confirmationText;
            element.appendChild(message);

            const buttonbar = document.createElement('div');
            buttonbar.classList.add('dialog-buttonbar');
            element.appendChild(buttonbar);

            // confirm
            const confirmButton = api.button.create('confirm', 'fa fa-check', 'default', 'dialog-');
            confirmButton.addEventListener('click', () => {
                element.close(true);
                element.remove();
                resolve(true);
            });
            buttonbar.appendChild(confirmButton);

            // cancel
            const cancelButton = api.button.create('cancel', 'fa fa-times', 'default', 'dialog-');
            cancelButton.addEventListener('click', () => {
                element.close(false);
                element.remove();
                resolve(false);
            });
            buttonbar.appendChild(cancelButton);

            element.addEventListener('cancel', function () {
                element.close(false);
                element.remove();
                resolve(false);
            });

            document.body.append(element);
            element.showModal();
        });
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
        setTimeout(() => {
            api.overlay.close();
            cb();
            api.isPrinting = false;
        }, to);
    };

    api.printImage = function (imageSrc, cb) {
        if (api.isVideoFile(imageSrc)) {
            api.console.log('ERROR: An error occurred: attempt to print non printable file.');
            api.overlay.showError(api.getTranslation('no_printing'));
            setTimeout(() => api.overlay.close(), notificationTimeout);
        } else if (api.isPrinting) {
            api.console.log('Printing in progress: ' + api.isPrinting);
        } else {
            api.overlay.show(api.getTranslation('printing'));
            api.isPrinting = true;
            if (typeof remoteBuzzerClient !== 'undefined') {
                remoteBuzzerClient.inProgress('print');
            }
            $.ajax({
                method: 'GET',
                url: environment.publicFolders.api + '/print.php',
                data: {
                    filename: imageSrc
                },
                success: (data) => {
                    api.console.log('Picture processed: ', data);

                    if (data.status == 'locking') {
                        api.overlay.showWarning(
                            config.print.locking_msg + ' (' + api.getTranslation('printed') + ' ' + data.count + ')'
                        );
                        api.resetPrintErrorMessage(cb, config.print.time);
                        $('.print-unlock-button').removeClass('hidden');
                    } else if (data.error) {
                        api.console.log('ERROR: An error occurred: ', data.error);
                        api.overlay.showError(data.error);
                        api.resetPrintErrorMessage(cb, config.print.time);
                    } else {
                        setTimeout(function () {
                            api.overlay.close();
                            cb();
                            api.isPrinting = false;
                        }, config.print.time);
                    }
                },
                error: (jqXHR, textStatus) => {
                    api.console.log('ERROR: An error occurred: ', textStatus);
                    api.overlay.showError(api.getTranslation('error'));
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
