/* globals photoboothTools */
/* exported adminsettings */
let admincount = 0;

function countreset() {
    admincount = 0;
}

// eslint-disable-next-line no-unused-vars
function adminsettings() {
    if (admincount == 3) {
        window.location.href = '/login';
    }
    photoboothTools.console.log(admincount);
    admincount++;
    setTimeout(countreset, 10000);
}
