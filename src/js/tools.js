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

    return api;
})();

// Init on domready
$(function () {
    photoboothTools.console.log('Dev mode:', config.dev.enabled ? 'enabled' : 'disabled');
});
