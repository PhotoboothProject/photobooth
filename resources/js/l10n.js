/* exported l10n */
/* globals L10N */
function l10n(elem) {
    elem = $(elem || 'body');
    elem.find('[data-l10n]').each(function () {
        const item = $(this);
        const key = item.data('l10n');
        const translation = L10N[key];

        if (!translation) {
            console.warn('No translation for: ', key);
        }

        item.html(translation || key);
    });
}

$(function () {
    l10n();
});
