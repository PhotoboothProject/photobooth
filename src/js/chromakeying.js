/* globals Seriously i18n */
/* exported setBackgroundImage */
let mainImage;
let mainImageWidth;
let mainImageHeight;
let backgroundImage;
let isPrinting = false;
let seriously;
let target;
let chroma;
let seriouslyimage;

function setMainImage(imgSrc) {
    const image = new Image();
    image.src = imgSrc;
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
    seriously.go();
    mainImage = new Image();
    mainImage.src = tmpCanvas.toDataURL('image/png');

    mainImage.onload = function () {
        drawCanvas();
    };
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
        //important to fetch tmpimageout
        ctx.drawImage(document.getElementById('tmpimageout'), 0, 0);
    }
}

function calculateAspectRatioFit(srcWidth, srcHeight, maxWidth, maxHeight) {
    const ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);

    return {
        width: srcWidth * ratio,
        height: srcHeight * ratio
    };
}

function printImage(filename, cb) {
    const errormsg = i18n('error');

    if (isPrinting) {
        console.log('Printing already: ' + isPrinting);
    } else {
        isPrinting = true;
        setTimeout(function () {
            $.ajax({
                method: 'GET',
                url: 'api/print.php',
                data: {
                    filename: filename
                },
                success: (data) => {
                    console.log('Picture processed: ', data);

                    if (data.error) {
                        console.log('An error occurred: ', data.error);
                        $('#print_mesg').empty();
                        $('#print_mesg').html(
                            '<div class="modal__body"><span style="color:red">' + data.error + '</span></div>'
                        );
                    }

                    setTimeout(function () {
                        $('#print_mesg').removeClass('modal--show');
                        if (data.error) {
                            $('#print_mesg').empty();
                            $('#print_mesg').html(
                                '<div class="modal__body"><span>' + i18n('printing') + '</span></div>'
                            );
                        }
                        cb();
                        isPrinting = false;
                    }, config.printing_time);
                },
                error: (jqXHR, textStatus) => {
                    console.log('An error occurred: ', textStatus);
                    $('#print_mesg').empty();
                    $('#print_mesg').html(
                        '<div class="modal__body"><span style="color:red">' + errormsg + '</span></div>'
                    );

                    setTimeout(function () {
                        $('#print_mesg').removeClass('modal--show');
                        $('#print_mesg').empty();
                        $('#print_mesg').html('<div class="modal__body"><span>' + i18n('printing') + '</span></div>');
                        cb();
                        isPrinting = false;
                    }, 5000);
                }
            });
        }, 1000);
    }
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
    $('#print_mesg').addClass('modal--show');

    setTimeout(function () {
        saveImage((data) => {
            if (!data.success) {
                return;
            }

            printImage(data.filename, () => {
                $('#print-btn').blur();
            });
        });
    }, 1000);
}

function saveImageHandler(ev) {
    ev.preventDefault();

    $('#save_mesg').addClass('modal--show');

    setTimeout(function () {
        saveImage(() => {
            setTimeout(function () {
                $('#save_mesg').removeClass('modal--show');
                $('#save-btn').blur();
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
    if (config.use_print_chromakeying && config.print_key && parseInt(config.print_key, 10) === ev.keyCode) {
        if (isPrinting) {
            console.log('Printing already in progress!');
        } else {
            $('#print-btn').trigger('click');
        }
    }
});

$(document).ready(function () {
    $('#save-btn').on('click', saveImageHandler);
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
});
