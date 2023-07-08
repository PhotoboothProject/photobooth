/* globals photoboothTools */
/* exported adminsettings */
let admincount = 0;

function countreset() {
    admincount = 0;
}

// eslint-disable-next-line no-unused-vars
function adminsettings(rootPath = '') {
    if (admincount == 3) {
        window.location.href = rootPath + 'login';
    }
    photoboothTools.console.log(admincount);
    admincount++;
    setTimeout(countreset, 10000);
}
