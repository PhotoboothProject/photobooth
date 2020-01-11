/* exported login */

function showPassword() {
    const x = document.getElementById('password');

    if (x.type === 'password') {
        x.type = 'text';
    } else {
        x.type = 'password';
    }
}

$('.password-toggle').on('click', function () {
    showPassword();
    $('.password-toggle').toggleClass('fa-eye fa-eye-slash');
});
