/* exported login */

function showPassword() {
    const x = document.getElementById('password');

    if (x.type === 'password') {
        x.type = 'text';
        $('.icon').removeClass('fa-eye-slash');
        $('.icon').addClass('fa-eye');
    } else {
        x.type = 'password';
        $('.icon').removeClass('fa-eye');
        $('.icon').addClass('fa-eye-slash');
    }
}

$('.password-visible').on('click', function () {
    showPassword();
});
