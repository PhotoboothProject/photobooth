/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools */
let phpMaxFileSize;
document.getElementById('Upload').style.visibility = 'hidden';
// eslint-disable-next-line no-unused-vars
let loadFile = function (event) {
    document.getElementById('Upload').style.visibility = 'visible';
    let output = document.getElementById('output');
    output.src = URL.createObjectURL(event.target.files[0]);
    let imagesize = event.target.files[0].size;
    let maxfilesize = phpMaxFileSize;
    if (parseInt(maxfilesize, 10) <= parseInt(imagesize, 10)) {
        let js_Str_warn = photoboothTools.getTranslation('file_upload_max_size') + phpMaxFileSize;
        document.querySelector('warn').textContent = js_Str_warn;
        document.getElementById('Upload').style.visibility = 'hidden';
    }
    output.onload = function () {
        URL.revokeObjectURL(output.src); // free memory
    };
};
