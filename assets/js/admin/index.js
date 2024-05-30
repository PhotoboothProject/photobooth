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

    changeGeneralSetting();
});

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

// triggers
$(window).on('resize', changeGeneralSetting);
$('[data-trigger=\'general\']').change(changeGeneralSetting);
$('[data-trigger=\'image\']').change(handleInputUpdate);

$('#loadCurrentConfiguration').click(function () {
    //loading the configuration just like in the backend
    const current_config = JSON.parse($('#current_config').val());
    const collageConfig = config.collage;
    const textConfig = config.textoncollage;
    const resolution = parseInt(collageConfig.resolution.slice(0, -3), 10);
    let collage_height = 4 * resolution;
    let collage_width = collage_height * 1.5;
    let layout = current_config;
    let backgroundImage = collageConfig.background;
    let backgroundColor = collageConfig.background_color;
    let frameImage = collageConfig.frame;
    let applyFrame = collageConfig.take_frame;
    let text_enabled = textConfig.enabled;
    let font_family = textConfig.font;
    let font_color = textConfig.font_color;
    let font_size = textConfig.font_size;
    let line1 = textConfig.line1;
    let line2 = textConfig.line2;
    let line3 = textConfig.line3;
    let linespace = textConfig.linespace;
    let locationX = textConfig.locationx;
    let locationY = textConfig.locationy;
    let text_rotation = textConfig.rotation;
    if (!Array.isArray(current_config)) {
        collage_width = current_config.width;
        collage_height = current_config.height;
        layout = current_config.layout;
        backgroundImage = current_config.background;
        backgroundColor = '#FFFFFF';
        frameImage = current_config.frame;
        applyFrame = current_config.apply_frame;
        text_enabled = current_config.enabled;
        font_family = current_config.text_font;
        font_color = current_config.text_font_color;
        font_size = current_config.text_font_size;
        line1 = current_config.text_line1;
        line2 = current_config.text_line2;
        line3 = current_config.text_line3;
        linespace = current_config.text_linespace;
        locationX = current_config.text_locationx;
        locationY = current_config.text_locationy;
        text_rotation = current_config.text_rotation;
    }

    //populate the inputs
    $('input[name=\'final_width\']').val(collage_width);
    $('input[name=\'final_height\']').val(collage_height);
    $('input[name=\'background_color\'').val(backgroundColor);

    $('input[name=\'generator-background\'').attr('value', backgroundImage);
    $('input[name=\'generator-background\'')
        .parents('.adminImageSelection')
        .find('.adminImageSelection-preview')
        .attr('src', backgroundImage);

    $('input[name=\'generator-frame\'').attr('value', frameImage);
    $('input[name=\'generator-frame\'')
        .parents('.adminImageSelection')
        .find('.adminImageSelection-preview')
        .attr('src', frameImage);

    $('select[name=\'apply_frame\']').val(applyFrame);

    $('input[name=\'text_enabled\'').prop('checked', text_enabled);
    $('select[name=\'text_font_family\'').val(font_family);
    $('input[name=\'text_font_color\'').attr('value', font_color);
    $('input[name=\'text_font_size\'').attr('value', font_size);
    $('input[name=\'text_line_1\'').attr('value', line1);
    $('input[name=\'text_line_2\'').attr('value', line2);
    $('input[name=\'text_line_3\'').attr('value', line3);
    $('input[name=\'text_line_space\'').attr('value', linespace);
    $('input[name=\'text_location_x\'').attr('value', locationX);
    $('input[name=\'text_location_y\'').attr('value', locationY);
    $('input[name=\'text_rotation\'').attr('value', text_rotation);
    $('input[name=\'text_rotation\'').parent().find('span:first').text(text_rotation);

    //hide images and image settings
    $('#result_canvas').find('div[id^=\'picture-\'').addClass('hidden');
    $('#layout_containers').find('div[data-picture^=\'picture-\'').addClass('hidden');

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

    //start rendering
    changeGeneralSetting();
});

