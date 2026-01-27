/* globals photoboothTools csrf */
/* eslint-env browser */
$(function () {
    initDirtyTracking();

    // adminRangeInput
    $(document).on('input', '.adminRangeInput', function () {
        document.querySelector('#' + this.name.replace('[', '\\[').replace(']', '\\]') + '-value span').innerHTML =
            this.value;
    });

    // Localization of toggle button text
    $('.adminCheckbox').on('click', function () {
        if ($(this).find('input').is(':checked')) {
            $('.adminCheckbox-true', this).removeClass('hidden');
            $('.adminCheckbox-false', this).addClass('hidden');
        } else {
            $('.adminCheckbox-true', this).addClass('hidden');
            $('.adminCheckbox-false', this).removeClass('hidden');
        }
    });
    initCollageLayoutOptions();
});

function initCollageLayoutOptions() {
    if (typeof environment === 'undefined' || !environment.publicFolders || !environment.publicFolders.api) {
        return;
    }
    const layoutSelect = document.querySelector('select[name="collage[layout]"]');
    const layoutGrid = document.querySelector('[data-setting-name="collage[layouts_enabled]"]');
    if (!layoutSelect && !layoutGrid) {
        return;
    }
    fetchCollageLayouts().then(function (layouts) {
        if (!layouts) {
            return;
        }
        if (layoutSelect) {
            updateCollageLayoutSelect(layoutSelect, layouts);
        }
        if (layoutGrid) {
            updateCollageLayoutsEnabled(layoutGrid, layouts);
        }
    });
}

function fetchCollageLayouts() {
    let orientation = 'landscape';
    if (typeof config !== 'undefined' && config.collage && config.collage.orientation) {
        orientation = String(config.collage.orientation);
    }
    const url = environment.publicFolders.api + '/getCollageLayouts.php?orientation=' + encodeURIComponent(orientation);
    return new Promise(function (resolve) {
        $.ajax({
            url: url,
            method: 'GET',
            cache: false,
            dataType: 'json'
        })
            .done(function (data) {
                resolve(Array.isArray(data) ? data : []);
            })
            .fail(function () {
                resolve(null);
            });
    });
}

function updateCollageLayoutSelect(selectEl, layouts) {
    let currentValue = selectEl.value;
    if ((!currentValue || currentValue === '') && typeof config !== 'undefined' && config.collage) {
        currentValue = String(config.collage.layout || '');
    }
    const ordered = [];
    const seen = {};
    layouts.forEach(function (layout) {
        if (!layout || !layout.id) {
            return;
        }
        const id = String(layout.id);
        if (seen[id]) {
            return;
        }
        seen[id] = true;
        ordered.push({
            id: id,
            label: layout.label ? String(layout.label) : id
        });
    });
    if (currentValue && !seen[currentValue]) {
        ordered.push({
            id: currentValue,
            label: currentValue
        });
    }
    selectEl.innerHTML = '';
    ordered.forEach(function (layout) {
        const option = document.createElement('option');
        option.value = layout.id;
        option.textContent = layout.label;
        if (layout.id === currentValue) {
            option.selected = true;
        }
        selectEl.appendChild(option);
    });
}

function updateCollageLayoutsEnabled(gridEl, layouts) {
    const selected = readSelectedLayouts();
    const ordered = [];
    const seen = {};
    layouts.forEach(function (layout) {
        if (!layout || !layout.id) {
            return;
        }
        const id = String(layout.id);
        if (seen[id]) {
            return;
        }
        seen[id] = true;
        ordered.push({
            id: id,
            label: layout.label ? String(layout.label) : id,
            preview: layout.preview ? String(layout.preview) : ''
        });
    });
    selected.forEach(function (id) {
        if (!seen[id]) {
            ordered.push({
                id: id,
                label: id,
                preview: ''
            });
        }
    });
    gridEl.innerHTML = '';
    ordered.forEach(function (layout) {
        const isSelected = selected.indexOf(layout.id) !== -1;
        gridEl.appendChild(buildLayoutToggleOption(layout.id, layout.label, isSelected, layout.preview));
    });
    bindToggleButtons(gridEl);
    setupAllowSelectionGuard(gridEl);
}

