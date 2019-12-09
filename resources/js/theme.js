const style = document.documentElement.style;

style.setProperty('--primary-color', config.colors.primary);
style.setProperty('--secondary-color', config.colors.secondary);
style.setProperty('--font-color', config.colors.font);

style.setProperty('--background-default', config.background_image);
style.setProperty('--background-admin', config.background_admin);
style.setProperty('--background-chroma', config.background_chroma);

if (config.dark_loader) {
    $('#loader').addClass('darkloader');
}

$(function () {
    $('#wrapper').show();
});
