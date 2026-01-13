/**
 * Screensaver module for Photobooth.
 * Exposes createScreensaver(deps) which returns the screensaver API.
 */
(function (window, $) {
    window.createScreensaver = function createScreensaver(deps) {
        const {
            config,
            environment,
            startPage,
            overlay,
            videoEl,
            imageEl,
            textTop,
            textCenter,
            textBottom,
            screensaverEnabled,
            screensaverMode,
            screensaverTimeoutMs,
            screensaverSwitchMs,
            urlSafe,
            galleryFallbackSource,
            photoboothTools
        } = deps;

        const fallbackSource = galleryFallbackSource || (() => config.screensaver.image_source || '');

        let screensaverTimeout;
        let screensaverSwitchTimeout;
        let screensaverFlip = false;
        let screensaverLastGallerySource = '';

        const api = {};

        api.resolveSource = function resolveSource() {
            const base = environment.publicFolders.api;
            switch (screensaverMode) {
                case 'video':
                    return config.screensaver.video_source;
                case 'image':
                    return config.screensaver.image_source;
                case 'folder':
                    return base + '/randomImg.php?dir=' + encodeURIComponent('screensavers') + '&t=' + Date.now();
                case 'gallery': {
                    const anchors = $('#galimages a');
                    if (anchors.length) {
                        const randomIndex = Math.floor(Math.random() * anchors.length);
                        return $(anchors[randomIndex]).attr('href');
                    }
                    return base + '/randomImg.php?dir=' + 'data/images' + '&t=' + Date.now();
                }
                default:
                    return '';
            }
        };

        api.hide = function hide() {
            if (!overlay.length) {
                return;
            }
            overlay.removeClass('screensaver-overlay--active');
            overlay.css('display', 'none');
            startPage.removeClass('stage--screensaver');
            clearInterval(screensaverSwitchTimeout);

            if (videoEl.length) {
                const vid = videoEl.get(0);
                vid.pause();
                vid.currentTime = 0;
                videoEl.attr('src', '');
            }
            imageEl.hide().attr('src', '');
            textTop.text('').hide();
            textCenter.text('').hide();
            textBottom.text('').hide();
        };

        api.toggleGalleryText = function toggleGalleryText() {
            const screensaverText = config.screensaver.text;
            const baseColor = config.screensaver.text_backdrop_color || '#202020';
            const alpha = parseFloat(config.screensaver.text_backdrop_opacity);
            const safeAlpha = Number.isFinite(alpha) ? Math.min(Math.max(alpha, 0), 1) : 0.55;
            const hex = baseColor.replace('#', '');
            const fullHex =
                hex.length === 3
                    ? hex
                          .split('')
                          .map((c) => c + c)
                          .join('')
                    : hex;
            const r = parseInt(fullHex.substring(0, 2), 16) || 0;
            const g = parseInt(fullHex.substring(2, 4), 16) || 0;
            const b = parseInt(fullHex.substring(4, 6), 16) || 0;
            const screensaverBackdrop = `rgba(${r}, ${g}, ${b}, ${safeAlpha})`;
            const buildEventText = () => {
                const left = config.event.textLeft || '';
                const right = config.event.textRight || '';
                const symbolClass = config.event.symbol || '';
                const symbol = symbolClass ? `<i class="fa ${symbolClass}" aria-hidden="true"></i>` : '';
                return [left, symbol, right].filter(Boolean).join(' ').trim();
            };

            const eventText = buildEventText();
            const showEvent = screensaverMode === 'gallery';
            const hasScreensaver = !!screensaverText;
            const hasEvent = showEvent && !!eventText;

            const position = config.screensaver.text_position || 'center';
            const showTop = position === 'top-center';
            const showCenter = position === 'center';
            const showBottom = position === 'bottom-center';

            const resetSlots = () => {
                textTop.removeClass('screensaver-overlay__text--center').hide().text('');
                textCenter.hide().text('');
                textBottom.hide().text('');
            };

            const applyContent = ($el, content, isHtml = false) => {
                if (isHtml) {
                    $el.html(content);
                } else {
                    $el.text(content);
                }
            };

            const setSlot = (content, isHtml = false) => {
                resetSlots();
                [textTop, textCenter, textBottom].forEach(($el) => {
                    $el.css('background', screensaverBackdrop);
                });
                if (showCenter) {
                    applyContent(textCenter, content, isHtml);
                    textCenter.show();
                    return;
                }
                if (showTop) {
                    applyContent(textTop, content, isHtml);
                    textTop.show();
                }
                if (showBottom) {
                    applyContent(textBottom, content, isHtml);
                    textBottom.show();
                }
            };

            if (hasScreensaver && hasEvent) {
                if (screensaverFlip) {
                    setSlot(screensaverText);
                    if (showTop && showBottom) {
                        applyContent(textBottom, eventText, true);
                        textBottom.show();
                    } else if (showCenter || showTop) {
                        applyContent(textBottom, eventText, true);
                        textBottom.show();
                    } else {
                        applyContent(textTop, eventText, true);
                        textTop.show();
                    }
                } else {
                    setSlot(eventText, true);
                    if (showTop && showBottom) {
                        applyContent(textBottom, screensaverText);
                        textBottom.show();
                    } else if (showCenter || showTop) {
                        applyContent(textBottom, screensaverText);
                        textBottom.show();
                    } else {
                        applyContent(textTop, screensaverText);
                        textTop.show();
                    }
                }
            } else {
                const singleText = hasScreensaver ? screensaverText : hasEvent ? eventText : '';
                if (singleText) {
                    setSlot(singleText, hasEvent);
                } else {
                    resetSlots();
                }
            }

            screensaverFlip = !screensaverFlip;
        };

        api.stepScreensaver = function stepScreensaver() {
            const mode = overlay.data('mode') || screensaverMode;
            photoboothTools.console.logDev('Screensaver: step in mode \'' + mode + '\'');

            let nextSource = api.resolveSource();
            if (!nextSource && mode === 'gallery') {
                nextSource = galleryFallbackSource();
            }

            if (mode === 'gallery' || mode === 'folder') {
                let guard = 5;
                while (nextSource === screensaverLastGallerySource && guard > 0) {
                    nextSource = api.resolveSource();
                    guard--;
                }
                screensaverLastGallerySource = nextSource;
            }

            photoboothTools.console.logDev('Screensaver: next source \'' + nextSource + '\'');
            if (nextSource) {
                if (mode === 'folder') {
                    overlay.css('background-image', nextSource ? `url(${urlSafe(nextSource)})` : 'none');
                } else if (mode === 'gallery') {
                    imageEl
                        .one('error', function () {
                            const fallback = galleryFallbackSource();
                            if (fallback && fallback !== nextSource) {
                                screensaverLastGallerySource = fallback;
                                $(this).attr('src', urlSafe(fallback));
                            }
                        })
                        .attr('src', urlSafe(nextSource))
                        .show();
                }
            }
            if (mode === 'gallery') {
                api.toggleGalleryText();
            }
        };

        api.show = function show(force = false) {
            if ((!force && !screensaverEnabled) || !overlay.length) {
                return;
            }
            const mode = screensaverMode;
            if (!startPage.hasClass('stage--active')) {
                api.resetTimer();
                return;
            }

            if (mode === 'gallery') {
                overlay.addClass('screensaver-overlay--gallery');
                const width = config.screensaver.gallery_width || 800;
                imageEl.css('width', width + 'px');
            } else {
                overlay.removeClass('screensaver-overlay--gallery');
                imageEl.css('width', '');
            }

            const source = api.resolveSource();
            const finalSource = source || fallbackSource();
            if (!finalSource) {
                api.resetTimer();
                return;
            }
            if (mode === 'gallery') {
                screensaverLastGallerySource = finalSource;
            }

            if (mode === 'video') {
                overlay.css('background-image', 'none');
                videoEl.attr('src', urlSafe(finalSource));
                videoEl.show();
                const vid = videoEl.get(0);
                vid.play().catch((err) => {
                    photoboothTools.console.logDev('Idle video play failed: ' + err);
                });
                imageEl.hide();
                api.toggleGalleryText();
            } else if (mode === 'gallery') {
                videoEl.hide();
                overlay.css('background-image', 'none');
                imageEl
                    .one('error', function () {
                        const fallback = fallbackSource();
                        if (fallback && fallback !== finalSource) {
                            screensaverLastGallerySource = fallback;
                            $(this).attr('src', urlSafe(fallback));
                        }
                    })
                    .attr('src', urlSafe(finalSource))
                    .show();
                api.toggleGalleryText();
            } else {
                videoEl.hide();
                imageEl.hide();
                api.toggleGalleryText();
                overlay.css('background-image', finalSource ? `url(${urlSafe(finalSource)})` : 'none');
                overlay.css('background-size', 'cover');
            }

            startPage.addClass('stage--screensaver');
            overlay.addClass('screensaver-overlay--active');
            overlay.css('display', 'flex');

            clearInterval(screensaverSwitchTimeout);
            if ((mode === 'folder' || mode === 'gallery') && screensaverSwitchMs > 0) {
                screensaverSwitchTimeout = setInterval(function nextIdleFrame() {
                    api.stepScreensaver();
                }, screensaverSwitchMs);
            }
        };

        api.resetTimer = function resetTimer() {
            if (!screensaverEnabled) {
                return;
            }
            clearTimeout(screensaverTimeout);
            api.hide();
            screensaverTimeout = setTimeout(api.show, screensaverTimeoutMs);
        };

        return api;
    };
})(window, jQuery);
