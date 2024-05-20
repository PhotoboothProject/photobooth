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

    $('#final_width, #final_height').each(function (index) {
        $(this).trigger('change');
    });
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

// eslint-disable-next-line no-unused-vars
function selectImage(actElem, element) {
    const origin = $(actElem).data('origin');
    const src = $(actElem).attr('src');
    const recipient = $('#collage_' + element);
    recipient.find('img').attr('src', src);
}

// eslint-disable-next-line no-unused-vars
function changeImageSetting(inpElem, raw_property) {
    const new_value = $(inpElem).val();
    const collage_width = $('#result_canvas').width();
    const collage_height = $('#result_canvas').height();
    const prop_array = raw_property.split('-');
    const index = prop_array[1];
    const prop_name = prop_array[0];
    const img_element = $('#picture-' + index);

    let processed_value = 'rotate(' + new_value + 'deg)';
    if (prop_name !== 'transform') {
        processed_value = eval(new_value.replace('x', collage_width).replace('y', collage_height)) + 'px';
    }

    img_element.css(prop_name, processed_value);
}

// eslint-disable-next-line no-unused-vars
function changeGeneralSetting() {
    const component_width = $('#result_canvas').width();
    const collage_width = $('#final_width').val();
    const collage_height = $('#final_height').val();

    const aspect_ratio = collage_width / collage_height;

    let component_height = component_width / aspect_ratio;

    $('#result_canvas').css('height', component_height + 'px');

    $('#layout_containers')
        .find('input')
        .each(function (index) {
            $(this).trigger('change');
        });
}

// eslint-disable-next-line no-unused-vars
function addImage() {
    console.log('addImage');
    const layout_settings = $('#layout_containers').find('div:hidden:first');
    layout_settings.removeClass('hidden');
    const img_id = layout_settings.data('picture');
    $('#' + img_id).removeClass('hidden');
}
