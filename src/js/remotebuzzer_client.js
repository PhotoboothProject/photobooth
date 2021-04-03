/* exported rotaryController initRemoteBuzzerFromDOM remoteBuzzerClient */
/* global photoBooth globalGalleryHandle io */

let remoteBuzzerClient;
let rotaryController;
// eslint-disable-next-line no-unused-vars
function initRemoteBuzzerFromDOM() {
    if (config.dev.enabled) {
        console.log('Remote Buzzer client is', config.remotebuzzer.enabled ? 'enabled' : 'disabled');
    }

    /*
     ** Communication with Remote Buzzer Server
     ** Controls PB when in BUTTON mode
     */

    remoteBuzzerClient = (function () {
        let ioClient;
        const api = {};

        api.enabled = function () {
            return config.remotebuzzer.enabled;
        };

        api.init = function () {
            if (!this.enabled()) {
                return;
            }

            if (config.webserver.ip) {
                ioClient = io('http://' + config.webserver.ip + ':' + config.remotebuzzer.port);

                if (config.dev.enabled) {
                    console.log(
                        'Remote buzzer connecting to http://' + config.webserver.ip + ':' + config.remotebuzzer.port
                    );
                }

                ioClient.on('photobooth-socket', function (data) {
                    switch (data) {
                        case 'start-picture':
                            $('.resultInner').removeClass('show');
                            photoBooth.thrill('photo');
                            break;

                        case 'start-collage':
                            if (config.collage.enabled) {
                                $('.resultInner').removeClass('show');
                                photoBooth.thrill('collage');
                            }
                            break;

                        case 'print':
                            if ($('#result').is(':visible')) {
                                $('.printbtn').trigger('click');
                                $('.printbtn').blur();
                            } else if ($('.pswp__button--print').is(':visible')) {
                                $('.pswp__button--print').trigger('click');
                            } else {
                                ioClient.emit('photobooth-socket', 'completed');
                            }
                            break;

                        case 'rotary-cw':
                            rotaryController.focusNext();
                            break;

                        case 'rotary-ccw':
                            rotaryController.focusPrev();
                            break;

                        case 'rotary-btn-press':
                            rotaryController.click();
                            break;

                        default:
                            break;
                    }
                });

                ioClient.on('connect_error', function () {
                    console.log(
                        'ERROR: remotebuzzer_client unable to connect to webserver ip - please ensure remotebuzzer_server is running on Photobooth server. Use Photobooth dev mode to create log file for debugging'
                    );
                });

                ioClient.on('connect', function () {
                    if (config.dev.enabled) {
                        console.log('remotebuzzer_client successfully connected to Photobooth webserver ip');
                    }
                });

                rotaryController.focusSet('#start');
            } else {
                console.log(
                    'ERROR: remotebuzzer_client unable to connect - webserver.ip not defined in photobooth config'
                );
            }

            rotaryController.init();
        };

        api.inProgress = function (flag) {
            if (this.enabled()) {
                if (flag) {
                    ioClient.emit('photobooth-socket', 'in-progress');
                } else {
                    ioClient.emit('photobooth-socket', 'completed');
                }
            }
        };

        api.collageWaitForNext = function () {
            if (this.enabled()) {
                ioClient.emit('photobooth-socket', 'collage-wait-for-next');
            }
        };

        return api;
    })();

    /*
     ** Controls PB when in ROTARY mode
     */

    rotaryController = (function () {
        // vars
        let enabled = false;
        const api = {};

        // API functions
        api.init = function () {
            enabled = config.remotebuzzer.userotary;
        };

        api.focusSet = function (id) {
            if (enabled) {
                this.focusRemove();
                $(id).find('.rotaryfocus').first().addClass('focused');
            }
        };

        api.focusRemove = function () {
            $('.focused').removeClass('focused');
        };

        api.focusNext = function () {
            if (this.rotationInactive() || !enabled) {
                return;
            }

            if ($('.pswp.pswp--open').is(':visible')) {
                // photoswipe navigation

                const buttonList = $('.pswp.pswp--open').find('.rotaryfocus:visible');
                let focusIndex = buttonList.index($('.focused'));

                if (buttonList.eq(focusIndex + 1).exists()) {
                    focusIndex += 1;
                } else if (buttonList.eq(0).exists()) {
                    focusIndex = 0;
                }

                globalGalleryHandle.ui.setIdle(false);

                $('.focused')
                    .removeClass('focused pswp-rotary-focus')
                    .parents('.pswp.pswp--open')
                    .find('.rotaryfocus:visible')
                    .eq(focusIndex)
                    .addClass('focused pswp-rotary-focus')
                    .find('i.fa')
                    .css('z-index', '1');
            } else {
                const buttonList = $('.focused').parents('.rotarygroup').find('.rotaryfocus:visible');
                let focusIndex = buttonList.index($('.focused'));

                if (buttonList.eq(focusIndex + 1).exists()) {
                    focusIndex += 1;
                } else if (buttonList.eq(0).exists()) {
                    focusIndex = 0;
                }

                $('.focused')
                    .removeClass('focused')
                    .parents('.rotarygroup')
                    .find('.rotaryfocus:visible')
                    .eq(focusIndex)
                    .addClass('focused')
                    .focus();
            }
        };

        api.focusPrev = function () {
            if (this.rotationInactive() || !enabled) {
                return;
            }

            if ($('.pswp.pswp--open').is(':visible')) {
                // photoswipe navigation
                const buttonList = $('.pswp.pswp--open').find('.rotaryfocus:visible');
                const focusIndex = buttonList.index($('.focused'));

                if (buttonList.eq(focusIndex - 1).exists()) {
                    globalGalleryHandle.ui.setIdle(false);

                    $('.focused')
                        .removeClass('focused pswp-rotary-focus')
                        .parents('.pswp.pswp--open')
                        .find('.rotaryfocus:visible')
                        .eq(focusIndex - 1)
                        .addClass('focused pswp-rotary-focus')
                        .find('i.fa')
                        .css('z-index', '1');
                }
            } else {
                const buttonList = $('.focused').parents('.rotarygroup').find('.rotaryfocus:visible');
                const focusIndex = buttonList.index($('.focused'));

                if (buttonList.eq(focusIndex - 1).exists()) {
                    $('.focused')
                        .removeClass('focused')
                        .parents('.rotarygroup')
                        .find('.rotaryfocus:visible')
                        .eq(focusIndex - 1)
                        .addClass('focused')
                        .focus();
                }
            }
        };

        api.rotationInactive = function () {
            if ($('.modal.modal--show').exists()) {
                return true;
            }

            return false;
        };

        api.click = function () {
            if (enabled) {
                // click modal if open
                if ($('#qrCode.modal.modal--show').exists()) {
                    $('#qrCode').click();
                } else {
                    $('.focused').blur().trigger('click');

                    if ($('.pswp.pswp--open').is(':visible')) {
                        globalGalleryHandle.ui.setIdle(true);
                    }
                }
            }
        };

        // private helper functions
        $.fn.exists = function () {
            return this.length !== 0;
        };

        return api;
    })();

    remoteBuzzerClient.init();
}
