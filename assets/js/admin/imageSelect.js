// eslint-disable-next-line no-unused-vars
function adminImageSelect(element, path) {
    const parent = element.closest('.adminImageSelection');
    const origin = element.dataset.origin;
    const src = element.src;
    const previewElement = parent.querySelector('.adminImageSelection-preview');
    const textElement = parent.querySelector('.adminImageSelection-text');
    const inputElement = parent.querySelector('input[name="' + path + '"]');

    previewElement.src = src;
    textElement.textContent = origin;
    inputElement.value = origin;

    if (src !== '') {
        previewElement.parentElement.classList.remove('hidden');
    } else {
        previewElement.parentElement.classList.add('hidden');
    }

    const event = new Event('change');
    inputElement.dispatchEvent(event);

    const toggleGeneralCheckbox = (checkboxName) => {
        const checkbox = document.querySelector(`input[name='${checkboxName}'][data-trigger='general']`);
        if (checkbox && checkbox.checked === false) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
    };

    if (path === 'generator-background') {
        toggleGeneralCheckbox('show-background');
    }
    if (path === 'generator-frame') {
        toggleGeneralCheckbox('show-frame');
    }
    parent.classList.remove('isOpen');
}

// eslint-disable-next-line no-unused-vars
function openAdminImageSelect(element) {
    element.closest('.adminImageSelection').classList.add('isOpen');
}

// eslint-disable-next-line no-unused-vars
function closeAdminImageSelect() {
    const selections = document.querySelectorAll('.adminImageSelection');
    selections.forEach((selection) => {
        selection.classList.remove('isOpen');
    });
}
