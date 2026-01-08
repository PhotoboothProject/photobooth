/* globals photoboothTools csrf */
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
});

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
