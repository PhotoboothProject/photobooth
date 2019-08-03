$(function(){
  $('.panel-heading').on('click', function(){
    var panel = $(this).parents('.panel');
    var others = $(this).parents('.accordion').find('.open').not(panel);

    others.removeClass('open init');

    panel.toggleClass('open');
    panel.find('.panel-body').slideToggle();

    others.find('.panel-body').slideUp('fast');
  });

  $('.reset-btn').on('click', function(e){
    var msg = L10N['really_delete'];
    var really = confirm(msg);
    var data = {'type':'reset'};
    var elem = $(this);
    elem.addClass('saving');
    if(really) {
      $.ajax({
        'url': 'admin.php',
        'data': data,
        'dataType' : 'json',
        'type': 'post',
        'success': function(resp) {
          elem.removeClass('saving');
          elem.addClass(resp);
          
          setTimeout(function(){
            elem.removeClass('error success');
          },3000);
        }
      });
    }
  });

  $('.save-btn').on('click', function(e){
    e.preventDefault();
    var elem = $(this);
    elem.addClass('saving');
    var data = 'type=config&'+$('form').serialize();
    $.ajax({
      'url': 'admin.php',
      'data': data,
      'dataType' : 'json',
      'type': 'post',
      'success': function(resp) {
        elem.removeClass('saving');
        elem.addClass(resp);
        setTimeout(function(){
          elem.removeClass('error success');
        },2000);
      }
    });
  });
});
