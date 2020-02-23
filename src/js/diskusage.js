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
                    if (response === 'success') {
                        $.ajax({
                            url: '../data/out.zip',
                            type: 'HEAD',
                            error: function () {
                                //file not exists
                            },
                            success: function () {
                                location.href = '../data/out.zip';
                            }
                        });
                    }
                    $('#save_mesg').removeClass('modal--show');
                    $('.download-zip-btn').blur();
                }, 10000);
            }
        });
    });
});
