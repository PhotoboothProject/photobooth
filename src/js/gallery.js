/* globals photoBooth photoboothTools */
$(function () {
    const reloadElement = $('<a class="btn btn--' + config.ui.button + ' gallery__reload rotaryfocus">');
    reloadElement.append('<i class="' + config.icons.refresh + '"></i>');
    reloadElement.attr('href', '#');
    reloadElement.on('click', () => photoboothTools.reloadPage());
    reloadElement.appendTo('.gallery__header');
    $('.gallery__close').hide();

    photoBooth.openGallery();
});
