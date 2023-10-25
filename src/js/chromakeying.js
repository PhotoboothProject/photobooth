/* globals photoBooth MarvinColorModelConverter AlphaBoundary MarvinImage Seriously initRemoteBuzzerFromDOM photoboothTools */
/* exported setChromaImage processChromaImage */
let mainImage;
let mainImageWidth;
let mainImageHeight;
let backgroundImage;
let seriously;
let target;
let chroma;
let seriouslyimage;
let needsReload = false;
const notificationTimeout = config.ui.notification_timeout * 1000;

const chromaCanvas = document.getElementById('chromaCanvas');
const chromaCanvasContext = chromaCanvas.getContext ? chromaCanvas.getContext('2d') : null;

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

function processChromaImage(imgSrc, save = false, filename = '') {
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
                drawCanvas(save, filename);
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
                drawCanvas(save, filename);
            };
        };
        image.src = imgSrc;
    }
}

// eslint-disable-next-line no-unused-vars
function setChromaImage(url) {
    backgroundImage = new Image();
    backgroundImage.src = url;
    backgroundImage.onload = function () {
        drawCanvas();
    };
}

function drawCanvas(save = false, filename = '') {
    if (typeof mainImage !== 'undefined' && mainImage !== null) {
       chromaCanvas.width = mainImage.width;
       chromaCanvas.height = mainImage.height;
    } else if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
       chromaCanvas.width = backgroundImage.width;
       chromaCanvas.height = backgroundImage.height;
    }

    // Clear the canvas
    chromaCanvasContext.clearRect(0, 0,chromaCanvas.width,chromaCanvas.height);

    if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
        if (typeof mainImage !== 'undefined' && mainImage !== null) {
            const size = calculateAspectRatioFit(
                backgroundImage.width,
                backgroundImage.height,
                mainImage.width,
                mainImage.height
            );
            chromaCanvasContext.drawImage(backgroundImage, 0, 0, size.width, size.height);
        } else {
            chromaCanvasContext.drawImage(backgroundImage, 0, 0, backgroundImage.width, backgroundImage.height);
        }
    }

    if (typeof mainImage !== 'undefined' && mainImage !== null) {
        if (config.keying.variant === 'marvinj') {
            chromaCanvasContext.drawImage(mainImage, 0, 0);
        } else {
            //important to fetch tmpimageout
            chromaCanvasContext.drawImage(document.getElementById('tmpimageout'), 0, 0);
        }
        if (save) {
            saveImage(filename);
        }
    }
}

function clearCanvasAndLoadImage(imageUrl) {
    // Clear the canvas
    chromaCanvasContext.clearRect(0, 0,chromaCanvas.width,chromaCanvas.height);

    // Create a new image element
    const newImage = new Image();

    // Set the onload event handler to execute code after the image is loaded
    newImage.onload = function () {
       chromaCanvas.width = newImage.width;
       chromaCanvas.height = newImage.height;
        chromaCanvasContext.drawImage(newImage, 0, 0);
    };

    // Set the source of the image to the specified URL
    newImage.src = imageUrl;
}

function saveImage(filename, cb) {
    const dataURL = chromaCanvas.toDataURL('image/png');
    $.ajax({
        method: 'POST',
        url: config.foldersPublic.api + '/chromakeying/save.php',
        data: {
            imgData: dataURL,
            file: filename
        },
        success: (resp) => {
            if (typeof onCaptureChromaView === 'undefined') {
                setTimeout(function () {
                    photoboothTools.overlay.close();
                    $('[data-command="save-chroma-btn"]').trigger('blur');
                }, notificationTimeout);
            } else {
                photoBooth.takingPic = false;
                needsReload = true;
                photoBooth.chromaimage = resp.filename;
                clearCanvasAndLoadImage(config.foldersPublic.images + '/' + resp.filename);

                if (config.picture.allow_delete) {
                    $('[data-command="deletebtn"]')
                        .off('click')
                        .on('click', (ev) => {
                            ev.preventDefault();

                            const msg = photoboothTools.getTranslation('really_delete_image');
                            const really = config.delete.no_request ? true : confirm(resp.filename + ' ' + msg);
                            if (really) {
                                photoBooth.deleteImage(resp.filename, (result) => {
                                    if (result.success && config.keying.show_all) {
                                        photoBooth.deleteImage(photoBooth.filename, () => {
                                            setTimeout(function () {
                                                photoboothTools.reloadPage();
                                            }, notificationTimeout);
                                        });
                                    } else {
                                        setTimeout(function () {
                                            photoboothTools.reloadPage();
                                        }, notificationTimeout);
                                    }
                                });
                            } else {
                                $('.deletebtn').trigger('blur');
                            }
                        });
                }
                if (resp.filename) {
                    // Add Image to gallery and slider
                    photoBooth.addImage(resp.filename);
                }
            }
            if (cb) {
                cb(resp);
            }
        },
        error: (jqXHR, textStatus) => {
            photoboothTools.console.log(textStatus);
            setTimeout(function () {
                photoboothTools.reloadPage();
            }, notificationTimeout);
        }
    });
}

