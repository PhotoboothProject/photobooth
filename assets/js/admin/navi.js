$(function () {
    // init navi item
    getInitialNaviItem();

    // item click
    $('.navItem').on('click', function () {
        setNaviItem($(this)[0].id.replace('nav-', ''));
    });
});

function isElementInViewport(el) {
    let offset = 40;
    if ($('#activeTabLabel').is(':visible')) {
        offset = 100;
    }
    if (typeof jQuery === 'function' && el instanceof jQuery) {
        el = el[0];
    }
    const rect = el.getBoundingClientRect();
    const isActive = rect.top <= offset && rect.top >= -el.clientHeight;

    if (isActive) {
        return true;
    }

    return false;
}
$('.adminContent').on('scroll', function () {
    $('.adminSection.visible').each(function (idx, el) {
        if (isElementInViewport(el)) {
            const hash = window.location.hash;
            const urlHash = '#' + el.id;
            if (hash != urlHash) {
                window.history.pushState(null, null, urlHash);
                setNaviItem(el.id);
            }
        }
    });
});

function getInitialNaviItem() {
    if ($('.navItem')[0]) {
        const urlHash = window.location.hash;
        const hash = urlHash.replace('#', '');
        if (hash) {
            setNaviItem(hash);
            $(urlHash)[0].scrollIntoView();
        } else {
            $('.navItem').removeClass('isActive');
            $('.navItem').first().addClass('isActive');
            const itemName = $('.navItem.isActive').find('.naviLabel').html();
            $('#activeTabLabel').html(itemName);
        }
    }
}

function setNaviItem(item) {
    $('.navItem').removeClass('isActive');
    $('#nav-' + item).addClass('isActive');

    const itemName = $('.navItem.isActive').find('.naviLabel').html();
    $('#activeTabLabel').html(itemName);

    const top = $('#nav-' + item).offset().top;
    const height = $(window).height();

    if (top <= height || top >= height) {
        $('#nav-' + item)
            .parents()[0]
            .scrollIntoView({ block: 'end' });
    }
}

// eslint-disable-next-line no-unused-vars
function toggleAdminNavi() {
    if ($('.adminNavi').hasClass('isActive')) {
        $('.adminNavi').removeClass('isActive');
    } else {
        $('.adminNavi').addClass('isActive');
    }
}
