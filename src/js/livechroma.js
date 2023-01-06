/* globals photoBooth MarvinColorModelConverter AlphaBoundary MarvinImage Seriously rotaryController photoboothTools */
/* exported setBackgroundImage setMainImage */
let mainImage;
let mainImageWidth;
let mainImageHeight;
let backgroundImage;
let seriously;
let target;
let chroma;
let seriouslyimage;
let needsReload = false;

function greenToTransparency(imageIn, imageOut) {
    for (let y = 0; y < imageIn.getHeight(); y++) {
        for (let x = 0; x < imageIn.getWidth(); x++) {
            const color = imageIn.getIntColor(x, y);
            const hsv = MarvinColorModelConverter.rgbToHsv([color]);

            if (hsv[0] >= 60 && hsv[0] <= 200 && hsv[1] >= 0.2 && hsv[2] >= 0.2) {
                imageOut.setIntColor(x, y, 0, 127, 127, 127);
            } else {
                imageOut.setIntColor(x, y, color);
            }
        }
    }
}

function reduceGreen(image) {
    for (let y = 0; y < image.getHeight(); y++) {
        for (let x = 0; x < image.getWidth(); x++) {
            const r = image.getIntComponent0(x, y);
            const g = image.getIntComponent1(x, y);
            const b = image.getIntComponent2(x, y);
            const color = image.getIntColor(x, y);
            const hsv = MarvinColorModelConverter.rgbToHsv([color]);

            if (hsv[0] >= 60 && hsv[0] <= 130 && hsv[1] >= 0.15 && hsv[2] >= 0.15) {
                if (r * b != 0 && (g * g) / (r * b) > 1.5) {
                    image.setIntColor(x, y, 255, r * 1.4, g, b * 1.4);
                } else {
                    image.setIntColor(x, y, 255, r * 1.2, g, b * 1.2);
                }
            }
        }
    }
}

function alphaBoundary(imageOut, radius) {
    const ab = new AlphaBoundary();
    for (let y = 0; y < imageOut.getHeight(); y++) {
        for (let x = 0; x < imageOut.getWidth(); x++) {
            ab.alphaRadius(imageOut, x, y, radius);
        }
    }
}

// eslint-disable-next-line no-unused-vars
function setMainImage(imgSrc) {
    photoboothTools.console.logDev('[LIVECHROMA] Keying variant: ' + config.keying.variant);
    if (config.keying.variant === 'marvinj') {
        const image = new MarvinImage();
        image.load(imgSrc, function () {
            mainImageWidth = image.getWidth();
            mainImageHeight = image.getHeight();

            const imageOut = new MarvinImage(image.getWidth(), image.getHeight());

            //1. Convert green to transparency
            greenToTransparency(image, imageOut);

            // 2. Reduce remaining green pixels
            reduceGreen(imageOut);

            // 3. Apply alpha to the boundary
            alphaBoundary(imageOut, 6);

            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = mainImageWidth;
            tmpCanvas.height = mainImageHeight;
            imageOut.draw(tmpCanvas);

            mainImage = new Image();
            mainImage.src = tmpCanvas.toDataURL('image/png');
            mainImage.onload = function () {
                drawCanvas();
            };
        });
    } else {
        const image = new Image();
        image.src = imgSrc;
        image.onload = function () {
            mainImageWidth = image.width;
            mainImageHeight = image.height;

            // create tmpcanvas and size it to image size
            const tmpCanvas = document.createElement('canvas');
            tmpCanvas.width = mainImageWidth;
            tmpCanvas.height = mainImageHeight;
            tmpCanvas.id = 'tmpimageout';

            // append Canvas for Seriously to chromakey the image
            // eslint-disable-next-line no-unused-vars
            const body = document.getElementsByTagName('body')[0];
            document.body.appendChild(tmpCanvas);

            seriously = new Seriously();
            target = seriously.target('#tmpimageout');
            seriouslyimage = seriously.source(image);
            chroma = seriously.effect('chroma');
            chroma.source = seriouslyimage;
            target.source = chroma;
            const color = config.keying.seriouslyjs_color;
            const r = parseInt(color.substr(1, 2), 16) / 255;
            const g = parseInt(color.substr(3, 2), 16) / 255;
            const b = parseInt(color.substr(5, 2), 16) / 255;
            photoboothTools.console.logDev('Chromakeying color:', color);
            photoboothTools.console.logDev('Red:', r, 'Green:', g, 'Blue:', b);
            chroma.screen = [r, g, b, 1];
            seriously.go();
            mainImage = new Image();
            mainImage.src = tmpCanvas.toDataURL('image/png');
            mainImage.onload = function () {
                drawCanvas();
            };
        };
    }
}

// eslint-disable-next-line no-unused-vars
function setBackgroundImage(url) {
    backgroundImage = new Image();
    backgroundImage.src = url;
    backgroundImage.onload = function () {
        drawCanvas();
    };
}

