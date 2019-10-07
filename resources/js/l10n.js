/* globals L10N */
$(function() {
    const body = $('body');
    body.find('[data-l10n]').each(function () {
        const item = $(this);
        const key = item.data('l10n');
        const translation = L10N[key];

        if (!translation) {
            console.warn('No translation for: ', key);
        }

        item.html(translation || key);
    });
});
