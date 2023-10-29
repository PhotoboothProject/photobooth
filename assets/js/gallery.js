/* globals photoBooth */
$(function () {
    document.querySelector('.gallery__refresh').classList.remove('hidden');
    document.querySelector('.gallery__close').classList.add('hidden');
    photoBooth.openGallery();
});