function changeGeneralSetting() {
    const c_width = $('input[name=\'final_width\']').val();
    const c_height = $('input[name=\'final_height\']').val();
    const c_bg_color = $('input[name=\'background_color\']').val();
    const c_bg = $('input[name=\'generator-background\']').val();
    const c_frame = $('input[name=\'generator-frame\']').val();
    const c_apply_frame = $('select[name=\'apply_frame\']').val();
    const c_show_frame = $('input[name=\'show-frame\']').is(':checked');
    const c_show_background = $('input[name=\'show-background\']').is(':checked');

    const c_text_enabled = $('input[name=\'text_enabled\']').is(':checked');
    const c_text_font = $('select[name=\'text_font_family\'] option:selected').html();
    const c_font_color = $('input[name=\'text_font_color\'').val();
    const c_font_size = $('input[name=\'text_font_size\'').val();
    const c_text_1 = $('input[name=\'text_line_1\'').val();
    const c_text_2 = $('input[name=\'text_line_2\'').val();
    const c_text_3 = $('input[name=\'text_line_3\'').val();
    const c_text_space = $('input[name=\'text_line_space\'').val();
    const c_text_top = $('input[name=\'text_location_y\'').val();
    const c_text_left = $('input[name=\'text_location_x\'').val();
    const c_text_rotation = -parseInt($('input[name=\'text_rotation\'').val(), 10);

    const aspect_ratio = c_width / c_height;

    const canvasDOM = $('#result_canvas');

    canvasDOM.css('aspect-ratio', aspect_ratio);
    canvasDOM.css('background-color', c_bg_color);
    canvasDOM.find('div#collage_background img').attr('src', c_bg);
    canvasDOM.find('div#collage_background img').addClass('hidden');

    if (c_show_background) {
        canvasDOM.find('div#collage_background img').removeClass('hidden');
    }

    let collageImgs = canvasDOM.find('div#collage_frame img');
    let pictureFrameImgs = canvasDOM.find('img.picture-frame');
    let allImgs = collageImgs.add(pictureFrameImgs);

    allImgs.attr('src', c_frame).addClass('hidden');

    if (c_show_frame) {
        allImgs.removeClass('hidden');

        if (c_apply_frame === 'always') {
            collageImgs.addClass('hidden');
        } else if (c_apply_frame === 'once') {
            pictureFrameImgs.addClass('hidden');
        } else {
            allImgs.addClass('hidden');
        }
    }

    const canvas_width = canvasDOM.width();
    const canvas_height = canvasDOM.height();
    const adjusted_tfs = (c_font_size * canvas_height) / c_height;
    const adjusted_tt = (c_text_top * canvas_height) / c_height;
    const adjusted_tl = (c_text_left * canvas_width) / c_width;
    const adjusted_tls = (c_text_space * canvas_height) / c_height;
    const real_text_top = (i) => i * adjusted_tls - adjusted_tfs;
    const real_text_left = (i) => i * adjusted_tls;
    const collageTextDOM = $('#collage_text');
    collageTextDOM.css({
        'font-family': c_text_font,
        'font-size': adjusted_tfs + 'pt',
        color: c_font_color,
        top: adjusted_tt + 'px',
        left: adjusted_tl + 'px'
    });
    collageTextDOM
        .find('.text-line-1')
        .css({
            transform: 'rotate(' + c_text_rotation + 'deg)',
            top: real_text_top(0) + 'px'
        })
        .html(c_text_1.replace(/ /g, '\u00a0'));
    collageTextDOM
        .find('.text-line-2')
        .css({
            transform: 'rotate(' + c_text_rotation + 'deg)',
            top: (c_text_rotation > -45 && c_text_rotation < 45 ? real_text_top(1) : real_text_top(0)) + 'px',
            left: (c_text_rotation > -45 && c_text_rotation < 45 ? real_text_left(0) : real_text_left(1)) + 'px'
        })
        .html(c_text_2.replace(/ /g, '\u00a0'));
    collageTextDOM
        .find('.text-line-3')
        .css({
            transform: 'rotate(' + c_text_rotation + 'deg)',
            top: (c_text_rotation > -45 && c_text_rotation < 45 ? real_text_top(2) : real_text_top(0)) + 'px',
            left: (c_text_rotation > -45 && c_text_rotation < 45 ? real_text_left(0) : real_text_left(2)) + 'px'
        })
        .html(c_text_3.replace(/ /g, '\u00a0'));
    collageTextDOM.addClass('hidden');
    if (c_text_enabled) {
        collageTextDOM.removeClass('hidden');
    }

    for (let i = 0; i < 5; i++) {
        updateImage(i);
    }
}

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
        const { imgW, imgH, fromTop } = calculateImgDimensions(contW, contH, angle, ar, 1, {});
        //const minH = Math.min(imgH, contH, contW);
        //console.log({ minH });
        contImages.height(imgH);
        contImages.width(imgW);
        contImages.css('top', Math.min(fromTop, contH));
    } else {
        let clean_operation = new_value.replace('x', canvas_width).replace('y', canvas_height);
        let processed_value = calculate(tokenize(clean_operation));
        if (new_value == processed_value) {
            // == and NOT === because one is a string and the other is a number
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
        return { imgW: width, imgH: height, fromTop: 0 };
    } else if (['-90', '90', -90, 90].includes(angle)) {
        let small_side = Math.min(width, height);
        return { imgW: small_side, imgH: small_side / aspect_ratio, fromTop: small_side };
    }

    const brute_force = angle > -80 && angle < 80 ? 100 : 200;
    const angleCos = Math.cos((angle * Math.PI) / 180);
    let imgW = width / angleCos;
    let imgH = imgW / aspect_ratio;
    let smallCatet = Math.sqrt(Math.pow(imgW, 2) - Math.pow(width, 2));
    let largeCatet = imgH * angleCos;
    let quality = 1 - (largeCatet + smallCatet) / height;

    if (Math.abs(quality) <= 0.001) {
        return { imgW, imgH, fromTop: smallCatet };
    } else {
        if (times < brute_force) {
            let factor = quality > 0 ? 1.05 : 0.95;
            let new_best_guess = { quality: Math.abs(quality), imgW, imgH, smallCatet };
            if (best_guess) {
                if (best_guess.quality < new_best_guess.quality) {
                    new_best_guess = { ...best_guess };
                }
            }
            return calculateImgDimensions(width * factor, height, angle, aspect_ratio, times + 1, new_best_guess);
        }
    }
    console.log({ angle, times, width });
    return { imgW: best_guess.imgW, imgH: best_guess.imgH, fromTop: smallCatet };
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
