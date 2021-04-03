/* globals Translator */
const translator = new Translator({
    persist: false,
    defaultLanguage: 'en',
    detectLanguage: false,
    registerGlobally: 'i18n',
    filesLocation: '../resources/lang',
    debug: config.dev.enabled
});

translator.fetch('en').then(() => {
    translator.translatePageTo();
});

if (config.ui.language !== 'en') {
    translator.fetch(config.ui.language).then(() => {
        translator.translatePageTo(config.ui.language);
    });
}
