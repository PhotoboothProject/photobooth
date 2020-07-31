"use strict";

var translator = new Translator({
  persist: false,
  defaultLanguage: 'en',
  detectLanguage: false,
  registerGlobally: 'i18n',
  filesLocation: '/photobooth/resources/lang',
  debug: true
});
translator.fetch(['de', 'en', 'es', 'el', 'fr']).then(function () {
  translator.translatePageTo();
});