function calculateAspectRatioFit(srcWidth, srcHeight, maxWidth, maxHeight) {
    const ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);

    return {
        width: srcWidth * ratio,
        height: srcHeight * ratio
    };
}

$(document).on('keyup', function (ev) {
    if (
        typeof onCaptureChromaView === 'undefined' &&
        config.print.from_chromakeying &&
        config.print.key &&
        parseInt(config.print.key, 10) === ev.keyCode
    ) {
        if (photoboothTools.isPrinting) {
            photoboothTools.console.log('Printing already in progress!');
        } else {
            $('[data-command="print-btn"]').trigger('click');
        }
    } else if (
        typeof onCaptureChromaView != 'undefined' &&
        ((config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) ||
            (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode))
    ) {
        if (!backgroundImage) {
            photoboothTools.console.logDev('Please choose a background first!');
            photoboothTools.overlay.showError(photoboothTools.getTranslation('chroma_needs_background'));
            setTimeout(() => photoboothTools.overlay.close(), 1000);
        } else if (needsReload) {
            photoboothTools.console.logDev('Please reload the page to take a new Picture!');
            photoboothTools.overlay.showError(photoboothTools.getTranslation('chroma_needs_reload'));
            setTimeout(() => photoboothTools.overlay.close(), 1000);
        } else if (!photoBooth.takingPic) {
            if (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) {
                photoboothTools.console.logDev('Collage key pressed. Not possible on live chroma, triggering photo now.');
            }
            photoBooth.thrill('chroma');
        } else if (config.dev.loglevel > 0 && photoBooth.takingPic) {
            photoboothTools.console.log('Taking photo already in progress!');
        }
    }
});

$(function() {
    const $chromaStage = $('.stage[data-stage="start"]');
    const $chromaActions = $chromaStage.find('.buttonbar.buttonbar--bottom');
    const $chromaMessage = $chromaStage.find('.stage-message');

    if (typeof onCaptureChromaView === 'undefined') {
        $('[data-command="save-chroma-btn"]').on('click', (event) => {
            event.preventDefault();
            photoboothTools.overlay.show(photoboothTools.getTranslation('saving'));
            saveImage();
        });
        $('[data-command="print-btn"]').on('click', (event) => {
            event.preventDefault();
            setTimeout(function () {
                saveImage('', (resp) => {
                    if (!resp.success) {
                        return;
                    }
                    photoboothTools.printImage(resp.filename, () => {
                        $('[data-command="print-btn"]').trigger('blur');
                    });
                });
            }, 1000);
        });
        $('[data-command="close-btn"]').on('click', (event) => {
            event.preventDefault();
            if (document.referrer) {
                window.location = document.referrer;
            } else {
                window.history.back();
            }
        });

        setTimeout(function () {
            processChromaImage($('body').attr('data-main-image'));
        }, 100);

        initRemoteBuzzerFromDOM();
    } else {
        $chromaActions.addClass('hidden');

        $('.chroma-background-selector-image').on('click', function () {
            $chromaMessage.addClass('hidden');
            $chromaActions.removeClass('hidden');
        });

        $('[data-command="take-chroma"]').on('click', (event) => {
            event.preventDefault();
            if (photoBooth.takingPic) {
                photoboothTools.console.logDev('Taking picture in progress already!');
                return;
            }
            photoBooth.thrill('chroma');
        });

        photoboothTools.console.log('[CHROMA CAPTURE] DOM ready');
    }
});