function drawCanvas() {
    const canvas = document.getElementById('mainCanvas');
    if (typeof mainImage !== 'undefined' && mainImage !== null) {
        canvas.width = mainImage.width;
        canvas.height = mainImage.height;
    } else if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
        canvas.width = backgroundImage.width;
        canvas.height = backgroundImage.height;
    }

    const ctx = canvas.getContext ? canvas.getContext('2d') : null;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
        if (typeof mainImage !== 'undefined' && mainImage !== null) {
            const size = calculateAspectRatioFit(
                backgroundImage.width,
                backgroundImage.height,
                mainImage.width,
                mainImage.height
            );
            ctx.drawImage(backgroundImage, 0, 0, size.width, size.height);
        } else {
            ctx.drawImage(backgroundImage, 0, 0, backgroundImage.width, backgroundImage.height);
        }
    }

    if (typeof mainImage !== 'undefined' && mainImage !== null) {
        if (config.keying.variant === 'marvinj') {
            ctx.drawImage(mainImage, 0, 0);
        } else {
            //important to fetch tmpimageout
            ctx.drawImage(document.getElementById('tmpimageout'), 0, 0);
        }
        saveImage();
    }
}

function calculateAspectRatioFit(srcWidth, srcHeight, maxWidth, maxHeight) {
    const ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);

    return {
        width: srcWidth * ratio,
        height: srcHeight * ratio
    };
}

function saveImage(cb) {
    const canvas = document.getElementById('mainCanvas');
    const dataURL = canvas.toDataURL('image/png');

    $.post(
        'api/chromakeying/save.php',
        {
            imgData: dataURL
        },
        function (data) {
            photoBooth.takingPic = false;
            needsReload = true;
            if ($('.chroma-control-bar').is(':hidden')) {
                $('.chroma-control-bar').show();
                $('.takeChroma').hide();
            }
            if (config.picture.allow_delete) {
                $('.deletebtn').css('visibility', 'visible');
                $('.chroma-control-bar')
                    .find('.deletebtn')
                    .off('click')
                    .on('click', (ev) => {
                        ev.preventDefault();

                        const msg = photoboothTools.getTranslation('really_delete_image');
                        const really = config.delete.no_request ? true : confirm(data.filename + ' ' + msg);
                        if (really) {
                            photoBooth.deleteImage(data.filename, (result) => {
                                if (result.success && config.live_keying.show_all) {
                                    photoBooth.deleteImage(photoBooth.chromaimage, () => {
                                        setTimeout(function () {
                                            photoboothTools.reloadPage();
                                        }, 5000);
                                    });
                                } else {
                                    setTimeout(function () {
                                        photoboothTools.reloadPage();
                                    }, 5000);
                                }
                            });
                        } else {
                            $('.deletebtn').blur();
                        }
                    });
            }
            if (data.filename) {
                // Add Image to gallery and slider
                photoBooth.addImage(data.filename);
            }
            if (cb) {
                cb(data);
            }
        }
    );
}

$('.backgroundPreview').on('click', function () {
    $('.canvasWrapper').removeClass('initial');
    if ($('.chroma-control-bar').is(':hidden')) {
        $('.chroma-control-bar').show();
        $('.chromaNote').empty();
        $('.chromaNote').hide();
    }
    $('.backgrounds').addClass('shrinked');
});

// Take Chroma Button
$('.takeChroma, .newchroma').on('click', function (e) {
    e.preventDefault();

    if (photoBooth.takingPic) {
        photoboothTools.console.logDev('Taking picture in progress already!');

        return;
    }

    const chromaInfo = photoboothTools.getTranslation('chromaInfoAfter');

    photoBooth.thrill('chroma');

    if ($('.chroma-control-bar').is(':visible')) {
        $('.chroma-control-bar').hide();
        $('.backgrounds').hide();

        setTimeout(() => {
            $('.chromaNote').show();
            $('.chromaNote').text(chromaInfo);
            $('.chroma-control-bar > .takeChroma').hide();
            $('.chroma-control-bar > .deleteBtn').hide();
            $('.chroma-control-bar > .reloadPage').show();
            $('.chroma-control-bar').show();
        }, config.picture.cntdwn_time * 1000);
    }
});

$(document).on('keyup', function (ev) {
    if (config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) {
        if (!backgroundImage) {
            photoboothTools.console.log('Please choose a background first!');
        } else if (needsReload) {
            photoboothTools.console.log('Please reload the page to take a new Picture!');
        } else if (!photoBooth.takingPic) {
            $('.closeGallery').trigger('click');
            $('.takeChroma').trigger('click');
        } else if (config.dev.loglevel > 0 && photoBooth.takingPic) {
            photoboothTools.console.log('Taking photo already in progress!');
        }
    }

    if (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) {
        if (!backgroundImage) {
            photoboothTools.console.log('Please choose a background first!');
        } else if (needsReload) {
            photoboothTools.console.log('Please reload the page to take a new Picture!');
        } else if (!photoBooth.takingPic) {
            $('.closeGallery').trigger('click');
            photoboothTools.console.logDev('Collage key pressed. Not possible on live chroma, triggering photo now.');
            $('.takeChroma').trigger('click');
        } else if (config.dev.loglevel > 0 && photoBooth.takingPic) {
            photoboothTools.console.log('Taking photo already in progress!');
        }
    }
});

$('.reloadPage').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    photoboothTools.reloadPage();
});

// Open Gallery Button
$('.livechroma-gallery-btn').on('click', function (e) {
    e.preventDefault();

    photoBooth.openGallery($(this));
});

// Close Button
$('.livechroma-close-btn').on('click', function () {
    location.assign('./index.php');
});

$(document).ready(function () {
    photoboothTools.console.log('[LIVECHROMA] DOM ready');
    rotaryController.focusSet('#start');
});
