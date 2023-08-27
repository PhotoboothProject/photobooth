// eslint-disable-next-line no-unused-vars
function closeQrCodeModal() {
    $('#newQrModal').removeClass('isOpen');
    $('#imageQrCode').html('');
}

// eslint-disable-next-line no-unused-vars
function openQrCodeModal(image) {
    console.warn(image);
    getWifiQrCode();
    getImageQrCode(image);
    $('#newQrModal').addClass('isOpen');
}

function getWifiQrCode() {
    console.warn('getWifiQrCode');
    $.ajax({
        url: config.foldersPublic.api + '/controller.php',
        type: 'POST',
        data: {
            controller: 'getWifiQrCode'
        },

        success: function (e) {
            $('#wifiQrCode').html(e);
        },

        error: function () {
            alert('error');
        }
    });
}

function getImageQrCode(image) {
    console.warn('getImageQrCode');
    $.ajax({
        url: config.foldersPublic.api + '/controller.php',
        type: 'POST',
        data: {
            controller: 'getImageQrCode',
            image: image
        },

        success: function (e) {
            $('#imageQrCode').html(e);
        },

        error: function () {
            alert('error');
        }
    });
}
