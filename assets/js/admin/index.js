/* globals photoboothTools */
$(function () {
    // adminRangeInput
    $(document).on('input', '.adminRangeInput', function () {
        document.querySelector('#' + this.name.replace('[', '\\[').replace(']', '\\]') + '-value span').innerHTML =
            this.value;
    });

    // Localization of toggle button text
    $('.adminCheckbox').on('click', function () {
        if ($(this).find('input').is(':checked')) {
            $('.adminCheckbox-true', this).removeClass('hidden');
            $('.adminCheckbox-false', this).addClass('hidden');
        } else {
            $('.adminCheckbox-true', this).addClass('hidden');
            $('.adminCheckbox-false', this).removeClass('hidden');
        }
    });

    $('input[name=\'final_width\'], input[name=\'final_height\']').each(function () {
        $(this).trigger('change');
    });
});

$(window).on('resize', changeGeneralSetting);

// eslint-disable-next-line no-unused-vars
const shellCommand = function ($mode, $filename = '') {
    const command = {
        mode: $mode,
        filename: $filename
    };

    photoboothTools.console.log('Run' + $mode);

    jQuery
        .post('../api/shellCommand.php', command)
        .done(function (result) {
            photoboothTools.console.log($mode, 'result: ', result);
        })
        .fail(function (xhr, status, result) {
            photoboothTools.console.log($mode, 'result: ', result);
        });
};

$('input[name^=\'generator-\']').change(function () {
    const src = $(this).val();
    const name = $(this).attr('name').replace('generator-', '');
    const recipient = $('#collage_' + name);
    recipient.find('img').attr('src', src);
});

$('input[name=\'final_width\']').change(function () {
    changeGeneralSetting();
});

$('input[name=\'final_height\']').change(function () {
    changeGeneralSetting();
});

$('#loadCurrentConfiguration').click(function () {
    const current_config = JSON.parse($('#current_config').val());
    const collageConfig = config.collage;
    const resolution = parseInt(collageConfig.resolution.slice(0, -3), 10);
    let collage_height = 4 * resolution;
    let collage_width = collage_height * 1.5;
    let layout = current_config;
    if (!Array.isArray(current_config)) {
        collage_width = current_config.width;
        collage_height = current_config.height;
        layout = current_config.layout;
    }
    $('input[name=\'final_width\']').val(collage_width);
    $('input[name=\'final_height\']').val(collage_height);

    for (let i = 0; i < layout.length; i++) {
        let identifier = 'picture-' + i;
        let inputLayout = $('div[data-picture=\'' + identifier + '\']');
        inputLayout.removeClass('hidden');
        let exampleImage = $('#' + identifier);
        exampleImage.removeClass('hidden');

        inputLayout.find('input').each(function (propertyPosition) {
            let isRange = $(this).attr('type') === 'range';
            if (isRange) {
                $(this).parent().find('span:first').text(layout[i][propertyPosition]);
            }
            $(this).val(layout[i][propertyPosition]);
        });
    }
    changeGeneralSetting();
});

$('input[name^=\'picture-x-position-\'').change(handleInputUpdate);
$('input[name^=\'picture-y-position-\'').change(handleInputUpdate);
$('input[name^=\'picture-width-\'').change(handleInputUpdate);
$('input[name^=\'picture-height-\'').change(handleInputUpdate);
$('input[name^=\'picture-rotation-\'').change(handleInputUpdate);

function handleInputUpdate() {
    const modifiedInput = $(this);
    const inputName = modifiedInput.attr('name');
    const settingsContainerId = inputName.split('-').pop();
    updateImage(settingsContainerId);
}

function updateImage(containerId) {
    const settingsContainer = $('div[data-picture=\'picture-' + containerId + '\'');
    settingsContainer.find('input').each(function () {
        let new_value = $(this).val();
        let prop_name = $(this).data('prop');
        if (new_value) {
            changeImageSetting(new_value, prop_name, containerId);
        }
    });
}

