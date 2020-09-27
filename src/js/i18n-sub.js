/* globals Translator */
const translator = new Translator({
    persist: false,
    defaultLanguage: 'en',
    detectLanguage: false,
    registerGlobally: 'i18n',
    filesLocation: '../resources/lang',
    debug: config.dev
});

translator.fetch('en').then(() => {
    translator.translatePageTo();
});

if (config.language !== 'en') {
    translator.fetch(config.language).then(() => {
        translator.translatePageTo(config.language);
    });
}
