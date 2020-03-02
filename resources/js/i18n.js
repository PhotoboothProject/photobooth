import Translator from '../../vendor/simple-translator/src/translator.js';

const translator = new Translator({
    persist: false,
    languages: [
        'de',
        'el',
        'en',
        'es',
        'fr'
    ],
    defaultLanguage: 'en',
    detectLanguage: false,
    filesLocation: '/resources/lang'
});

translator.load();
translator.load(config.language);
