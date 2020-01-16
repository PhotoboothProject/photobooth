const style = document.documentElement.style;

style.setProperty('--primary-color', config.colors.primary);
style.setProperty('--secondary-color', config.colors.secondary);
style.setProperty('--font-color', config.colors.font);
style.setProperty('--button-font-color', config.colors.button_font);
style.setProperty('--start-font-color', config.colors.start_font);
style.setProperty('--countdown-color', config.colors.countdown);
style.setProperty('--background-countdown-color', config.colors.background_countdown);
style.setProperty('--cheese-color', config.colors.cheese);
style.setProperty('--panel-color', config.colors.panel);
style.setProperty('--hover-panel-color', config.colors.hover_panel);
style.setProperty('--border-color', config.colors.border);
style.setProperty('--box-color', config.colors.box);
style.setProperty('--gallery-button-color', config.colors.gallery_button);
style.setProperty('--background-default', config.background_image);
style.setProperty('--background-admin', config.background_admin);
style.setProperty('--background-chroma', config.background_chroma);
style.setProperty('--fontSize', config.font_size);

$(function () {
    $('#wrapper').show();
});
