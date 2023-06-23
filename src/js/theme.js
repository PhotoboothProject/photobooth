const style = document.documentElement.style;

style.setProperty('--primary-color', config.colors.primary);
style.setProperty('--primary-light-color', config.colors.primary_light);
style.setProperty('--secondary-color', config.colors.secondary);
style.setProperty('--highlight-color', config.colors.highlight);
style.setProperty('--font-color', config.colors.font);
style.setProperty('--secondary-font-color', config.colors.font_secondary);
style.setProperty('--button-font-color', config.colors.button_font);
style.setProperty('--start-font-color', config.colors.start_font);
style.setProperty('--countdown-color', config.colors.countdown);
style.setProperty('--background-countdown-color', config.colors.background_countdown);
style.setProperty('--cheese-color', config.colors.cheese);
style.setProperty('--panel-color', config.colors.panel);
style.setProperty('--border-color', config.colors.border);
style.setProperty('--box-color', config.colors.box);
style.setProperty('--gallery-button-color', config.colors.gallery_button);
style.setProperty('--background-default', config.background.defaults);
style.setProperty('--background-chroma', config.background.chroma);
style.setProperty('--background-url', config.preview.url);
style.setProperty('--fontSize', config.ui.font_size);
style.setProperty('--preview-rotation', config.preview.rotation);
style.setProperty('--picture-frame', config.picture.htmlframe);
style.setProperty('--collage-frame', config.collage.htmlframe);

$(function () {
    $('#wrapper').show();
});
