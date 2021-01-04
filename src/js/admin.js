/* globals i18n */
$(function () {
    $('.reset-btn').on('click', function () {
        const msg = i18n('really_delete');
        const really = confirm(msg);
        const data = {type: 'reset'};
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

    $('.save-btn').on('click', function (e) {
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

    $('#checkVersion a').on('click', function (ev) {
        ev.preventDefault();

        $(this).html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>');

        $.ajax({
            url: '../api/checkVersion.php',
            method: 'GET',
            success: (data) => {
                let message = 'Error';
                $('#checkVersion').empty();
                console.log('data', data);
                if (!data.updateAvailable) {
                    message = i18n('using_latest_version');
                } else if (/^\d+\.\d+\.\d+$/u.test(data.availableVersion)) {
                    message = i18n('update_available');
                } else {
                    message = i18n('test_update_available');
                }

                const textElement = $('<p>');
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
        // console.log('slider moves - query #' + this.name + '-value');
        document.querySelector('#' + this.name + '-value span').innerHTML = this.value;
    });

    /*
     * Install waypoints for each section, to enable dynamic nav bar
     * FIXME - trigger waypoints for settings at bottom of screen (those WPs never trigger automatically)
     */
    $('.setting_section').waypoint({
        handler: function (direction) {
            //console.log('waypoint triggered ' + this.element.id + ' - scroll direction is ' + direction);
            $('.adminnavlistelement').removeClass('active');
            $('#nav-' + this.element.id).addClass('active');

            const contentpage = document.getElementById('adminsidebar');
            const elemTarget = document.getElementById('nav-' + this.element.id);

            if (!isInViewport(elemTarget)) {
                const topPos = elemTarget.offsetTop;
                let newPos = 0;

                if (direction == 'down') {
                    newPos = topPos + elemTarget.offsetHeight - window.innerHeight;
                    //console.log('Nav bar element nav-' + this.element.id + ' out of viewport - winInner:' + window.innerHeight + ' Element height:' + elemTarget.offsetHeight + '  new scroll pos:' + newPos);
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

        //console.log('nav clicked ' + this.id);

        if (this.id == 'nav-ref-main') {
            location.assign('..');

            return false;
        }

        const target = $(this).attr('href');
        //console.log('target is ' + target.substring(1));

        // scroll content page if we need to
        const contentpage = document.getElementById('admincontentpage');
        const elemTarget = document.getElementById(target.substring(1));

        const totalPageHeight = contentpage.scrollHeight;
        const scrollPoint = window.scrollY + window.innerHeight;

        //console.log('scrollY: ' + window.scrollY + '::: target scroll element: ' + elemTarget.scrollTop);

        if (isInViewport(elemTarget) && scrollPoint >= totalPageHeight) {
            $('.adminnavlistelement').removeClass('active');
            $('#' + this.id).addClass('active');

            return false;
        }

        // console.log("target element is currently not visible - need to scroll");
        $('html, body').animate(
            {
                scrollTop: $(target).offset().top
            },
            1000,
            () => {
                //console.log('callback triggered ' + this.id);
                $('.adminnavlistelement').removeClass('active');
                $('#' + this.id).addClass('active');

                // scroll nav bar if needed
                const cp = document.getElementById('adminsidebar');
                const eT = document.getElementById(this.id);

                if (!isInViewport(eT)) {
                    const viewportOffset = elemTarget.getBoundingClientRect();
                    let newPos = 0;
                    //console.log('viewportoffset.top: ' + viewportOffset.top)
                    if (viewportOffset.top < 0) {
                        newPos = eT.offsetTop;
                    } else {
                        newPos = window.innerHeight - eT.offsetHeight;
                        //console.log('Nav bar element ' + this.id + ' out of viewport - winInner:' + window.innerHeight + ' Element height:' + eT.offsetHeight + '  new scroll pos:' + newPos);
                    }
                    cp.scrollTop = newPos;
                }

                return false;
            }
        );

        return false;
    });
});
