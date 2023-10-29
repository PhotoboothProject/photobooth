// eslint-disable-next-line no-unused-vars
function openToast(msg, type = 'isSuccess', duration = 2000) {
    $('.adminToast').addClass('isActive ' + type);
    $('.adminToast').find('.headline').html(msg);

    if (type == 'isError') {
        $('.adminToast-icon').removeClass('fa-check');
        $('.adminToast-icon').addClass('fa-times');
    }

    setTimeout(function () {
        $('.adminToast').removeClass('isActive');
    }, duration);
}
