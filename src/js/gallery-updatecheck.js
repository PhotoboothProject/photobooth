/* globals photoBooth photoboothTools */
/*
 * This script checks for new pictures in regular intervals.
 * If changes are detected, the page will automatically be reloaded.
 *
 * Needs:
 * - jQuery
 * - photoBooth & photoboothTools Javascript
 *
 * Remarks:
 * - Not made for highly demanded pages (as pages is requested regulary
 *   and would pile up in high load with thausend of users)
 * - Instead of reloading, adding the pictures directly would be an
 *   improvement, but would need further changes in gallery-templates
 *
 */

// Size of the DB - is used to determine changes
let lastDBSize = -1;
// Interval, the page is checked (/ms)
const interval = 1000 * config.gallery.db_check_time;
// URL to request for changes
const ajaxurl = config.foldersPublic.api + '/gallery.php?status';

/*
 * This function will be called if there are new pictures
 */

function dbUpdated() {
    photoboothTools.console.log('DB is updated - refreshing');
    //location.reload(true); //Alternative
    photoboothTools.reloadPage();
}

const checkForUpdates = function () {
    if (photoBooth.isTimeOutPending()) {
        // If there is user interaction, do not check for updates
        photoboothTools.console.logDev('Timeout pending, waiting to refresh the standalone gallery');

        return;
    }
    $.getJSON({
        url: ajaxurl,
        success: function (result) {
            const currentDBSize = result.dbsize;
            if (lastDBSize != currentDBSize && lastDBSize != -1) {
                dbUpdated();
            }
            lastDBSize = currentDBSize;
        }
    });
};
setInterval(checkForUpdates, interval);
