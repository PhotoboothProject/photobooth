$(function () {
    $('.download-zip-btn').on('click', function (e) {
        e.preventDefault();
        $('#save_mesg').addClass('modal--show');
        const data = {type: 'zip'};
        $.ajax({
            url: '../api/diskusage.php',
            data: data,
            dataType: 'json',
            type: 'post',
            success: function (response) {
                console.log('data', response);
                setTimeout(function () {
                    if (response.success === 'zip') {
                        $.ajax({
                            url: '../' + config.folders.archives + '/' + response.file,
                            type: 'HEAD',
                            error: function () {
                                console.log('ZIP does not exist!');
                            },
                            success: function () {
                                location.href = '../' + config.folders.archives + '/' + response.file;
                            }
                        });
                    }
                    $('#save_mesg').removeClass('modal--show');
                    $('.download-zip-btn').blur();
                }, 10000);
            },
            error: function (jqXHR, textStatus) {
                console.log('Error while downloading: ', textStatus);
                $('#save_mesg').removeClass('modal--show');
            }
        });
    });
});
