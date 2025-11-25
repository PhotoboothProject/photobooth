/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoBooth photoboothTools */

function addCacheBustingParam(url) {
    const timestamp = new Date().getTime();

    if (url.includes('?')) {
        return `${url}&t=${timestamp}`;
    }

    return `${url}?t=${timestamp}`;
}

function getRootProperty(property) {
    const root = document.documentElement;
    const style = getComputedStyle(root);
    return style.getPropertyValue(property).trim();
}

const photoboothPreview = (function () {
    // vars
    const CameraDisplayMode = {
            INIT: 1,
            BACKGROUND: 2,
            COUNTDOWN: 3,
            TEST: 4
        },
        PreviewMode = {
            NONE: 'none',
            DEVICE: 'device_cam',
            URL: 'url',
            WEBRTC: 'webrtc'
        },
        webcamConstraints = {
            audio: false,
            video: {
                width: config.preview.videoWidth,
                height: config.preview.videoHeight,
                facingMode: config.preview.camera_mode
            }
        },
        api = {};

    let pid,
        video,
        loader,
        url,
        pictureFrame,
        collageFrame,
        retryGetMedia = 3,
        webrtcPeer = null;

    function startGo2rtcWebRTC(srcName) {
        const webrtcUrl = srcName;
        const videoEl = video.get(0);

        // Close previous peer if any
        if (webrtcPeer) {
            try {
                webrtcPeer.getSenders().forEach((s) => s.track && s.track.stop());
                webrtcPeer.close();
            } catch (e) {
                photoboothTools.console.logDev('Preview: startGo2rtcWebRTC error ' + JSON.stringify(e));
            }
            webrtcPeer = null;
        }

        const pc = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        });

        webrtcPeer = pc;

        const inboundStream = new MediaStream();
        videoEl.srcObject = inboundStream;

        pc.addTransceiver('video', { direction: 'recvonly' });

        pc.ontrack = function (ev) {
            inboundStream.addTrack(ev.track);
        };

        function waitForIceComplete() {
            return new Promise((resolve) => {
                if (pc.iceGatheringState === 'complete') {
                    resolve();
                } else {
                    const checkState = () => {
                        if (pc.iceGatheringState === 'complete') {
                            pc.removeEventListener('icegatheringstatechange', checkState);
                            resolve();
                        }
                    };
                    pc.addEventListener('icegatheringstatechange', checkState);
                }
            });
        }

        return pc
            .createOffer()
            .then((offer) => pc.setLocalDescription(offer))
            .then(waitForIceComplete)
            .then(() =>
                fetch(webrtcUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type: 'offer',
                        sdp: pc.localDescription.sdp
                    })
                })
            )
            .then((resp) => {
                if (!resp.ok) {
                    throw new Error('error go2rtc HTTP ' + resp.status);
                }
                return resp.json();
            })
            .then((answer) => pc.setRemoteDescription(answer))
            .then(() =>
                videoEl.play().catch(() => {
                    photoboothTools.console.logDev('Preview: Safari requires user gesture before play()');
                })
            )
            .catch((err) => {
                photoboothTools.console.log('ERROR: Preview WebRTC failed:', err);
            });
    }

    api.changeVideoMode = function (mode) {
        photoboothTools.console.logDev('Preview: Changing video mode: ' + mode);
        if (mode !== CameraDisplayMode.BACKGROUND) {
            loader.css('--stage-background', 'transparent');
        }
        video.show();
    };

    api.initializeMedia = function (
        cb = () => {
            return;
        },
        retry = 0
    ) {
        photoboothTools.console.logDev('Preview: Trying to initialize media...');
        if (
            !navigator.mediaDevices ||
            config.preview.mode === PreviewMode.NONE.valueOf() ||
            config.preview.mode === PreviewMode.URL.valueOf() ||
            config.preview.mode === PreviewMode.WEBRTC.valueOf() // NEW: skip gUM for WebRTC
        ) {
            photoboothTools.console.logDev('Preview: No preview from device cam or no webcam available!');

            return;
        }
        const getMedia =
            navigator.mediaDevices.getUserMedia ||
            navigator.mediaDevices.webkitGetUserMedia ||
            navigator.mediaDevices.mozGetUserMedia ||
            false;

        if (!getMedia) {
            photoboothTools.console.logDev('Preview: Could not get media!');

            return;
        }

        getMedia
            .call(navigator.mediaDevices, webcamConstraints)
            .then(function (stream) {
                photoboothTools.console.logDev('Preview: getMedia done!');
                api.stream = stream;
                video.get(0).srcObject = stream;
                cb();
            })
            .catch(function (error) {
                photoboothTools.console.log('ERROR: Preview: Could not get user media: ', error);
                if (retry < retryGetMedia) {
                    photoboothTools.console.logDev(
                        'Preview: Retrying to get user media. Retry ' + retry + ' / ' + retryGetMedia
                    );
                    retry += 1;
                    setTimeout(function () {
                        api.initializeMedia(cb, retry);
                    }, 1000);
                } else {
                    photoboothTools.console.logDev(
                        'ERROR: Preview: Unable to get user media. Failed retries: ' + retry
                    );
                }
            });
    };

    api.getAndDisplayMedia = function (mode) {
        if (api.stream && api.stream.active) {
            api.changeVideoMode(mode);
        } else {
            api.initializeMedia(() => {
                api.changeVideoMode(mode);
            });
        }
    };

    api.runCmd = function (mode) {
        const dataVideo = {
            play: mode,
            pid: pid
        };

        jQuery
            .post('api/previewCamera.php', dataVideo)
            .done(function (result) {
                photoboothTools.console.log('Preview: ' + dataVideo.play + ' webcam successfully.');
                pid = result.pid;
            })
            // eslint-disable-next-line no-unused-vars
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('ERROR: Preview: Failed to ' + dataVideo.play + ' webcam!');
            });
    };

    api.startVideo = function (mode, retry = 0, maxGetMediaRetry = 3) {
        retryGetMedia = maxGetMediaRetry;
        photoboothTools.console.log('Preview: startVideo mode: ' + mode);

        // Only require mediaDevices for DEVICE mode
        if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
            if (!navigator.mediaDevices || config.preview.mode === PreviewMode.NONE.valueOf()) {
                return;
            }
        }

        switch (mode) {
            case CameraDisplayMode.INIT:
                photoboothTools.console.logDev('Preview: Running preview cmd (INIT).');
                api.runCmd('start');
                break;
            case CameraDisplayMode.BACKGROUND:
                if (
                    config.preview.mode === PreviewMode.DEVICE.valueOf() &&
                    config.commands.preview &&
                    !config.preview.bsm
                ) {
                    photoboothTools.console.logDev('Preview: Running preview cmd (BACKGROUND).');
                    api.runCmd('start');
                }

                if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
                    api.getAndDisplayMedia(CameraDisplayMode.BACKGROUND);
                } else if (config.preview.mode === PreviewMode.WEBRTC.valueOf()) {
                    photoboothTools.console.logDev('Preview: BACKGROUND preview via WebRTC.');
                    video.show();
                    startGo2rtcWebRTC(config.preview.url);
                }
                break;
            case CameraDisplayMode.COUNTDOWN:
                if (config.commands.preview) {
                    if (
                        config.preview.bsm ||
                        (!config.preview.bsm && retry > 0) ||
                        (typeof photoBooth !== 'undefined' && photoBooth.nextCollageNumber > 0)
                    ) {
                        photoboothTools.console.logDev('Preview: Running preview cmd (COUNTDOWN).');
                        api.runCmd('start');
                    }
                }
                if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview at countdown from device cam.');
                    api.getAndDisplayMedia(CameraDisplayMode.COUNTDOWN);
                } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview at countdown from URL.');
                    setTimeout(function () {
                        url.attr('src', addCacheBustingParam(getRootProperty('--background-preview')));
                        url.show();
                    }, config.preview.url_delay);
                } else if (config.preview.mode === PreviewMode.WEBRTC.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview at countdown via WebRTC.');
                    video.show();
                    startGo2rtcWebRTC(config.preview.url);
                }
                break;
            case CameraDisplayMode.TEST:
                if (config.preview.mode === PreviewMode.DEVICE.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview from device cam.');
                    api.getAndDisplayMedia(CameraDisplayMode.TEST);
                } else if (config.preview.mode === PreviewMode.URL.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview from URL.');
                    setTimeout(function () {
                        url.attr('src', addCacheBustingParam(getRootProperty('--background-preview')));
                        url.show();
                    }, config.preview.url_delay);
                } else if (config.preview.mode === PreviewMode.WEBRTC.valueOf()) {
                    photoboothTools.console.logDev('Preview: Preview from WebRTC.');
                    video.show();
                    startGo2rtcWebRTC(config.preview.url);
                }
                break;
            default:
                photoboothTools.console.log('ERROR: Preview: Call for unexpected video mode: ' + mode);
                break;
        }
    };

    api.stopPreview = function () {
        if (config.commands.preview_kill) {
            api.runCmd('stop');
        }
        if (
            config.preview.mode === PreviewMode.DEVICE.valueOf() ||
            config.preview.mode === PreviewMode.URL.valueOf() ||
            config.preview.mode === PreviewMode.WEBRTC.valueOf()
        ) {
            api.stopVideo();
        }
    };

    api.stopVideo = function () {
        loader.css('--stage-background', 'var(--background-countdown-color)');
        if (api.stream) {
            api.stream.getTracks().forEach((track) => track.stop());
            api.stream = null;
        }

        // stop WebRTC
        if (webrtcPeer) {
            try {
                webrtcPeer.getSenders().forEach((sender) => sender.track && sender.track.stop());
                webrtcPeer.close();
            } catch (e) {
                photoboothTools.console.logDev('Preview: stop webrtc error ' + JSON.stringify(e));
            }
            webrtcPeer = null;
        }

        const videoEl = video.get(0);
        if (videoEl) {
            videoEl.srcObject = null;
            videoEl.removeAttribute('src');
            try {
                videoEl.load();
            } catch (e) {
                photoboothTools.console.logDev('Preview: stop videoEl error ' + JSON.stringify(e));
            }
        }
        url.hide();
        url.attr('src', '');
        video.hide();
        pictureFrame.hide();
        collageFrame.hide();
    };

    api.setElements = () => {
        video = $('#preview--video');
        loader = $('.stage[data-stage="loader"]');
        url = $('#preview--ipcam');
        pictureFrame = $('#previewframe--picture');
        collageFrame = $('#previewframe--collage');
    };

    api.init = function () {
        api.setElements();
    };

    return api;
})();

$(function () {
    photoboothPreview.init();
    photoboothTools.console.log('Preview: Preview functions available.');
});