function readSelectedLayouts() {
    if (typeof config !== 'undefined' && config.collage && Array.isArray(config.collage.layouts_enabled)) {
        return config.collage.layouts_enabled.map((value) => String(value));
    }
    const selected = [];
    document.querySelectorAll('input[name="collage[layouts_enabled][]"]:checked').forEach(function (checkbox) {
        selected.push(checkbox.value);
    });
    return selected;
}

function buildLayoutToggleOption(layoutId, label, isSelected, previewSvg) {
    const wrapper = document.createElement('label');
    wrapper.className = 'relative cursor-pointer';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.name = 'collage[layouts_enabled][]';
    input.value = layoutId;
    input.className = 'hidden toggle-checkbox';
    if (isSelected) {
        input.checked = true;
    }
    const activeClass = isSelected
        ? 'bg-brand-1 text-white border-brand-1'
        : 'bg-white text-gray-700 border-gray-300 hover:border-brand-1';
    const button = document.createElement('div');
    button.className = 'toggle-button px-3 py-2 border text-sm rounded-md text-center transition-all ' + activeClass;
    button.style.display = 'flex';
    button.style.flexDirection = 'column';
    button.style.alignItems = 'center';
    button.style.gap = '0.5rem';
    if (previewSvg) {
        const preview = document.createElement('div');
        preview.className = 'bg-white border border-gray-200 rounded overflow-hidden';
        preview.style.display = 'flex';
        preview.style.alignItems = 'center';
        preview.style.justifyContent = 'center';
        preview.style.width = '112px';
        preview.style.height = '80px';
        preview.innerHTML = previewSvg;
        const previewSvgNode = preview.querySelector('svg');
        if (previewSvgNode) {
            previewSvgNode.setAttribute('width', '112');
            previewSvgNode.setAttribute('height', '80');
        }
        button.appendChild(preview);
    }
    const labelEl = document.createElement('div');
    labelEl.className = 'text-xs font-medium';
    labelEl.textContent = label;
    button.appendChild(labelEl);
    wrapper.appendChild(input);
    wrapper.appendChild(button);
    return wrapper;
}

function bindToggleButtons(container) {
    container.querySelectorAll('.toggle-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const button = this.nextElementSibling;
            if (!button) {
                return;
            }
            if (this.checked) {
                button.classList.remove('bg-white', 'text-gray-700', 'border-gray-300', 'hover:border-brand-1');
                button.classList.add('bg-brand-1', 'text-white', 'border-brand-1');
            } else {
                button.classList.remove('bg-brand-1', 'text-white', 'border-brand-1');
                button.classList.add('bg-white', 'text-gray-700', 'border-gray-300', 'hover:border-brand-1');
            }
        });
        const button = checkbox.nextElementSibling;
        if (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                checkbox.click();
            });
        }
    });
}

function setupAllowSelectionGuard(container) {
    const allowSelection = document.querySelector('input[type="checkbox"][name="collage[allow_selection]"]');
    if (!allowSelection) {
        return;
    }
    const allowWrapper = allowSelection.closest('.adminCheckbox');
    const warningId = 'collage-allow-selection-warning';
    let warning = document.getElementById(warningId);
    if (!warning && allowWrapper) {
        warning = document.createElement('div');
        warning.id = warningId;
        warning.className = 'mt-2 text-xs text-red-600';
        warning.style.display = 'none';
        if (typeof photoboothTools !== 'undefined' && photoboothTools.getTranslation) {
            warning.textContent = photoboothTools.getTranslation('collage_select_min_two_layouts');
        } else {
            warning.textContent = 'Select at least two layouts to enable selection.';
        }
        allowWrapper.insertAdjacentElement('afterend', warning);
    }
    const syncAllowToggleText = function (isChecked) {
        if (!allowWrapper) {
            return;
        }
        const onLabel = allowWrapper.querySelector('.adminCheckbox-true');
        const offLabel = allowWrapper.querySelector('.adminCheckbox-false');
        if (onLabel && offLabel) {
            if (isChecked) {
                onLabel.classList.remove('hidden');
                offLabel.classList.add('hidden');
            } else {
                onLabel.classList.add('hidden');
                offLabel.classList.remove('hidden');
            }
        }
    };
    const getUniqueSelectedLayoutCount = function () {
        const checked = container.querySelectorAll('input[type="checkbox"][name="collage[layouts_enabled][]"]:checked');
        const values = new Set();
        checked.forEach((checkbox) => values.add(checkbox.value));
        return values.size;
    };
    const guardAllowSelection = function () {
        const count = getUniqueSelectedLayoutCount();
        if (warning) {
            warning.style.display = count < 2 ? 'block' : 'none';
        }
        if (allowSelection.checked && count < 2) {
            allowSelection.checked = false;
            syncAllowToggleText(false);
        }
    };
    allowSelection.addEventListener('change', guardAllowSelection);
    container.querySelectorAll('.toggle-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', guardAllowSelection);
    });
    guardAllowSelection();
}

