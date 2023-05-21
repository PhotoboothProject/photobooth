/* globals photoboothTools */
$(function () {
    const shellCommand = function ($mode, $filename = '') {
        const command = {
            mode: $mode,
            filename: $filename
        };

        photoboothTools.console.log('Run' + $mode);

        jQuery
            .post('../api/shellCommand.php', command)
            .done(function (result) {
                photoboothTools.console.log($mode, 'result: ', result);
            })
            .fail(function (xhr, status, result) {
                photoboothTools.console.log($mode, 'result: ', result);
            });
    };

    $('#reset-btn').on('click', function (e) {
        e.preventDefault();
        const msg = photoboothTools.getTranslation('really_delete');
        const really = confirm(msg);
        const data = 'type=reset&' + $('form').serialize();
        const elem = $(this);
        elem.addClass('saving');
        if (really) {
            $.ajax({
                url: '../api/admin.php',
                data: data,
                dataType: 'json',
                type: 'post',
                success: function (resp) {
                    elem.removeClass('saving');
                    elem.addClass(resp);

                    setTimeout(function () {
                        elem.removeClass('error success');

                        window.location.reload();
                    }, 3000);
                }
            });
        } else {
            elem.removeClass('saving');
        }
    });

    $('#save-admin-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);
        elem.addClass('saving');
        const data = 'type=config&' + $('form').serialize();
        $.ajax({
            url: '../api/admin.php',
            data: data,
            dataType: 'json',
            type: 'post',
            success: function (resp) {
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

    $('#diskusage-btn').on('click', function (e) {
        e.preventDefault();
        location.assign('/admin/diskusage');

        return false;
    });

    $('#databaserebuild-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);
        $('#databaserebuild-btn').children('.text').toggle();
        elem.addClass('saving');

        $.ajax({
            url: '../api/rebuildImageDB.php',
            success: function (resp) {
                elem.removeClass('saving');
                elem.addClass(resp);

                setTimeout(function () {
                    elem.removeClass('error success');
                    $('#databaserebuild-btn').children('.text').toggle();
                }, 2000);
            }
        });
    });

    $('#reset-print-lock-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);
        $('#reset-print-lock-btn').children('.text').toggle();
        elem.addClass('saving');

        $.ajax({
            method: 'GET',
            url: '../api/printDB.php',
            data: {
                action: 'unlockPrint'
            },
            success: (data) => {
                elem.removeClass('saving');
                const dataClass = data.success ? 'success' : 'error';
                elem.addClass(dataClass);

                setTimeout(function () {
                    elem.removeClass('error success');
                    $('#reset-print-lock-btn').children('.text').toggle();
                }, 2000);
            }
        });
    });

    $('#checkversion-btn').on('click', function (ev) {
        ev.preventDefault();
        const elem = $(this);
        $('#checkversion-btn').children('.text').toggle();
        elem.addClass('saving');

        $.ajax({
            url: '../api/checkVersion.php',
            method: 'GET',
            success: (data) => {
                $('#checkVersion').empty();
                photoboothTools.console.log('data', data);
                if (!data.updateAvailable) {
                    $('#current_version_text').text(photoboothTools.getTranslation('using_latest_version'));
                } else if (/^\d+\.\d+\.\d+$/u.test(data.availableVersion)) {
                    $('#current_version_text').text(photoboothTools.getTranslation('current_version'));
                    $('#current_version').text(data.currentVersion);
                    $('#available_version_text').text(photoboothTools.getTranslation('available_version'));
                    $('#available_version').text(data.availableVersion);
                } else {
                    $('#current_version_text').text(photoboothTools.getTranslation('test_update_available'));
                }

                elem.removeClass('saving');
                elem.addClass('success');

                setTimeout(function () {
                    elem.removeClass('error success');
                    $('#checkversion-btn').children('.text').toggle();
                }, 2000);
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking Version: ', jqXHR.responseText);

                elem.removeClass('saving');
                elem.addClass('error');

                setTimeout(function () {
                    elem.removeClass('error success');
                    $('#checkversion-btn').children('.text').toggle();
                }, 2000);
            }
        });
    });

    $('#debugpanel-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('/admin/debug');

        return false;
    });

    $('#translate-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('https://crowdin.com/project/photobooth');

        return false;
    });

    $('#reboot-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('reboot');

        return false;
    });

    $('#shutdown-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('shutdown');

        return false;
    });

    // Admin Panel active section at init
    $('#nav-general').addClass('active');

    /*
     * Check if element is visible in current viewport on screen
     * https://www.javascripttutorial.net/dom/css/check-if-an-element-is-visible-in-the-viewport
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();

        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Range slider - dynamically update value when being moved
    $(document).on('input', '.configslider', function () {
        document.querySelector('#' + this.name.replace('[', '\\[').replace(']', '\\]') + '-value span').innerHTML =
            this.value;
    });

    /*
     * Install waypoints for each section, to enable dynamic nav bar
     * FIXME - trigger waypoints for settings at bottom of screen (those WPs never trigger automatically)
     */
    $('.setting_section').waypoint({
        handler: function (direction) {
            $('.adminnavlistelement').removeClass('active');
            $('#nav-' + this.element.id).addClass('active');

            const contentpage = document.getElementById('adminsidebar');
            const elemTarget = document.getElementById('nav-' + this.element.id);

            if (!isInViewport(elemTarget)) {
                const topPos = elemTarget.offsetTop;
                let newPos = 0;

                if (direction == 'down') {
                    newPos = topPos + elemTarget.offsetHeight - window.innerHeight;
                } else {
                    newPos = topPos;
                }
                contentpage.scrollTop = newPos;
            }
        }
    });

    // Click on nav bar element scrolls settings content page
    $('.adminnavlistelement').click(function (e) {
        e.preventDefault();

        // on small screens, hide navbar after click
        if (window.matchMedia('screen and (max-width: 700px)').matches) {
            $('div.adminsidebar').toggle();
        }

        const target = $(this).attr('href');

        // scroll content page if we need to
        const contentpage = document.getElementById('admincontentpage');
        const elemTarget = document.getElementById(target.substring(1));

        const totalPageHeight = contentpage.scrollHeight;
        const scrollPoint = window.scrollY + window.innerHeight;

        if (isInViewport(elemTarget) && scrollPoint >= totalPageHeight) {
            $('.adminnavlistelement').removeClass('active');
            $('#' + this.id).addClass('active');

            return false;
        }

        $('html, body').animate(
            {
                // sroll element to 5em below top - and have to disable eslint rule because prettier removes unnecessary but clarifying parenthesis
                // eslint-disable-next-line no-mixed-operators
                scrollTop: $(target).offset().top - 5 * parseInt(config.ui.font_size, 10)
            },
            1000,
            () => {
                $('.adminnavlistelement').removeClass('active');
                $('#' + this.id).addClass('active');

                // scroll nav bar if needed
                const cp = document.getElementById('adminsidebar');
                const eT = document.getElementById(this.id);

                if (!isInViewport(eT)) {
                    const viewportOffset = elemTarget.getBoundingClientRect();
                    let newPos = 0;

                    if (viewportOffset.top < 0) {
                        newPos = eT.offsetTop;
                    } else {
                        newPos = window.innerHeight - eT.offsetHeight;
                    }
                    cp.scrollTop = newPos;
                }

                return false;
            }
        );

        return false;
    });

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

    // activate selectize library for multi-selects
    $('.multi-select').selectize({});

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

    // logout button for topnavbar (small screen sizes)
    $('#admintopnavbarlogout').on('click', function () {
        location.assign('../login/logout.php');
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
});
