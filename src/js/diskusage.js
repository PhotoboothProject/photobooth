/* globals photoboothTools */
$(function () {
    $('.download-zip-btn').on('click', function (e) {
        e.preventDefault();
        photoboothTools.modal.open('#save_mesg');
        const data = {type: 'zip'};
        $.ajax({
            url: '../api/diskusage.php',
            data: data,
            dataType: 'json',
            type: 'post',
            success: function (response) {
                photoboothTools.console.log('data', response);
                setTimeout(function () {
                    if (response.success === 'zip') {
                        $.ajax({
                            url: '../' + config.folders.archives + '/' + response.file,
                            type: 'HEAD',
                            error: function () {
                                photoboothTools.console.log('ZIP does not exist!');
                            },
                            success: function () {
                                location.href = '../' + config.folders.archives + '/' + response.file;
                            }
                        });
                    }
                    photoboothTools.modal.close('#save_mesg');
                    $('.download-zip-btn').blur();
                }, 10000);
            },
            error: function (jqXHR, textStatus) {
                photoboothTools.console.log('Error while downloading: ', textStatus);
                photoboothTools.modal.close('#save_mesg');
            }
        });
    });
});
