/* globals Translator */
const translator = new Translator({
    persist: false,
    defaultLanguage: 'en',
    detectLanguage: false,
    registerGlobally: 'i18n',
    filesLocation: '../resources/lang',
    debug: config.dev
});

translator.fetch(config.language).then(() => {
    translator.translatePageTo(config.language);
});
