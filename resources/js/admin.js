/* globals L10N */
$(function() {
    $('.panel-heading').on('click', function() {
        const panel = $(this).parents('.panel');
        const others = $(this).parents('.accordion').find('.open').not(panel);

        others.removeClass('open init');

        panel.toggleClass('open');
        panel.find('.panel-body').slideToggle();

        others.find('.panel-body').slideUp('fast');
    });

    $('.reset-btn').on('click', function() {
        const msg = L10N.really_delete;
        const really = confirm(msg);
        const data = {'type': 'reset'};
        const elem = $(this);
        elem.addClass('saving');
        if (really) {
            $.ajax({
                'url': '../api/admin.php',
                'data': data,
                'dataType': 'json',
                'type': 'post',
                'success': function(resp) {
                    elem.removeClass('saving');
                    elem.addClass(resp);

                    setTimeout(function() {
                        elem.removeClass('error success');

                        window.location.reload();
                    }, 3000);
                }
            });
        }
    });

    $('.save-btn').on('click', function(e) {
        e.preventDefault();
        const elem = $(this);
        elem.addClass('saving');
        const data = 'type=config&' + $('form').serialize();
        $.ajax({
            'url': '../api/admin.php',
            'data': data,
            'dataType': 'json',
            'type': 'post',
            'success': function(resp) {
                elem.removeClass('saving');
                elem.addClass(resp);
                setTimeout(function() {
                    elem.removeClass('error success');

                    window.location.reload();
                }, 2000);
            }
        });
    });
});
