// eslint-disable-next-line no-unused-vars
function adminImageSelect(actElem, path) {
    const origin = $(actElem).data('origin');
    const src = $(actElem).attr('src');
    $(actElem).parents('.adminImageSelection').find('.adminImageSelection-preview').attr('src', src);
    $(actElem)
        .parents('.adminImageSelection')
        .find('input[name="' + path + '"]')
        .attr('value', origin);
    $(actElem).parents('.adminImageSelection').removeClass('isOpen');
}

// eslint-disable-next-line no-unused-vars
function openAdminImageSelect(actElem) {
    $(actElem).parents('.adminImageSelection').addClass('isOpen');
}
// eslint-disable-next-line no-unused-vars
function closeAdminImageSelect() {
    $('.adminImageSelection').removeClass('isOpen');
}
