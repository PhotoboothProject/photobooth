const photoboothTools = (function () {
    // vars
    const api = {};

    api.console = {
        log: function (...content) {
            console.log('[', new Date().toISOString(), ']:', content);
        },
        logDev: function (...content) {
            if (config.dev.enabled) {
                console.log('[', new Date().toISOString(), ']:', content);
            }
        }
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

    api.reloadPage = function () {
        window.location.reload();
    };

    return api;
})();

// Init on domready
$(function () {
    photoboothTools.console.log('Dev mode:', config.dev.enabled ? 'enabled' : 'disabled');
});
