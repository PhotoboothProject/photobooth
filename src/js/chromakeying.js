/* globals MarvinColorModelConverter AlphaBoundary MarvinImage Seriously initRemoteBuzzerFromDOM rotaryController photoboothTools */
/* exported setBackgroundImage */
let mainImage;
let mainImageWidth;
let mainImageHeight;
let backgroundImage;
let seriously;
let target;
let chroma;
let seriouslyimage;

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

function setMainImage(imgSrc) {
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
        image.src = imgSrc;
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
            if (cb) {
                cb(data);
            }
        }
    );
}

function printImageHandler(ev) {
    ev.preventDefault();

    setTimeout(function () {
        saveImage((data) => {
            if (!data.success) {
                return;
            }

            photoboothTools.printImage(data.filename, () => {
                $('#print-btn').blur();
            });
        });
    }, 1000);
}

function saveImageHandler(ev) {
    ev.preventDefault();

    photoboothTools.modal.open('#save_mesg');

    setTimeout(function () {
        saveImage(() => {
            setTimeout(function () {
                photoboothTools.modal.close('#save_mesg');
                $('#save-chroma-btn').blur();
            }, 2000);
        });
    }, 1000);
}

function closeHandler(ev) {
    ev.preventDefault();

    if (document.referrer) {
        window.location = document.referrer;
    } else {
        window.history.back();
    }
}

$(document).on('keyup', function (ev) {
    if (config.print.from_chromakeying && config.print.key && parseInt(config.print.key, 10) === ev.keyCode) {
        if (photoboothTools.isPrinting) {
            photoboothTools.console.log('Printing already in progress!');
        } else {
            $('#print-btn').trigger('click');
        }
    }
});

$(document).ready(function () {
    $('#save-chroma-btn').on('click', saveImageHandler);
    $('#print-btn').on('click', printImageHandler);
    $('#close-btn').on('click', closeHandler);

    setTimeout(function () {
        setMainImage($('body').attr('data-main-image'));
    }, 100);

    // we don't want to scroll on small or horizontal devices
    const windowHeight = $(window).innerHeight();
    const bottomLine = $('.chroma-control-bar').position().top + $('.chroma-control-bar').outerHeight(true);
    const diff = bottomLine - windowHeight;

    if (diff > 0) {
        const canvasHeight = $('#mainCanvas').height();

        $('#mainCanvas').css('height', canvasHeight - diff + 'px');
    }
    $('.canvasWrapper').removeClass('initial');

    initRemoteBuzzerFromDOM();
    rotaryController.focusSet('.chromawrapper');
});
