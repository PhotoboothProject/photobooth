"use strict";

/* globals i18n */
$(function () {
  $('.panel-heading').on('click', function () {
    var panel = $(this).parents('.panel');
    var others = $(this).parents('.accordion').find('.open').not(panel);
    others.removeClass('open init');
    panel.toggleClass('open');
    panel.find('.panel-body').slideToggle();
    others.find('.panel-body').slideUp('fast');
  });
  $('.reset-btn').on('click', function () {
    var msg = i18n('really_delete');
    var really = confirm(msg);
    var data = {
      'type': 'reset'
    };
    var elem = $(this);
    elem.addClass('saving');

    if (really) {
      $.ajax({
        'url': '../api/admin.php',
        'data': data,
        'dataType': 'json',
        'type': 'post',
        'success': function success(resp) {
          elem.removeClass('saving');
          elem.addClass(resp);
          setTimeout(function () {
            elem.removeClass('error success');
            window.location.reload();
          }, 3000);
        }
      });
    }
  });
  $('.save-btn').on('click', function (e) {
    e.preventDefault();
    var elem = $(this);
    elem.addClass('saving');
    var data = 'type=config&' + $('form').serialize();
    $.ajax({
      'url': '../api/admin.php',
      'data': data,
      'dataType': 'json',
      'type': 'post',
      'success': function success(resp) {
        elem.removeClass('saving');
        elem.addClass(resp);
        setTimeout(function () {
          elem.removeClass('error success');

          if (resp === 'success') {
            window.location.reload();
          }
        }, 2000);
      }
    });
  });
  $('#checkVersion a').on('click', function (ev) {
    ev.preventDefault();
    $(this).html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>');
    $.ajax({
      url: '../api/checkVersion.php',
      method: 'GET',
      success: function success(data) {
        var message = 'Error';
        $('#checkVersion').empty();
        console.log('data', data);

        if (!data.updateAvailable) {
          message = i18n('using_latest_version');
        } else if (/^[0-9]+\.[0-9]+\.[0-9]+$/.test(data.availableVersion)) {
          message = i18n('update_available');
        } else {
          message = i18n('test_update_available');
        }

        var textElement = $('<p>');
        textElement.text(message);
        textElement.append('<br />');
        textElement.append(i18n('current_version') + ': ');
        textElement.append(data.currentVersion);
        textElement.append('<br />');
        textElement.append(i18n('available_version') + ': ');
        textElement.append(data.availableVersion);
        textElement.appendTo('#checkVersion');
      }
    });
  });
  $('option').mousedown(function (e) {
    e.preventDefault();
    var originalScrollTop = $(this).parent().scrollTop();
    $(this).prop('selected', !$(this).prop('selected'));
    var that = this;
    $(this).parent().focus();
    setTimeout(function () {
      $(that).parent().scrollTop(originalScrollTop);
    }, 0);
    return false;
  });
});