/* globals Translator */
const translator = new Translator({
    persist: false,
    defaultLanguage: 'en',
    detectLanguage: false,
    registerGlobally: 'i18n',
    filesLocation: '../resources/lang',
    debug: true
});

translator.fetch([
    'de',
    'en',
    'es',
    'el',
    'fr'
]).then(() => {
    translator.translatePageTo();
});
