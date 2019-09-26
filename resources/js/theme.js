if (theme === 'bluegray') {
    var style = document.documentElement.style;

    style.setProperty('--primary-color', '#669db3');
    style.setProperty('--secondary-color', '#2e535e');
    style.setProperty('--font-color', '#f0f6f7');

    $('#wrapper').addClass('bluegray-bg');
}

$(function() {
    $('#wrapper').show();
});