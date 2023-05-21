$(function () {
    let autoRefreshActive = false;

    // Click on nav bar element populates content page
    $('.debugNavItem').on('click', function () {
        e.preventDefault();
        $('.adminnavlistelement').removeClass('active');
        $(this).addClass('active');

        $.ajax({
            url: '../api/serverInfo.php',
            method: 'GET',
            dataType: 'text',
            data: {
                content: this.id
            },
            success: (data) => {
                $('.debugcontent').html('<pre>' + data + '</pre>');
                if (autoRefreshActive) {
                    $('html,body').animate({scrollTop: $('#admincontentpage').height()}, 0);
                }
            },
            error: (jqXHR) => {
                $('.debugcontent').html('ERROR: No data available - Server Response <' + jqXHR.responseText);
            }
        });

        return false;
    });

    // menu toggle button for topnavbar (small screen sizes)
    $('#admintopnavbarmenutoggle').on('click', function () {
        $('.adminsidebar').toggle();
        if (window.matchMedia('screen and (min-width: 701px)').matches) {
            if ($('#adminsidebar').is(':visible')) {
                $('#admincontentpage').css('margin-left', '200px');
            } else {
                $('#admincontentpage').css('margin-left', '0px');
            }
        }
    });

    // back  button for topnavbar (small screen sizes)
    $('#admintopnavbarback').on('click', function () {
        location.assign('../');
    });

    // check padding of settings content on window resize
    window.addEventListener('resize', onWindowResize);
    function onWindowResize() {
        if (window.matchMedia('screen and (max-width: 700px)').matches) {
            $('#admincontentpage').css('margin-left', '0px');
        } else if ($('#adminsidebar').is(':visible')) {
            $('#admincontentpage').css('margin-left', '200px');
        }
    }

    // autorefresh button
    $('#debugpanel_autorefresh').on('click', function () {
        if ($('input', this).is(':checked') === autoRefreshActive) {
            // filter double events where no change
            return;
        }

        autoRefreshActive = $('input', this).is(':checked');
        autoRefresh();
    });

    // autorefresh function
    const autoRefresh = function () {
        if (!autoRefreshActive) {
            return false;
        }

        $('.adminnavlistelement.active').trigger('click');

        setTimeout(autoRefresh, 1000);

        return true;
    };

    // Localization of toggle button text
    $('.toggle').click(function () {
        if ($('input', this).is(':checked')) {
            $('.toggleTextON', this).removeClass('hidden');
            $('.toggleTextOFF', this).addClass('hidden');
        } else {
            $('.toggleTextOFF', this).removeClass('hidden');
            $('.toggleTextON', this).addClass('hidden');
        }
    });
});
