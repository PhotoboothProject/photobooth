// eslint-disable-next-line no-unused-vars
function adminFontSelect(element, path, fontclass) {
    const parent = element.closest('.adminFontSelection');
    const origin = element.dataset.origin;
    const src = element.src;
    const previewElement = parent.querySelector('.adminFontSelection-preview');
    const textElement = parent.querySelector('.adminFontSelection-text');
    const inputElement = parent.querySelector('input[name="' + path + '"]');

    previewElement.src = src;
    textElement.textContent = origin;
    inputElement.value = origin;
    inputElement.setAttribute('data-fontclass', fontclass);

    const event = new Event('change');
    inputElement.dispatchEvent(event);
    parent.classList.remove('isOpen');
}

// eslint-disable-next-line no-unused-vars
function openAdminFontSelect(element) {
    element.closest('.adminFontSelection').classList.add('isOpen');
}

// eslint-disable-next-line no-unused-vars
function closeAdminFontSelect() {
    const selections = document.querySelectorAll('.adminFontSelection');
    selections.forEach((selection) => {
        selection.classList.remove('isOpen');
    });
}
