$(function () {

    // init navi item
    getInitialNaviItem();
    initDebugcontent();

    // item click
    $('.navItem').on('click', function () {
        setNaviItem($(this)[0].id.replace('nav-', '') );
    });
    
});

function isElementInViewport (el) {
    var offset = 40;
    if( $('#activeTabLabel').is(':visible') ) {
        offset = 100;
    }
    if (typeof jQuery === "function" && el instanceof jQuery) {
        el = el[0];
    }
    var rect = el.getBoundingClientRect();
    var isActive = rect.top <= offset && rect.top >= -el.clientHeight;

    if(isActive){
        return (
            true
        );
    }
}
$(".adminContent").on("scroll", function() {
    $(".adminSection.visible").each(function (idx, el) {
        if ( isElementInViewport(el) ) {
            var hash = window.location.hash; 
            var urlHash = "#" + el.id;
            if( hash != urlHash ) {
                window.history.pushState(null, null, urlHash);
                setNaviItem( el.id );
            }
        }
    });
});

function getInitialNaviItem() {
    if (!$('.navItem')[0]){
        return;
    }
    var urlHash = window.location.hash;
    var hash = urlHash.replace('#', '');
    if (hash) {
        setNaviItem(hash);
        $(urlHash)[0].scrollIntoView();
    } else {
        $('.navItem').removeClass('isActive');
        $('.navItem').first().addClass('isActive');
        var itemName = $('.navItem.isActive').find('.naviLabel').html();
        $( '#activeTabLabel').html( itemName );
    }
}

function setNaviItem( item ) {
    $('.navItem').removeClass('isActive');
    $('#nav-' + item).addClass('isActive');

    var itemName = $('.navItem.isActive').find('.naviLabel').html();
    $( '#activeTabLabel').html( itemName );

    var top = $('#nav-' + item).offset().top;
    var height = $(window).height();

    if( top <= height || top >= height ) {
        $('#nav-' + item).parents()[0].scrollIntoView({ block: "end" });
    }

}

function toggleAdminNavi() {
    if( $('.adminNavi').hasClass('isActive') ) {
        $('.adminNavi').removeClass('isActive');
    } else {
        $('.adminNavi').addClass('isActive');
    }
}

function setDebugNavItemActive( elem ) {
    $( '.debugNavItem' ).removeClass('isActive');
    $( elem ).addClass('isActive');
    $('.adminNavi').removeClass('isActive');
}


function initDebugcontent() {
    if (!$('.debugNavItem')[0]){
        return;
    } else {
        $('.debugNavItem').first().trigger( "click" );
    }
}
