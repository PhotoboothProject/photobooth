/* globals L10N */
$(function() {
    const body = $('body');
    body.find('[data-l10n]').each(function (i, item) {
        item = $(item);
        item.html(L10N[item.data('l10n')]);
    });
});