function changeImageSetting(new_value, prop_name, index) {
    const canvas_width = $('#result_canvas').width();
    const canvas_height = $('#result_canvas').height();
    const img_container = $('#picture-' + index);

    if (prop_name === 'transform') {
        let contImages = img_container.find('img');
        let angle = -parseInt(new_value, 10);
        contImages.css(prop_name, 'rotate(' + angle + 'deg)');
        contImages.css('transform-origin', angle > 0 ? 'top right' : 'top left');
        contImages.css(angle > 0 ? 'right' : 'left', 0);
        contImages.css(angle < 0 ? 'right' : 'left', '');
        let contW = img_container.width();
        let contH = img_container.height();
        let ar = contW / contH;
        const { imgW, imgH, fromTop } = calculateImgDimensions(contW, contH, angle, ar, 0, {});
        contImages.height(imgH);
        contImages.width(imgW);
        contImages.css('top', fromTop);
    } else {
        let clean_operation = new_value.replace('x', canvas_width).replace('y', canvas_height);
        let processed_value = calculate(tokenize(clean_operation));
        if (new_value == processed_value) {
            // == and NOT === because one is a string and the other is a number
            console.log({ new_value, processed_value });
            let collage_width = $('input[name=\'final_width\']').val();
            let collage_height = $('input[name=\'final_height\']').val();
            if (['width', 'left'].includes(prop_name)) {
                processed_value = (new_value * canvas_width) / collage_width;
            } else if (['height', 'top'].includes(prop_name)) {
                processed_value = (new_value * canvas_height) / collage_height;
            }
        }
        img_container.css(prop_name, processed_value + 'px');
    }
}

function calculateImgDimensions(width, height, angle, aspect_ratio, times, best_guess) {
    if (angle === '0') {
        return { imgW: width, imgH: height };
    }

    const angleCos = Math.cos((angle * Math.PI) / 180);
    let imgW = width / angleCos;
    let imgH = imgW / aspect_ratio;
    let smallCatet = Math.sqrt(Math.pow(imgW, 2) - Math.pow(width, 2));
    let largeCatet = imgH * angleCos;
    let quality = 1 - (largeCatet + smallCatet) / height;

    if (Math.abs(quality) <= 0.001) {
        return { imgW, imgH, fromTop: smallCatet };
    } else {
        if (times < 100) {
            let factor = quality > 0 ? 1.02 : 0.98;
            let new_best_guess = { quality: Math.abs(quality), imgW, imgH, smallCatet };
            if (best_guess) {
                if (best_guess.quality < new_best_guess.quality) {
                    new_best_guess = { ...best_guess };
                }
            }
            return calculateImgDimensions(width * factor, height, angle, aspect_ratio, times + 1, new_best_guess);
        }
    }
    return { imgW: best_guess.imgW, imgH: best_guess.imgH, fromTop: smallCatet };
}

function changeGeneralSetting() {
    const collage_width = $('input[name=\'final_width\']').val();
    const collage_height = $('input[name=\'final_height\']').val();

    const aspect_ratio = collage_width / collage_height;

    $('#result_canvas').css('aspect-ratio', aspect_ratio);

    for (let i = 0; i < 5; i++) {
        updateImage(i);
    }
}

$('#addImage').click(function () {
    const layout_settings = $('#layout_containers').find('div[data-picture^=\'picture-\']:hidden:first');
    layout_settings.removeClass('hidden');
    const img_id = layout_settings.data('picture');
    $('#' + img_id).removeClass('hidden');
});

// eslint-disable-next-line no-unused-vars
function hideImage(containerId) {
    $('div[data-picture=\'' + containerId + '\'').addClass('hidden');
    $('div#' + containerId).addClass('hidden');
}

function tokenize(s) {
    // --- Parse a calculation string into an array of numbers and operators
    const r = [];
    let token = '';
    for (const character of s) {
        if ('^*/+-'.includes(character)) {
            if (token === '' && character === '-') {
                token = '-';
            } else {
                r.push(parseFloat(token), character);
                token = '';
            }
        } else {
            token += character;
        }
    }
    if (token !== '') {
        r.push(parseFloat(token));
    }
    return r;
}

function calculate(tokens) {
    // --- Perform a calculation expressed as an array of operators and numbers
    const operatorPrecedence = [
        { '^': (a, b) => Math.pow(a, b) },
        { '*': (a, b) => a * b, '/': (a, b) => a / b },
        { '+': (a, b) => a + b, '-': (a, b) => a - b }
    ];
    let operator;
    for (const operators of operatorPrecedence) {
        const newTokens = [];
        for (const token of tokens) {
            if (token in operators) {
                operator = operators[token];
            } else if (operator) {
                newTokens[newTokens.length - 1] = operator(newTokens[newTokens.length - 1], token);
                operator = null;
            } else {
                newTokens.push(token);
            }
        }
        tokens = newTokens;
    }
    if (tokens.length > 1) {
        console.log('Error: unable to resolve calculation');
        return tokens;
    } else {
        return tokens[0];
    }
}
