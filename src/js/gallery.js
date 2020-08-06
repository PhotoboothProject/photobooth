/* globals photoBooth */
$(function () {
    const reloadElement = $('<a class="gallery__reload">');
    reloadElement.append('<i class="fa fa-refresh"></i>');
    reloadElement.attr('href', '#');
    reloadElement.on('click', () => photoBooth.reloadPage());
    reloadElement.appendTo('.gallery__header');
    $('.gallery__close').hide();

    photoBooth.openGallery();
});
