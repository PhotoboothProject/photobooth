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

        function collectCurrentTheme() {
            const elements = getThemeElements();
            const data = {};

            elements.forEach((el) => {
                const $el = $(el);
                const name = $el.attr('name');
                if (!name) {
                    return;
                }

                if (el.tagName === 'INPUT') {
                    if ($el.attr('type') === 'checkbox') {
                        data[name] = $el.is(':checked') ? 'true' : 'false';
                    } else {
                        data[name] = $el.val();
                    }
                } else if (el.tagName === 'SELECT') {
                    data[name] = $el.val();
                } else if (el.tagName === 'TEXTAREA') {
                    data[name] = $el.val();
                }
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
                if (!name || !Object.prototype.hasOwnProperty.call(theme, name)) {
                    return;
                }

                const value = theme[name];

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
