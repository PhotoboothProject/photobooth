/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals remoteBuzzerClient csrf */
const photoboothTools = (function () {
    // vars
    const notificationTimeout = config.ui.notification_timeout * 1000,
        api = {};

    api.translations = null;
    api.sounds = null;
    api.isPrinting = false;
    api.csrfRefreshPromise = null;
    api.csrfReloadScheduled = false;

    api.hasCsrf = function () {
        return typeof csrf !== 'undefined' && typeof csrf.key === 'string' && typeof csrf.token === 'string';
    };

    api.getCsrfErrorMessage = function () {
        return 'Invalid CSRF token';
    };

    api.isCsrfErrorText = function (text) {
        if (typeof text !== 'string' || text === '') {
            return false;
        }

        return text.toLowerCase().includes(api.getCsrfErrorMessage().toLowerCase());
    };

    api.extractErrorMessage = function (payload) {
        if (!payload) {
            return '';
        }

        if (typeof payload === 'string') {
            return payload;
        }

        if (typeof payload.error === 'string') {
            return payload.error;
        }

        return '';
    };

    api.isCsrfErrorResponse = function (xhr) {
        if (!xhr || xhr.status !== 403) {
            return false;
        }

        const jsonMessage = api.extractErrorMessage(xhr.responseJSON);
        const textMessage = typeof xhr.responseText === 'string' ? xhr.responseText : '';

        return api.isCsrfErrorText(jsonMessage) || api.isCsrfErrorText(textMessage);
    };

    api.syncCsrfValue = function (csrfPayload) {
        if (!api.hasCsrf()) {
            return false;
        }

        if (!csrfPayload || typeof csrfPayload !== 'object') {
            return false;
        }

        if (typeof csrfPayload.key === 'string' && csrfPayload.key !== '') {
            csrf.key = csrfPayload.key;
        }

        if (typeof csrfPayload.token === 'string' && csrfPayload.token !== '') {
            csrf.token = csrfPayload.token;
            return true;
        }

        return false;
    };

    const addCsrfToUrl = function (url) {
        if (!api.hasCsrf()) {
            return url;
        }
        const u = new URL(url, window.location.origin);
        u.searchParams.set(csrf.key, csrf.token);
        return u.toString();
    };

    api.addCsrfToPayload = function (payload = {}) {
        if (!api.hasCsrf()) {
            return payload;
        }

        const csrfKey = csrf.key;
        const csrfToken = csrf.token;

        if (payload instanceof FormData) {
            if (typeof payload.set === 'function') {
                payload.set(csrfKey, csrfToken);
            } else {
                if (typeof payload.delete === 'function') {
                    payload.delete(csrfKey);
                }
                payload.append(csrfKey, csrfToken);
            }
            return payload;
        }

        if (payload instanceof URLSearchParams) {
            payload.set(csrfKey, csrfToken);
            return payload;
        }

        if (typeof payload === 'string') {
            const params = new URLSearchParams(payload);
            params.set(csrfKey, csrfToken);
            return params.toString();
        }

        if (payload === null || typeof payload === 'undefined') {
            return { [csrfKey]: csrfToken };
        }

        if (typeof payload === 'object') {
            return {
                ...payload,
                [csrfKey]: csrfToken
            };
        }

        return payload;
    };

    api.refreshCsrfToken = async function () {
        if (!api.hasCsrf()) {
            return false;
        }

        if (api.csrfRefreshPromise) {
            return api.csrfRefreshPromise;
        }

        api.csrfRefreshPromise = fetch(environment.publicFolders.api + '/csrf.php', {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin'
        })
            .then(async (response) => {
                if (!response.ok) {
                    return false;
                }

                const csrfPayload = await response.json();
                return api.syncCsrfValue(csrfPayload);
            })
            .catch(() => false)
            .finally(() => {
                api.csrfRefreshPromise = null;
            });

        return api.csrfRefreshPromise;
    };

    api.handleCsrfMismatch = function (context = '') {
        if (api.csrfReloadScheduled) {
            return;
        }

        api.csrfReloadScheduled = true;
        const message = api.getTranslation('csrf_session_reloading');
        api.console.log('ERROR: CSRF token mismatch', context);
        api.overlay.showWarning(message);
        setTimeout(() => {
            api.reloadPage();
        }, 750);
    };

    api.ajaxWithCsrf = function (ajaxOptions, retryOnCsrf = true) {
        const requestOptions = {
            ...ajaxOptions,
            data: api.addCsrfToPayload(ajaxOptions.data)
        };

        const deferred = $.Deferred();

        $.ajax(requestOptions)
            .done((data, textStatus, jqXHR) => {
                deferred.resolve(data, textStatus, jqXHR);
            })
            .fail((xhr, textStatus, errorThrown) => {
                if (retryOnCsrf && api.isCsrfErrorResponse(xhr)) {
                    api.refreshCsrfToken()
                        .then((refreshed) => {
                            if (!refreshed) {
                                api.handleCsrfMismatch(requestOptions.url || 'unknown');
                                deferred.reject(xhr, textStatus, errorThrown);
                                return;
                            }

                            api.ajaxWithCsrf(ajaxOptions, false)
                                .done((retryData, retryStatus, retryXhr) => {
                                    deferred.resolve(retryData, retryStatus, retryXhr);
                                })
                                .fail((retryErrXhr, retryErrStatus, retryErrThrown) => {
                                    if (api.isCsrfErrorResponse(retryErrXhr)) {
                                        api.handleCsrfMismatch(requestOptions.url || 'unknown');
                                    }
                                    deferred.reject(retryErrXhr, retryErrStatus, retryErrThrown);
                                });
                        })
                        .catch(() => {
                            api.handleCsrfMismatch(requestOptions.url || 'unknown');
                            deferred.reject(xhr, textStatus, errorThrown);
                        });
                    return;
                }

                deferred.reject(xhr, textStatus, errorThrown);
            });

        return deferred.promise();
    };

    api.initialize = async function () {
        const resultTranslations = await fetch(addCsrfToUrl(environment.publicFolders.api + '/translations.php'), {
            cache: 'no-store'
        });
        this.translations = await resultTranslations.json();
        const resultSounds = await fetch(addCsrfToUrl(environment.publicFolders.api + '/sounds.php'), {
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

            if (label === '') {
                return button;
            }

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

    api.askCopies = async () => {
        return new Promise((resolve) => {
            const element = document.createElement('dialog');
            element.classList.add('dialog');
            element.classList.add('rotarygroup');

            const message = document.createElement('div');
            message.classList.add('dialog-message');
            message.textContent = api.getTranslation('print:choose_copies');
            element.appendChild(message);

            const inputSection = document.createElement('div');
            inputSection.classList.add('buttonbar--print-copies');
            const minusButton = api.button.create('', 'fa fa-minus', 'default', '');
            const plusButton = api.button.create('', 'fa fa-plus', 'default', '');
            const inputText = document.createElement('input');
            inputText.classList.add('form-input-copies');
            inputText.value = '1';
            minusButton.addEventListener('click', () => {
                const oldValue = parseInt(inputText.value, 10);
                inputText.value = String(Math.max(1, oldValue - 1));
            });
            plusButton.addEventListener('click', () => {
                const oldValue = parseInt(inputText.value, 10);
                inputText.value = String(Math.min(config.print.max_multi, oldValue + 1));
            });
            inputSection.append(minusButton);
            inputSection.append(inputText);
            inputSection.append(plusButton);
            element.append(inputSection);

            const buttonbar = document.createElement('div');
            buttonbar.classList.add('dialog-buttonbar');
            element.appendChild(buttonbar);

            // confirm
            const confirmButton = api.button.create('print', 'fa fa-check', 'default', 'dialog-');
            confirmButton.addEventListener('click', () => {
                element.close(true);
                element.remove();
                resolve(inputText.value);
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
        const url = new URL(window.location.href);
        url.searchParams.set('refresh', '1');
        window.location.href = url.toString();
    };

    api.getRequest = function (url) {
        api.console.log('Sending GET request to: ' + url);
        fetch(new Request(addCsrfToUrl(url)), {
            method: 'GET',
            mode: 'cors',
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (response.status === 200) {
                    return response.text();
                } else if (response.status === 403) {
                    return response.text().then((bodyText) => {
                        throw new Error(bodyText || 'Forbidden');
                    });
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
                if (api.isCsrfErrorText(error.message)) {
                    api.handleCsrfMismatch(url);
                    return;
                }
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

    api.printImage = function (imageSrc, copies, cb) {
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
                    filename: imageSrc,
                    copies: copies,
                    [csrf.key]: csrf.token
                },
                success: (data) => {
                    api.console.log('Picture processed: ', data);

                    if (data.status == 'locking') {
                        api.overlay.showWarning(
                            config.print.locking_msg + ' (' + api.getTranslation('printed') + ' ' + data.count + ')'
                        );
                        api.resetPrintErrorMessage(cb, config.print.time);
                        $('.print-unlock-button').removeClass('hidden');
                    } else if (data.status == 'queued') {
                        api.overlay.showWarning(api.getTranslation('print_queued'));
                        api.resetPrintErrorMessage(cb, 2000);
                    } else if (data.status == 'error') {
                        if (data.error) {
                            api.console.log('ERROR: An error occurred: ', data.error);
                            api.overlay.showError(data.error);
                        } else {
                            api.console.log('ERROR: An error occurred on print.');
                            api.overlay.showError(api.getTranslation('error'));
                        }
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
                    api.console.log('ERROR: Print failed: ', textStatus);
                    api.overlay.showError(api.getTranslation('error'));
                    api.resetPrintErrorMessage(cb, notificationTimeout);
                }
            });
        }
    };

    api.printPayment = function (imageSrc, copies, cb) {
        const priceCents = Number(config.payments?.price_cents || 0);
        const priceEuro = (priceCents / 100).toFixed(2).replace('.', ',');
        const paymentMessage = (config.payments?.message || api.getTranslation('payments_message')).replace(
            '%price%',
            priceEuro
        );
        const paymentQrMsg = api.getTranslation('payments_qr_message');
        const paymentTerminalMsg = api.getTranslation('payments_terminal_message');

        api.overlay.show(paymentMessage);
        api.isPrinting = true;
        if (typeof remoteBuzzerClient !== 'undefined') {
            remoteBuzzerClient.inProgress('print');
        }

        $.ajax({
            method: 'POST',
            url: environment.publicFolders.api + '/startPaymentPrint.php',
            dataType: 'json',
            data: api.addCsrfToPayload({ filename: imageSrc, copies: copies }),
            success: (data) => {
                if (data.status === 'disabled') {
                    api.overlay.close();
                    api.isPrinting = false;
                    api.printImage(imageSrc, copies, cb);
                } else if (data.status === 'success') {
                    api.overlay.show(data.message || api.getTranslation('payments_success'));
                    setTimeout(() => {
                        api.overlay.close();
                        api.isPrinting = false;
                        api.printImage(imageSrc, copies, cb);
                    }, 1200);
                } else if (data.status === 'qr' || data.status === 'both') {
                    if (!data.payment_url) {
                        api.overlay.showError(api.getTranslation('payments_url_missing'));
                        api.resetPrintErrorMessage(cb, notificationTimeout);
                        return;
                    }
                    const qrUrl =
                        'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' +
                        encodeURIComponent(data.payment_url);
                    const statusClass = {
                        both: 'overlay-both',
                        qr: 'overlay-qr'
                    }[data.status];
                    const overlay = document.querySelector('.overlay');

                    api.overlay.show(`
                        <div style="text-align:center;">
                            <div style="font-size:1.4em; margin-bottom:12px;">${paymentMessage}</div>
                            <div style="margin-bottom:10px;">${paymentQrMsg}</div>
                            <img src="${qrUrl}" alt="QR Code" style="max-width:300px; width:80%; height:auto; background:#fff; padding:10px; border-radius:12px;">
                            ${data.status === 'both' ? `<div style="margin-top:12px;">${paymentTerminalMsg}</div>` : ''}
                        </div>
                    `);
                    api.isPrinting = false;
                    overlay.classList.remove('overlay-both', 'overlay-qr');
                    overlay.classList.add(statusClass);
                } else {
                    api.overlay.showError(data.error || api.getTranslation('payments_failed'));
                    api.resetPrintErrorMessage(cb, notificationTimeout);
                }
            },
            error: () => {
                api.overlay.showError(api.getTranslation('payments_error'));
                api.resetPrintErrorMessage(cb, notificationTimeout);
            }
        });
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
