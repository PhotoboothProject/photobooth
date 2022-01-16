/* globals photoBooth photoboothTools */
$(function () {
    const reloadElement = $('<a class="gallery__reload rotaryfocus">');
    reloadElement.append('<i class="fa fa-refresh"></i>');
    reloadElement.attr('href', '#');
    reloadElement.on('click', () => photoboothTools.reloadPage());
    reloadElement.appendTo('.gallery__header');
    $('.gallery__close').hide();

    photoBooth.openGallery();
});
