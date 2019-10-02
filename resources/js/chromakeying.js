/* globals MarvinColorModelConverter AlphaBoundary MarvinImage */
/* exported setBackgroundImage printImage saveImage */
let mainImage;
let mainImageSrc;
let mainImageWidth;
let mainImageHeight;
let backgroundImage;

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
                if ((r * b) != 0 && (g * g) / (r * b) > 1.5) {
                    image.setIntColor(x, y, 255, (r * 1.4), g, (b * 1.4));
                } else {
                    image.setIntColor(x, y, 255, (r * 1.2), g, (b * 1.2));
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
    const image = new MarvinImage();
    image.load(imgSrc, function () {
        mainImageSrc = imgSrc;
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
        }
    });
}


function setBackgroundImage(url) {
    backgroundImage = new Image();
    backgroundImage.src = url;
    backgroundImage.onload = function () {
        drawCanvas();
    }
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
            const size = calculateAspectRatioFit(backgroundImage.width, backgroundImage.height, mainImage.width, mainImage.height);
            ctx.drawImage(backgroundImage, 0, 0, size.width, size.height);
        } else {
            ctx.drawImage(backgroundImage, 0, 0, backgroundImage.width, backgroundImage.height);
        }
    }

    if (typeof mainImage !== 'undefined' && mainImage !== null) {
        ctx.drawImage(mainImage, 0, 0);
    }
}

function calculateAspectRatioFit(srcWidth, srcHeight, maxWidth, maxHeight) {
    const ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);

    return {
        width: srcWidth * ratio,
        height: srcHeight * ratio
    };
}

function printImage() {
    if (typeof mainImageSrc === 'undefined' || mainImageSrc === null) {
        return;
    }

    const canvas = document.getElementById('mainCanvas');
    const dataURL = canvas.toDataURL('image/png');

    $('#print_mesg').addClass('modal--show');

    setTimeout(function () {
        $.post(
            'api/chromakeying/print.php',
            {
                imgData: dataURL,
                mainImageSrc: mainImageSrc
            },
            function () {
                setTimeout(function () {
                    $('#print_mesg').removeClass('modal--show');
                    window.location.reload();
                }, 5000);
            },
            'json'
        );
    }, 1000);
}

function saveImage() {
    if (typeof mainImageSrc === 'undefined' || mainImageSrc === null) {
        return;
    }

    const canvas = document.getElementById('mainCanvas');
    const dataURL = canvas.toDataURL('image/png');

    $('#save_mesg').addClass('modal--show');

    setTimeout(function () {
        $.post(
            'api/chromakeying/save.php',
            {
                imgData: dataURL,
                mainImageSrc: mainImageSrc
            },
            function () {
                setTimeout(function () {
                    $('#save_mesg').removeClass('modal--show');
                }, 2000);
            }
        );
    }, 1000);
}

$(document).ready(function () {
    setTimeout(function () {
        setMainImage($('body').attr('data-main-image'));
    }, 100);
});
