// eslint-disable-next-line no-unused-vars
function adminVideoSelect(element, path) {
    const parent = element.closest('.adminVideoSelection');
    const origin = element.dataset.origin;
    const src = element.src;
    const previewElement = parent.querySelector('.adminVideoSelection-preview');
    const textElement = parent.querySelector('.adminVideoSelection-text');
    const inputElement = parent.querySelector('input[name="' + path + '"]');

    previewElement.src = src;
    textElement.textContent = origin;
    inputElement.value = origin;

    const event = new Event('change');
    inputElement.dispatchEvent(event);
    parent.classList.remove('isOpen');
}

// eslint-disable-next-line no-unused-vars
function openAdminVideoSelect(element) {
    element.closest('.adminVideoSelection').classList.add('isOpen');
}

// eslint-disable-next-line no-unused-vars
function closeAdminVideoSelect() {
    const selections = document.querySelectorAll('.adminVideoSelection');
    selections.forEach((selection) => {
        selection.classList.remove('isOpen');
    });
}
