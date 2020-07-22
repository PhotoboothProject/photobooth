/* exported i18n */
import {LANGUAGE} from '../../api/language.php';
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
    filesLocation: 'resources/lang'
});

window.i18n = function (key) {
    return translator.getTranslationByKey(LANGUAGE, key);
}

$(function () {
    translator.load(LANGUAGE);
});
