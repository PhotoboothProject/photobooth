/* globals photoboothTools csrf */

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
    const keypadPinElement = document.getElementById('keypad_pin');
    if (!keypadPinElement) {
        return;
    }

    const key = event.key || String.fromCharCode(event.which || event.keyCode);

    if (/^[0-9]$/.test(key)) {
        keypadAdd(parseInt(key, 10));
    } else if (key === 'Escape') {
        keypadClear();
    } else if (key === 'Backspace' || key === 'Delete') {
        keypadRemoveLastValue();
    }
});

function checkKeypadPin(pin) {
    $('.keypadLoader').removeClass('hidden');
    $('.keypadLoader').addClass('flex');

    $.ajax({
        url: environment.publicFolders.api + '/controller.php',
        dataType: 'json',
        type: 'POST',
        data: {
            controller: 'keypadLogin',
            pin: pin,
            [csrf.key]: csrf.token
        },

        success: (data) => {
            if (data.blocked) {
                const waitSeconds = data.retry_after || 0;
                $('.keypadLoader').addClass('hidden').removeClass('flex');
                const msg = data.message || photoboothTools.getTranslation('error');
                $('#keypad_message').text(msg + (waitSeconds ? ' (' + waitSeconds + 's)' : ''));
                $('.keypad_keybox').addClass('error');
                $('.keypad_key').addClass('error');
                // Keep message visible; user must wait out the window
                keypadClear();
                return;
            }

            if (data.state == true) {
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
            $('.keypadLoader').removeClass('flex');
        }
    });
}
