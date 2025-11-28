/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools */
$(function () {
    function initThemes() {
        const apiBase = '../api/themes.php';

        const $nameInput = $('#theme-name');
        const $saveButton = $('#theme-save-btn');
        const $loadButton = $('#theme-load-btn');
        const $deleteButton = $('#theme-delete-btn');
        const $select = $('#theme-select');
        const $currentInput = $('input[name="theme[current]"]');

        function updateLoadButtonState() {
            if ($loadButton.length === 0 || $deleteButton.length === 0) {
                return;
            }

            const selected = $select.val();
            const current = $currentInput.length ? $currentInput.val() : '';
            const isDifferent = selected && current && selected !== current;

            // Highlight load button in green when a different theme is selected
            if (isDifferent) {
                $loadButton.addClass('ring-2 ring-green-500');
            } else {
                $loadButton.removeClass('ring-2 ring-green-500');
            }

            // Disable load button when no theme is selected
            if (!selected) {
                $loadButton.prop('disabled', true);
                $loadButton.addClass('opacity-40 cursor-not-allowed');
            } else {
                $loadButton.prop('disabled', false);
                $loadButton.removeClass('opacity-40 cursor-not-allowed');
            }

            // Disable delete button when no theme is selected
            if (!selected) {
                $deleteButton.prop('disabled', true);
                $deleteButton.addClass('opacity-40 cursor-not-allowed');
            } else {
                $deleteButton.prop('disabled', false);
                $deleteButton.removeClass('opacity-40 cursor-not-allowed');
            }
        }

        function getThemeElements() {
            const elements = [];

            $('[data-theme-field="true"]').each((_, el) => {
                const $el = $(el);
                if ($el.attr('type') === 'hidden') {
                    return;
                }
                elements.push(el);
            });

            return elements;
        }

        function parseFieldName(name) {
            const parts = [];
            const regex = /([^[]+)|\[([^\]]*)\]/g;
            let match;

            while ((match = regex.exec(name)) !== null) {
                const key = match[1] || match[2];
                if (key !== '') {
                    parts.push(key);
                }
            }

            return parts;
        }

        function setNestedValue(target, path, value) {
            if (!Array.isArray(path) || path.length === 0) {
                return;
            }

            let current = target;
            for (let i = 0; i < path.length - 1; i++) {
                const key = path[i];
                if (
                    !Object.prototype.hasOwnProperty.call(current, key) ||
                    typeof current[key] !== 'object' ||
                    current[key] === null
                ) {
                    current[key] = {};
                }
                current = current[key];
            }

            current[path[path.length - 1]] = value;
        }

        function getNestedValue(source, path) {
            if (!Array.isArray(path) || path.length === 0) {
                return undefined;
            }

            let current = source;
            for (let i = 0; i < path.length; i++) {
                const key = path[i];
                if (!current || !Object.prototype.hasOwnProperty.call(current, key)) {
                    return undefined;
                }
                current = current[key];
            }

            return current;
        }

        function collectCurrentTheme() {
            const elements = getThemeElements();
            const data = {};

            elements.forEach((el) => {
                const $el = $(el);
                const name = $el.attr('name');
                if (!name) {
                    return;
                }

                const path = parseFieldName(name);
                if (!path.length) {
                    return;
                }

                let value;
                if (el.tagName === 'INPUT') {
                    if ($el.attr('type') === 'checkbox') {
                        value = $el.is(':checked') ? 'true' : 'false';
                    } else {
                        value = $el.val();
                    }
                } else if (el.tagName === 'SELECT') {
                    value = $el.val();
                } else if (el.tagName === 'TEXTAREA') {
                    value = $el.val();
                }

                setNestedValue(data, path, value);
            });

            return data;
        }

        function applyTheme(theme) {
            if (!theme || typeof theme !== 'object') {
                return;
            }

            const elements = getThemeElements();

            elements.forEach((el) => {
                const $el = $(el);
                const name = $el.attr('name');
                if (!name) {
                    return;
                }

                const path = parseFieldName(name);
                let value = getNestedValue(theme, path);

                // Fallback for older flat themes
                if (typeof value === 'undefined' && Object.prototype.hasOwnProperty.call(theme, name)) {
                    value = theme[name];
                }

                if (typeof value === 'undefined') {
                    return;
                }

                if (el.tagName === 'INPUT') {
                    if ($el.attr('type') === 'checkbox') {
                        $el.prop('checked', value === true || value === 'true');
                    } else {
                        $el.val(value);
                    }
                } else if (el.tagName === 'SELECT') {
                    $el.val(value).trigger('change');
                } else if (el.tagName === 'TEXTAREA') {
                    $el.val(value);
                }

                $el.trigger('change');
            });
        }

        function refreshSelect() {
            const current = $currentInput.length ? $currentInput.val() : '';
            const previousSelected = $select.val();

            $.getJSON(apiBase, { action: 'list', _: Date.now() })
                .done((data) => {
                    const themes = Array.isArray(data.themes) ? data.themes : [];

                    $select.empty();
                    $('<option>', {
                        value: '',
                        text: photoboothTools.getTranslation('theme_choose')
                    }).appendTo($select);

                    themes
                        .slice()
                        .sort()
                        .forEach((key) => {
                            $('<option>', {
                                value: key,
                                text: key,
                                selected: key === previousSelected
                            }).appendTo($select);
                        });

                    if (current && $nameInput.length) {
                        $nameInput.val(current);
                    }

                    updateLoadButtonState();
                })
                .fail(() => {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                });
        }

        $select.on('change', () => {
            updateLoadButtonState();
        });

        $saveButton.on('click', () => {
            const name = $nameInput.val().trim();
            if (!name) {
                return;
            }

            const payload = {
                action: 'save',
                name: name,
                theme: collectCurrentTheme()
            };

            $.ajax({
                url: apiBase,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                dataType: 'json'
            })
                .done(() => {
                    if ($currentInput.length) {
                        $currentInput.val(name);
                    }
                    $select.val(name);
                    $nameInput.val(name);
                    refreshSelect();
                    updateLoadButtonState();
                })
                .fail(() => {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                });
        });

        $loadButton.on('click', () => {
            const selected = $select.val();
            if (!selected) {
                return;
            }

            $.getJSON(apiBase, {
                action: 'get',
                name: selected,
                _: Date.now()
            })
                .done((data) => {
                    if (data.status === 'success' && data.theme) {
                        applyTheme(data.theme);
                        if ($currentInput.length) {
                            $currentInput.val(selected);
                        }
                        if ($nameInput.length) {
                            $nameInput.val(selected);
                        }
                        updateLoadButtonState();
                    }
                })
                .fail(() => {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                });
        });

        $deleteButton.on('click', () => {
            const selected = $select.val();
            if (!selected) {
                return;
            }

            const payload = {
                action: 'delete',
                name: selected
            };

            $.ajax({
                url: apiBase,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                dataType: 'json'
            })
                .done(() => {
                    refreshSelect();
                    updateLoadButtonState();
                })
                .fail(() => {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                });
        });
        refreshSelect();
        updateLoadButtonState();
    }

    photoboothTools.initialize().then(() => {
        initThemes();
    });
});
