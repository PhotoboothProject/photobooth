"use strict";

/* exported initPhotoSwipeFromDOM */

/* global photoBooth */
function initPhotoSwipeFromDOM(gallerySelector) {
  var gallery,
      ssRunning = false,
      ssOnce = false;
  var ssDelay = config.slideshow_pictureTime,
      ssButtonClass = '.pswp__button--playpause';

  var parseThumbnailElements = function parseThumbnailElements(container) {
    return $(container).find('>a').map(function () {
      var element = $(this);
      var size = element.attr('data-size').split('x');
      var medSize = element.attr('data-med-size').split('x'); // create slide object

      var item = {
        element: element.get(0),
        src: element.attr('href'),
        w: parseInt(size[0], 10),
        h: parseInt(size[1], 10),
        msrc: element.find('>img').attr('src'),
        mediumImage: {
          src: element.attr('data-med'),
          w: parseInt(medSize[0], 10),
          h: parseInt(medSize[1], 10)
        }
      };
      item.originalImage = {
        src: item.src,
        w: item.w,
        h: item.h
      };
      return item;
    }).get();
  };

  var onThumbnailClick = function onThumbnailClick(ev) {
    ev.preventDefault();
    var element = $(ev.target).closest('a');
    var index = $(gallerySelector).find('>a').index(element);
    openPhotoSwipe(index);
  };

  var openPhotoSwipe = function openPhotoSwipe(index) {
    var pswpElement = $('.pswp').get(0);
    var items = parseThumbnailElements(gallerySelector);
    var options = {
      index: index,
      getThumbBoundsFn: function getThumbBoundsFn(thumbIndex) {
        // See Options->getThumbBoundsFn section of docs for more info
        var thumbnail = items[thumbIndex].element.children[0],
            pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
            rect = thumbnail.getBoundingClientRect();
        return {
          x: rect.left,
          y: rect.top + pageYScroll,
          w: rect.width
        };
      },
      shareEl: false,
      zoomEl: false,
      fullscreenEl: false
    }; // Pass data to PhotoSwipe and initialize it

    gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options); // Slideshow not running from the start

    setSlideshowState(ssButtonClass, false); // see: http://photoswipe.com/documentation/responsive-images.html

    var realViewportWidth,
        useLargeImages = false,
        firstResize = true,
        imageSrcWillChange;
    gallery.listen('beforeResize', function () {
      var dpiRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
      dpiRatio = Math.min(dpiRatio, 2.5);
      realViewportWidth = gallery.viewportSize.x * dpiRatio;

      if (realViewportWidth >= 1200 || !gallery.likelyTouchDevice && realViewportWidth > 800 || screen.width > 1200) {
        if (!useLargeImages) {
          useLargeImages = true;
          imageSrcWillChange = true;
        }
      } else if (useLargeImages) {
        useLargeImages = false;
        imageSrcWillChange = true;
      }

      if (imageSrcWillChange && !firstResize) {
        gallery.invalidateCurrItems();
      }

      if (firstResize) {
        firstResize = false;
      }

      imageSrcWillChange = false;
    });
    gallery.listen('gettingData', function (_index, item) {
      if (useLargeImages) {
        item.src = item.originalImage.src;
        item.w = item.originalImage.w;
        item.h = item.originalImage.h;
      } else {
        item.src = item.mediumImage.src;
        item.w = item.mediumImage.w;
        item.h = item.mediumImage.h;
      }
    });
    gallery.listen('afterChange', function () {
      var img = gallery.currItem.src.split('\\').pop().split('/').pop();
      $('.pswp__button--download').attr({
        href: 'api/download.php?image=' + img,
        download: img
      });

      if (ssRunning && ssOnce) {
        ssOnce = false;
        setTimeout(gotoNextSlide, ssDelay);
      }
    });

    var resetMailForm = function resetMailForm() {
      $('.pswp__qr').removeClass('qr-active').fadeOut('fast');
      photoBooth.resetMailForm();
      $('.send-mail').removeClass('mail-active').fadeOut('fast');
    };

    gallery.listen('beforeChange', resetMailForm);
    gallery.listen('close', resetMailForm);
    gallery.init();
  }; // QR in gallery


  $('.pswp__button--qrcode').on('click touchstart', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var pswpQR = $('.pswp__qr');

    if (pswpQR.hasClass('qr-active')) {
      pswpQR.removeClass('qr-active').fadeOut('fast');
    } else {
      pswpQR.empty();
      var img = gallery.currItem.src;
      img = img.split('\\').pop().split('/').pop();
      $('<img>').attr('src', 'api/qrcode.php?filename=' + img).appendTo(pswpQR);
      pswpQR.addClass('qr-active').fadeIn('fast');
    }
  }); // print in gallery

  $('.pswp__button--print').on('click touchstart', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var img = gallery.currItem.src.split('\\').pop().split('/').pop();
    photoBooth.printImage(img, function () {
      gallery.close();
    });
  }); // Close Gallery while Taking a Picture or Collage

  $('.closeGallery').on('click', function (e) {
    e.preventDefault();

    if (gallery) {
      if (config.dev) {
        console.log('Closing Gallery');
      }

      gallery.close();
    }
  }); // chroma keying print

  $('.pswp__button--print-chroma-keying').on('click touchstart', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var img = gallery.currItem.src.split('\\').pop().split('/').pop();

    if (config.chroma_keying) {
      location = 'chromakeying.php?filename=' + encodeURI(img);
    }
  });
  $('.pswp__button--mail').on('click touchstart', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var img = gallery.currItem.src.split('\\').pop().split('/').pop();
    photoBooth.toggleMailDialog(img);
  });
  /* slideshow management */

  $(ssButtonClass).on('click touchstart', function (e) {
    e.preventDefault();
    e.stopPropagation(); // toggle slideshow on/off

    $('.pswp__button--playpause').toggleClass('fa-play fa-pause');
    setSlideshowState(this, !ssRunning);
  });

  function setSlideshowState(el, running) {
    if (running) {
      setTimeout(gotoNextSlide, ssDelay / 2.0);
    }

    var title = running ? 'Pause Slideshow' : 'Play Slideshow';
    $(el).prop('title', title);
    ssRunning = running;
  }

  function gotoNextSlide() {
    if (ssRunning && Boolean(gallery)) {
      ssOnce = true;
      gallery.next();
    }
  }

  $(gallerySelector).on('click', onThumbnailClick);
}