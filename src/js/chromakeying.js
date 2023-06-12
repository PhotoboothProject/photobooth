/* globals photoBooth MarvinColorModelConverter AlphaBoundary MarvinImage Seriously initRemoteBuzzerFromDOM rotaryController photoboothTools */
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
const notificationTimeout = config.ui.notification_timeout * 1000;
const canvas = document.getElementById('mainCanvas');
const ctx = canvas.getContext ? canvas.getContext('2d') : null;

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

function setMainImage(imgSrc, save = false, filename = '') {
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
function setBackgroundImage(url) {
    backgroundImage = new Image();
    backgroundImage.src = url;
    backgroundImage.onload = function () {
        drawCanvas();
        $('.canvasWrapper').css('display', 'inline-block');
    };
}

function drawCanvas(save = false, filename = '') {
    if (typeof mainImage !== 'undefined' && mainImage !== null) {
        canvas.width = mainImage.width;
        canvas.height = mainImage.height;
    } else if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
        canvas.width = backgroundImage.width;
        canvas.height = backgroundImage.height;
    }

    // Clear the canvas
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
        if (save) {
            saveImage(filename);
        }
    }
}

function clearCanvasAndLoadImage(imageUrl) {
    // Clear the canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Create a new image element
    const newImage = new Image();

    // Set the onload event handler to execute code after the image is loaded
    newImage.onload = function () {
        canvas.width = newImage.width;
        canvas.height = newImage.height;
        ctx.drawImage(newImage, 0, 0);
    };

    // Set the source of the image to the specified URL
    newImage.src = imageUrl;
}

function saveImage(filename, cb) {
    const dataURL = canvas.toDataURL('image/png');
    $.ajax({
        method: 'POST',
        url: config.foldersJS.api + '/chromakeying/save.php',
        data: {
            imgData: dataURL,
            file: filename
        },
        success: (resp) => {
            if (typeof onCaptureChromaView === 'undefined') {
                setTimeout(function () {
                    photoboothTools.modal.close('#save_mesg');
                    $('#save-chroma-btn').blur();
                }, notificationTimeout);
            } else {
                photoBooth.takingPic = false;
                needsReload = true;
                photoBooth.chromaimage = resp.filename;
                if ($('.chroma-control-bar').is(':hidden')) {
                    $('.chroma-control-bar').show();
                    $('.takeChroma').hide();
                }
                clearCanvasAndLoadImage(config.foldersJS.images + '/' + resp.filename);

                if (config.picture.allow_delete) {
                    $('.deletebtn').css('visibility', 'visible');
                    $('.chroma-control-bar')
                        .find('.deletebtn')
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
                                $('.deletebtn').blur();
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

function printImageHandler(ev) {
    ev.preventDefault();

    setTimeout(function () {
        saveImage('', (resp) => {
            if (!resp.success) {
                return;
            }

            photoboothTools.printImage(resp.filename, () => {
                $('#print-btn').blur();
            });
        });
    }, 1000);
}

function saveImageHandler(ev) {
    ev.preventDefault();

    photoboothTools.modal.open('#save_mesg');

    saveImage();
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
    if (
        typeof onCaptureChromaView === 'undefined' &&
        config.print.from_chromakeying &&
        config.print.key &&
        parseInt(config.print.key, 10) === ev.keyCode
    ) {
        if (photoboothTools.isPrinting) {
            photoboothTools.console.log('Printing already in progress!');
        } else {
            $('#print-btn').trigger('click');
        }
    } else if (
        typeof onCaptureChromaView != 'undefined' &&
        ((config.picture.key && parseInt(config.picture.key, 10) === ev.keyCode) ||
            (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode))
    ) {
        if (!backgroundImage) {
            photoboothTools.modalMesg.showError(
                '#modal_mesg',
                photoboothTools.getTranslation('chroma_needs_background')
            );
            setTimeout(function () {
                photoboothTools.modalMesg.reset('#modal_mesg');
            }, 1000);
            photoboothTools.console.logDev('Please choose a background first!');
        } else if (needsReload) {
            photoboothTools.modalMesg.showError('#modal_mesg', photoboothTools.getTranslation('chroma_needs_reload'));
            setTimeout(function () {
                photoboothTools.modalMesg.reset('#modal_mesg');
            }, 1000);
            photoboothTools.console.logDev('Please reload the page to take a new Picture!');
        } else if (!photoBooth.takingPic) {
            if (config.collage.key && parseInt(config.collage.key, 10) === ev.keyCode) {
                photoboothTools.console.logDev(
                    'Collage key pressed. Not possible on live chroma, triggering photo now.'
                );
            }
            photoBooth.thrill('chroma');
        } else if (config.dev.loglevel > 0 && photoBooth.takingPic) {
            photoboothTools.console.log('Taking photo already in progress!');
        }
    }
});

$(document).ready(function () {
    if (typeof onCaptureChromaView === 'undefined') {
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
    } else {
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

        $('.reloadPage').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            photoboothTools.reloadPage();
        });

        // Open Gallery Button
        $('.chromaCapture-gallery-btn').on('click', function (e) {
            e.preventDefault();

            photoBooth.openGallery($(this));
        });

        // Close Button
        $('.chromaCapture-close-btn').on('click', function () {
            location.assign('./index.php');
        });

        photoboothTools.console.log('[CHROMA CAPTURE] DOM ready');
        if (typeof rotaryController !== 'undefined') {
            rotaryController.focusSet('#start');
        }
    }
});
