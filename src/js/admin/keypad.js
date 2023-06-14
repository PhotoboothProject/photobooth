function keypadAdd(value) {
    const keypadPin = $('#keypad_pin').html();
    const newPin = keypadPin + value;
    const pinLength = newPin.length;
    $('#keypad_pin').html(newPin);

    $('.keypad_keybox.active').addClass('checked');
    $('.keypad_keybox').find('.keypad_key.active').addClass('checked');

    $('.keypad_keybox.active').addClass('checked');
    $('.keypad_keybox').find('.keypad_key.active').addClass('checked');

    $('.keypad_keybox').removeClass('active');
    $('.keypad_keybox').find('.keypad_key').removeClass('active');

    $('.keypad_keybox').eq(pinLength).addClass('active');
    $('.keypad_keybox').eq(pinLength).find('.keypad_key').addClass('active');

    if (pinLength == 4) {
        checkKeypadPin(newPin);
    }
}

function keypadRemoveLastValue() {
    const newPin = $('#keypad_pin').html().slice(0, -1);
    const pinLength = newPin.length;
    $('#keypad_pin').html(newPin);

    $('.keypad_keybox')
        .eq(pinLength + 1)
        .removeClass('active')
        .removeClass('checked');
    $('.keypad_keybox')
        .eq(pinLength + 1)
        .find('.keypad_key')
        .removeClass('active')
        .removeClass('checked');

    $('.keypad_keybox').eq(pinLength).addClass('active');
    $('.keypad_keybox').eq(pinLength).find('.keypad_key').addClass('active');
    $('.keypad_keybox').eq(pinLength).removeClass('checked');
    $('.keypad_keybox').eq(pinLength).find('.keypad_key').removeClass('checked');
}

function keypadClear() {
    $('#keypad_pin').html('');
    $('.keypad_keybox').removeClass('active');
    $('.keypad_keybox').find('.keypad_key').removeClass('active');
    $('.keypad_keybox').removeClass('checked');
    $('.keypad_keybox').find('.keypad_key').removeClass('checked');
    $('.keypad_keybox').eq(0).addClass('active');
    $('.keypad_keybox').eq(0).find('.keypad_key').addClass('active');
}

document.addEventListener('keydown', function (event) {
    if (event.which >= 48 && event.which <= 57) {
        keypadAdd(event.which - 48);
    } else if (event.which == 27) {
        keypadClear();
    } else if (event.which == 8) {
        keypadRemoveLastValue();
    }
});

function checkKeypadPin(pin) {
    $('.keypadLoader').removeClass('hidden');

    $.ajax({
        url: config.foldersJS.api + '/controller.php',
        type: 'POST',
        data: {
            controller: 'keypadLogin',
            pin: pin
        },

        success: function (e) {
            const jsonData = $.parseJSON(e);

            if (jsonData.state == true) {
                window.location.href = '../admin';
            } else {
                $('.keypad_keybox').addClass('error');
                $('.keypad_key').addClass('error');
                setTimeout(function () {
                    $('.keypadLoader').addClass('hidden');
                }, 100);
                setTimeout(function () {
                    $('.keypad_keybox').removeClass('error');
                    $('.keypad_key').removeClass('error');
                    keypadClear();
                }, 555);
            }
        },

        error: function () {
            keypadClear();
            $('.keypadLoader').addClass('hidden');
        }
    });
}
