/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools csrf */
$(function () {
    function initThemes() {
        const apiBase = environment.publicFolders.api + '/themes.php';

        const $nameInput = $('#theme-name');
        const $saveButton = $('#theme-save-btn');
        const $loadButton = $('#theme-load-btn');
        const $deleteButton = $('#theme-delete-btn');
        const $exportButton = $('#theme-export-btn');
        const $importButton = $('#theme-import-btn');
        const $importInput = $('#theme-import-input');
        const $select = $('#theme-select');
        const $currentInput = $('input[name="theme[current]"]');
        let lastAppliedThemeSnapshot = null;

        function snapshotTheme() {
            lastAppliedThemeSnapshot = JSON.stringify(collectCurrentTheme());
        }

        function hasUnsavedChanges() {
            if (lastAppliedThemeSnapshot === null) {
                return false;
            }

            return JSON.stringify(collectCurrentTheme()) !== lastAppliedThemeSnapshot;
        }

        function updateLoadButtonState() {
            if ($loadButton.length === 0 || $deleteButton.length === 0 || $exportButton.length === 0) {
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

            const disabledClasses = 'opacity-40 cursor-not-allowed';

            const toggleButton = (button, isDisabled) => {
                button.prop('disabled', isDisabled);
                button.toggleClass(disabledClasses, isDisabled);
            };

            toggleButton($deleteButton, !selected);
            toggleButton($exportButton, !selected);
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

                if (el.tagName === 'INPUT') {
                    const isCheckbox = $el.attr('type') === 'checkbox';
                    const normalized = typeof value === 'undefined' ? (isCheckbox ? false : '') : value;
                    if (isCheckbox) {
                        $el.prop('checked', normalized === true || normalized === 'true');
                    } else {
                        $el.val(normalized);
                    }
                } else if (el.tagName === 'SELECT') {
                    $el.val(typeof value === 'undefined' ? '' : value).trigger('change');
                } else if (el.tagName === 'TEXTAREA') {
                    $el.val(typeof value === 'undefined' ? '' : value);
                }

                $el.trigger('change');
            });
        }

        function refreshSelect(desiredSelection = null) {
            const current = $currentInput.length ? $currentInput.val() : '';
            const previousSelected = $select.val();
            const targetSelection = desiredSelection ?? previousSelected;

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
                                selected: key === previousSelected || key === targetSelection
                            }).appendTo($select);
                        });

                    if (current && $nameInput.length) {
                        $nameInput.val(current);
                    }

                    if (targetSelection) {
                        $select.val(targetSelection);
                    }

                    updateLoadButtonState();
                    if (lastAppliedThemeSnapshot === null) {
                        snapshotTheme();
                    }
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

            const themeExists = Array.from($select.find('option')).some((opt) => opt.value === name);
            if (themeExists) {
                const confirmMessage = photoboothTools.getTranslation('theme_override_confirm').replace('%s', name);
                const confirmed = window.confirm(confirmMessage);
                if (!confirmed) {
                    return;
                }
            }

            const payload = {
                action: 'save',
                name: name,
                theme: collectCurrentTheme(),
                [csrf.key]: csrf.token
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
                    snapshotTheme();
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

            if (hasUnsavedChanges()) {
                const confirmMessage = photoboothTools.getTranslation('theme_unsaved_confirm');
                const confirmed = window.confirm(confirmMessage);
                if (!confirmed) {
                    return;
                }
            }

            $.getJSON(apiBase, {
                action: 'get',
                name: selected,
                _: Date.now(),
                [csrf.key]: csrf.token
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
                        snapshotTheme();
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
                name: selected,
                [csrf.key]: csrf.token
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

        $exportButton.on('click', () => {
            const selected = $select.val();
            if (!selected) {
                return;
            }

            const url = `${apiBase}?action=export&name=${encodeURIComponent(selected)}&_=${Date.now()}`;
            const sep = url.includes('?') ? '&' : '?';
            const csrfUrl = `${url}${sep}${csrf.key}=${encodeURIComponent(csrf.token)}`;
            const link = document.createElement('a');
            link.href = csrfUrl;
            link.download = `${selected}.zip`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        $importButton.on('click', () => {
            if ($importInput.length) {
                $importInput.trigger('click');
            }
        });

        $importInput.on('change', function onImportChange() {
            const file = this.files ? this.files[0] : null;
            if (!file) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'import');
            formData.append('theme_zip', file);
            formData.append(csrf.key, csrf.token);

            const desiredName = $nameInput.length ? $nameInput.val().trim() : '';
            if (desiredName) {
                formData.append('name', desiredName);
            }

            $.ajax({
                url: apiBase,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            })
                .done((data) => {
                    if (data.status !== 'success') {
                        photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                        return;
                    }

                    const importedName = data.name || desiredName;
                    if (data.theme) {
                        applyTheme(data.theme);
                        snapshotTheme();
                    }

                    if ($currentInput.length && importedName) {
                        $currentInput.val(importedName);
                    }
                    if ($nameInput.length && importedName) {
                        $nameInput.val(importedName);
                    }

                    refreshSelect(importedName);
                    updateLoadButtonState();
                })
                .fail(() => {
                    photoboothTools.overlay.showError(photoboothTools.getTranslation('error'));
                })
                .always(() => {
                    $importInput.val('');
                });
        });
        refreshSelect();
        updateLoadButtonState();
    }

    photoboothTools.initialize().then(() => {
        initThemes();
    });
});
