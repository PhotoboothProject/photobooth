"use strict";

/* globals MarvinColorModelConverter AlphaBoundary MarvinImage */

/* exported setBackgroundImage */
var mainImage;
var mainImageWidth;
var mainImageHeight;
var backgroundImage;

function greenToTransparency(imageIn, imageOut) {
  for (var y = 0; y < imageIn.getHeight(); y++) {
    for (var x = 0; x < imageIn.getWidth(); x++) {
      var color = imageIn.getIntColor(x, y);
      var hsv = MarvinColorModelConverter.rgbToHsv([color]);

      if (hsv[0] >= 60 && hsv[0] <= 200 && hsv[1] >= 0.2 && hsv[2] >= 0.2) {
        imageOut.setIntColor(x, y, 0, 127, 127, 127);
      } else {
        imageOut.setIntColor(x, y, color);
      }
    }
  }
}

function reduceGreen(image) {
  for (var y = 0; y < image.getHeight(); y++) {
    for (var x = 0; x < image.getWidth(); x++) {
      var r = image.getIntComponent0(x, y);
      var g = image.getIntComponent1(x, y);
      var b = image.getIntComponent2(x, y);
      var color = image.getIntColor(x, y);
      var hsv = MarvinColorModelConverter.rgbToHsv([color]);

      if (hsv[0] >= 60 && hsv[0] <= 130 && hsv[1] >= 0.15 && hsv[2] >= 0.15) {
        if (r * b != 0 && g * g / (r * b) > 1.5) {
          image.setIntColor(x, y, 255, r * 1.4, g, b * 1.4);
        } else {
          image.setIntColor(x, y, 255, r * 1.2, g, b * 1.2);
        }
      }
    }
  }
}

function alphaBoundary(imageOut, radius) {
  var ab = new AlphaBoundary();

  for (var y = 0; y < imageOut.getHeight(); y++) {
    for (var x = 0; x < imageOut.getWidth(); x++) {
      ab.alphaRadius(imageOut, x, y, radius);
    }
  }
}

function setMainImage(imgSrc) {
  var image = new MarvinImage();
  image.load(imgSrc, function () {
    mainImageWidth = image.getWidth();
    mainImageHeight = image.getHeight();
    var imageOut = new MarvinImage(image.getWidth(), image.getHeight()); //1. Convert green to transparency

    greenToTransparency(image, imageOut); // 2. Reduce remaining green pixels

    reduceGreen(imageOut); // 3. Apply alpha to the boundary

    alphaBoundary(imageOut, 6);
    var tmpCanvas = document.createElement('canvas');
    tmpCanvas.width = mainImageWidth;
    tmpCanvas.height = mainImageHeight;
    imageOut.draw(tmpCanvas);
    mainImage = new Image();
    mainImage.src = tmpCanvas.toDataURL('image/png');

    mainImage.onload = function () {
      drawCanvas();
    };
  });
}

function setBackgroundImage(url) {
  backgroundImage = new Image();
  backgroundImage.src = url;

  backgroundImage.onload = function () {
    drawCanvas();
  };
}

function drawCanvas() {
  var canvas = document.getElementById('mainCanvas');

  if (typeof mainImage !== 'undefined' && mainImage !== null) {
    canvas.width = mainImage.width;
    canvas.height = mainImage.height;
  } else if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
    canvas.width = backgroundImage.width;
    canvas.height = backgroundImage.height;
  }

  var ctx = canvas.getContext ? canvas.getContext('2d') : null;
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (typeof backgroundImage !== 'undefined' && backgroundImage !== null) {
    if (typeof mainImage !== 'undefined' && mainImage !== null) {
      var size = calculateAspectRatioFit(backgroundImage.width, backgroundImage.height, mainImage.width, mainImage.height);
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
  var ratio = Math.max(maxWidth / srcWidth, maxHeight / srcHeight);
  return {
    width: srcWidth * ratio,
    height: srcHeight * ratio
  };
}

function printImage(filename, cb) {
  console.log('print', filename);
  $.get('api/print.php', {
    filename: filename
  }, function (data) {
    console.log('print data', data);

    if (cb) {
      cb(data);
    }
  });
}

function saveImage(cb) {
  var canvas = document.getElementById('mainCanvas');
  var dataURL = canvas.toDataURL('image/png');
  $.post('api/chromakeying/save.php', {
    imgData: dataURL
  }, function (data) {
    if (cb) {
      cb(data);
    }
  });
}

function printImageHandler(ev) {
  ev.preventDefault();
  $('#print_mesg').addClass('modal--show');
  setTimeout(function () {
    saveImage(function (data) {
      if (!data.success) {
        return;
      }

      printImage(data.filename, function () {
        setTimeout(function () {
          $('#print_mesg').removeClass('modal--show');
          $('#print-btn').blur();
        }, 5000);
      });
    });
  }, 1000);
}

function saveImageHandler(ev) {
  ev.preventDefault();
  $('#save_mesg').addClass('modal--show');
  setTimeout(function () {
    saveImage(function () {
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

$(document).ready(function () {
  $('#save-btn').on('click', saveImageHandler);
  $('#print-btn').on('click', printImageHandler);
  $('#close-btn').on('click', closeHandler);
  setTimeout(function () {
    setMainImage($('body').attr('data-main-image'));
  }, 100); // we don't want to scroll on small or horizontal devices

  var windowHeight = $(window).innerHeight();
  var bottomLine = $('.chroma-control-bar').position().top + $('.chroma-control-bar').outerHeight(true);
  var diff = bottomLine - windowHeight;

  if (diff > 0) {
    var canvasHeight = $('#mainCanvas').height();
    $('#mainCanvas').css('height', canvasHeight - diff + 'px');
  }
});