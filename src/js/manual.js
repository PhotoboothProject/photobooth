$(function () {
    $('.panel-heading').on('click', function () {
        const panel = $(this).parents('.panel');
        const others = $(this).parents('.accordion').find('.open').not(panel);

        others.removeClass('open init');

        panel.toggleClass('open');
        panel.find('.panel-body').slideToggle();

        others.find('.panel-body').slideUp('fast');
    });
});
