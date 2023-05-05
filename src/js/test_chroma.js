/* globals photoboothTools */
const photoboothChromaTest = (function () {
    const api = {};

    api.runCmd = function (chromaImage, chromaColor, chromaSensitivity, chromaBlend) {
        const dataChroma = {
            chromaImage: chromaImage,
            chromaColor: chromaColor,
            chromaSensitivity: chromaSensitivity,
            chromaBlend: chromaBlend
        };

        jQuery
            .post('../api/liveChromaConfig.php', dataChroma)
            .done(function () {
                photoboothTools.console.log('successfully set chroma data');
            })
            // eslint-disable-next-line no-unused-vars
            .fail(function (xhr, status, result) {
                photoboothTools.console.log('Failed to set chroma data!');
            });
    };

    $('.setChroma').on('click', function (e) {
        e.preventDefault();

        photoboothTools.console.log('Setting chroma values...');

        api.runCmd('/var/www/html/resources/img/bg.jpg', '00ff00', 0.4, 0.5)
    });

    return api;
})();
