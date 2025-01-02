// eslint-disable-next-line no-unused-vars
const virtualKeyboard = (function () {
    const api = {};
    const layout = ['0123456789', 'azertyuiop', 'qsdfghjklm', 'wxcvbn@.-_←'];

    let inputElement = null;
    let containerElement = null;

    api.initialize = function (inputSelector, containerSelector) {
        inputElement = document.querySelector(inputSelector);
        containerElement = document.querySelector(containerSelector);

        if (!inputElement || !containerElement) {
            console.error('Invalid input or container selector');
            return;
        }

        this.renderKeyboard();
    };

    api.renderKeyboard = function () {
        const keyboardContainer = document.createElement('div');
        keyboardContainer.id = 'virtual-keyboard';

        const createButton = (key) => {
            const button = document.createElement('button');
            button.textContent = key;
            button.type = 'button';
            button.className = 'keyboard-button';

            if (key === '←') {
                button.classList.add('backspace');
                button.addEventListener('click', () => {
                    inputElement.value = inputElement.value.slice(0, -1);
                    animateButton(button);
                });
            } else {
                button.addEventListener('click', () => {
                    inputElement.value += key;
                    animateButton(button);
                });
            }

            return button;
        };

        const animateButton = (button) => {
            button.classList.add('active');
            setTimeout(() => button.classList.remove('active'), 100);
        };

        layout.forEach((row) => {
            const rowContainer = document.createElement('div');
            rowContainer.className = 'keyboard-row';

            row.split('').forEach((key) => {
                const button = createButton(key);
                rowContainer.appendChild(button);
            });

            keyboardContainer.appendChild(rowContainer);
        });

        containerElement.appendChild(keyboardContainer);
    };

    return api;
})();
