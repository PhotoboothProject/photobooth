let timeout = false;
// delay after event is "complete" to run callback
const delay = 500;

$('#slideshow > div:gt(0)').hide();

function resizeFunction() {
    $('.center').css({
        position: 'absolute',
        left: ($(window).width() - $('.center').outerWidth()) / 2,
        top: ($(window).height() - $('.center').outerHeight()) / 2
    });
}

// window.resize event listener
window.addEventListener('resize', () => {
    // clear the timeout
    clearTimeout(timeout);
    // start timing for event "completion"
    timeout = setTimeout(resizeFunction, delay);
});

setInterval(function () {
    $('#slideshow > div:first').fadeOut(1000).next().fadeIn(1000).end().appendTo('#slideshow');
}, config.slideshow.pictureTime);

resizeFunction();
