/* globals photoboothTools */

// eslint-disable-next-line no-unused-vars
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

        api.runCmd(
            $('#chromaImage').val(),
            $('#chromaColor').val().replace(/#/gu, ''),
            $('#chromaSensitivity').val(),
            $('#chromaBlend').val()
        );
    });

    return api;
})();
