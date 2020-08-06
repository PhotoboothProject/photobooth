$('#slideshow > div:gt(0)').hide();

setInterval(function () {
    $('#slideshow > div:first').fadeOut(1000).next().fadeIn(1000).end().appendTo('#slideshow');
}, config.slideshow_pictureTime);

$(window).resize(function () {
    $('.center').css({
        position: 'absolute',
        left: ($(window).width() - $('.center').outerWidth()) / 2,
        top: ($(window).height() - $('.center').outerHeight()) / 2
    });
});

$(window).resize();