// eslint-disable-next-line no-unused-vars
const shellCommand = function ($mode, $filename = '') {
    const command = {
        mode: $mode,
        filename: $filename
    };
    if (typeof csrf !== 'undefined') {
        command[csrf.key] = csrf.token;
    }

    photoboothTools.console.log('Run' + $mode);

    jQuery
        .post('../api/shellCommand.php', command)
        .done(function (result) {
            photoboothTools.console.log($mode, 'result: ', result);
        })
        .fail(function (xhr, status, result) {
            photoboothTools.console.log($mode, 'result: ', result);
        });
};

function initDirtyTracking() {
    const $fields = $('.adminSection').find('input, select, textarea').not('[type="hidden"]');

    $fields.each(function () {
        const $el = $(this);
        $el.data('initial', readFieldValue($el));
    });

    $(document).on('change input', '.adminSection input, .adminSection select, .adminSection textarea', function () {
        updateDirtyState($(this));
    });

    $(document).on('click', '.adminSettingCard-revert', function (e) {
        e.preventDefault();
        const $card = $(this).closest('.adminSettingCard');
        revertCard($card);
    });
}

function readFieldValue($el) {
    const el = $el[0];
    if (el.tagName === 'SELECT' && el.multiple) {
        return ($el.val() || []).slice().sort().join('|');
    }
    if (el.type === 'checkbox') {
        return $el.is(':checked') ? '1' : '0';
    }
    return $el.val();
}

function updateDirtyState($el) {
    const initial = $el.data('initial');
    const current = readFieldValue($el);
    const isDirty = initial !== current;
    const $card = $el.closest('.adminSettingCard');

    if ($card.length === 0) {
        return;
    }

    if (isDirty) {
        $card.addClass('ring-2 ring-indigo-200 shadow-indigo-200');
        $el.data('dirty', true);
        ensureRevertButton($card);
    } else {
        $el.data('dirty', false);
        if (
            !$card.find('input,select,textarea').filter(function () {
                return $(this).data('dirty');
            }).length
        ) {
            $card.removeClass('ring-2 ring-indigo-200 shadow-indigo-200');
            removeRevertButton($card);
        }
    }
}

function ensureRevertButton($card) {
    if ($card.find('.adminSettingCard-revert').length) {
        return;
    }
    const btn = $(
        '<button type="button" class="adminSettingCard-revert h-7 w-7 absolute right-2 top-2 text-xs font-semibold text-amber-700 border border-amber-400 rounded-full bg-amber-50 hover:bg-amber-100" title="Revert">' +
            '<i class="fa fa-undo"></i>' +
            '</button>'
    );
    $card.append(btn);
}

function removeRevertButton($card) {
    $card.find('.adminSettingCard-revert').remove();
}

function revertCard($card) {
    $card.find('input,select,textarea').each(function () {
        const $el = $(this);
        const initial = $el.data('initial');
        restoreFieldValue($el, initial);
        $el.data('dirty', false);
    });
    $card.removeClass('ring-2 ring-indigo-400 shadow-indigo-200');
    removeRevertButton($card);
}

function restoreFieldValue($el, value) {
    const el = $el[0];
    if (el.tagName === 'SELECT' && el.multiple) {
        const list = (value || '').split('|').filter((v) => v !== '');
        $el.val(list);
    } else if (el.type === 'checkbox') {
        $el.prop('checked', value === '1');
    } else {
        $el.val(value);
    }
    $el.trigger('change');

    // Keep range labels in sync after revert
    if ($el.hasClass('adminRangeInput')) {
        const labelId = '#' + el.name.replace('[', '\\[').replace(']', '\\]') + '-value span';
        const labelEl = document.querySelector(labelId);
        if (labelEl) {
            labelEl.innerHTML = $el.val();
        }
    }
}
