/* globals photoBooth standaloneReturnUrl */
$(function () {
    if (typeof standaloneReturnUrl === 'undefined' || !standaloneReturnUrl) {
        document.querySelector('.gallery__refresh').classList.remove('hidden');
        document.querySelector('.gallery__close').classList.add('hidden');
    }
    photoBooth.openGallery();
});
