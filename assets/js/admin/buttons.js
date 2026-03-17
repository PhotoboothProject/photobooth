/* eslint n/no-unsupported-features/node-builtins: "off" */
/* globals photoboothTools shellCommand csrf */
$(function () {
    // Highlight save button on form changes
    const $saveButton = $('#save-admin-btn');
    const initialSerialized = $('form').serialize();

    $(document).on('change input', 'form :input', function () {
        const currentSerialized = $('form').serialize();

        if (currentSerialized !== initialSerialized) {
            $saveButton.addClass('isDirty');
        } else {
            $saveButton.removeClass('isDirty');
        }
    });

    $('#reset-btn').on('click', function (e) {
        e.preventDefault();
        const msg = photoboothTools.getTranslation('really_delete');
        const really = confirm(msg);
        const elem = $(this);
        elem.addClass('saving');
        if (really) {
            // show loader
            $('.pageLoader').addClass('isActive');
            $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

            const data = new FormData(document.querySelector('form'));
            data.append('type', 'reset');
            if (typeof csrf !== 'undefined') {
                data.append(csrf.key, csrf.token);
            }

            fetch('../api/admin.php', {
                method: 'POST',
                body: data
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        photoboothTools.console.logDev(data.message);
                        window.location.reload();
                    }
                })
                .catch((error) => {
                    photoboothTools.console.logDev('Error:', error);
                });
        } else {
            elem.removeClass('saving');
        }
    });

    $('#save-admin-btn').on('click', function (e) {
        e.preventDefault();

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('saving'));

        const data = new FormData(document.querySelector('form'));
        data.append('type', 'config');
        if (typeof csrf !== 'undefined') {
            data.append(csrf.key, csrf.token);
        }

        fetch('../api/admin.php', {
            method: 'POST',
            body: data
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    photoboothTools.console.logDev(data.message);
                    window.location.reload();
                }
            })
            .catch((error) => {
                photoboothTools.console.logDev('Error:', error);
            });
    });

    $('#screensaver-preview-btn').on('click', function (e) {
        e.preventDefault();
        window.open('../?screensaverPreview=1', '_blank');
        return false;
    });

    $('#layout-generator').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/generator');

        return false;
    });

    // FTP Folder Browser
    function ftpGetFormData() {
        const formData = $('form').serializeArray();
        if (typeof csrf !== 'undefined') {
            formData.push({ name: csrf.key, value: csrf.token });
        }
        return formData;
    }

    function ftpGetParentPath(path) {
        if (path === '/' || path === '') {
            return '/';
        }
        const trimmed = path.replace(/\/+$/, '');
        const lastSlash = trimmed.lastIndexOf('/');
        return lastSlash <= 0 ? '/' : trimmed.substring(0, lastSlash);
    }

    function ftpLoadFolders(path) {
        const $container = $('#ftp-folder-browser');
        const $list = $container.find('.ftp-folder-list');
        $list.html(
            '<div class="flex items-center justify-center py-4 text-gray-400"><i class="fa fa-spinner fa-spin mr-2"></i></div>'
        );
        $container.removeClass('hidden');

        const formData = ftpGetFormData();
        formData.push({ name: 'path', value: path });

        $.ajax({
            url: '../api/ftpListFolders.php',
            dataType: 'json',
            data: formData,
            type: 'post',
            success: (resp) => {
                if (resp.error) {
                    $list.html('<div class="text-sm text-red-400 py-2">' + resp.error + '</div>');
                    return;
                }

                let html = '';

                // Toolbar: back button + current path
                html += '<div class="flex items-center gap-2 mb-3">';
                if (path !== '/') {
                    html +=
                        '<button type="button" class="ftp-nav-folder flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-gray-200 hover:bg-brand-1 hover:text-white text-gray-600 transition" data-path="' +
                        ftpGetParentPath(path) +
                        '" title="' +
                        photoboothTools.getTranslation('back') +
                        '">';
                    html += '<i class="fa fa-arrow-left"></i>';
                    html += '</button>';
                }
                // Breadcrumb path display
                const parts = path === '/' ? [] : path.replace(/^\//, '').split('/');
                html += '<div class="flex items-center gap-0.5 text-sm text-gray-500 overflow-x-auto flex-1 min-w-0">';
                html +=
                    '<button type="button" class="ftp-nav-folder px-1.5 py-0.5 rounded hover:bg-gray-200 hover:text-brand-1 font-semibold flex-shrink-0 transition" data-path="/">/</button>';
                let buildPath = '';
                for (let i = 0; i < parts.length; i++) {
                    buildPath += '/' + parts[i];
                    html += '<span class="text-gray-300 flex-shrink-0">/</span>';
                    const isLast = i === parts.length - 1;
                    html +=
                        '<button type="button" class="ftp-nav-folder px-1.5 py-0.5 rounded hover:bg-gray-200 hover:text-brand-1 flex-shrink-0 transition ' +
                        (isLast ? 'font-bold text-gray-700' : 'font-semibold') +
                        '" data-path="' +
                        buildPath +
                        '">' +
                        parts[i] +
                        '</button>';
                }
                html += '</div>';
                html += '</div>';

                // Select current folder button (only when not at root)
                if (path !== '/') {
                    const folderValue = path.replace(/^\//, '');
                    html +=
                        '<button type="button" class="ftp-select-folder w-full text-left px-3 py-2.5 mb-3 rounded-lg bg-brand-1 text-white text-sm font-semibold hover:bg-brand-1/80 transition flex items-center gap-2" data-folder="' +
                        folderValue +
                        '">';
                    html += '<i class="fa fa-check"></i>';
                    html +=
                        '<span>' +
                        photoboothTools.getTranslation('ftp:select_folder') +
                        ': <strong>' +
                        path +
                        '</strong></span>';
                    html += '</button>';
                }

                // Folder list
                if (resp.folders.length === 0 && path !== '/') {
                    html +=
                        '<div class="text-sm text-gray-400 py-2 text-center">' +
                        photoboothTools.getTranslation('ftp:no_subfolders') +
                        '</div>';
                } else if (resp.folders.length === 0) {
                    html +=
                        '<div class="text-sm text-gray-400 py-2 text-center">' +
                        photoboothTools.getTranslation('ftp:no_subfolders') +
                        '</div>';
                } else {
                    html += '<div class="flex flex-col gap-1">';
                    resp.folders.forEach((folder) => {
                        const folderName = folder.split('/').pop();
                        const fullPath = '/' + folder;
                        html +=
                            '<div class="flex items-center rounded-lg border border-gray-200 hover:border-brand-1 hover:bg-blue-50 transition overflow-hidden">';
                        // Clickable folder row (navigate into)
                        html +=
                            '<button type="button" class="ftp-nav-folder flex-1 flex items-center gap-3 px-3 py-2.5 text-left text-sm hover:text-brand-1 transition min-w-0" data-path="' +
                            fullPath +
                            '">';
                        html += '<i class="fa fa-folder text-brand-1 text-base flex-shrink-0"></i>';
                        html += '<span class="truncate">' + folderName + '</span>';
                        html += '<i class="fa fa-chevron-right text-gray-300 ml-auto flex-shrink-0"></i>';
                        html += '</button>';
                        // Select button
                        html +=
                            '<button type="button" class="ftp-select-folder flex-shrink-0 px-3 py-2.5 text-xs font-semibold text-brand-1 hover:bg-brand-1 hover:text-white border-l border-gray-200 transition" data-folder="' +
                            folder +
                            '">';
                        html += photoboothTools.getTranslation('ftp:select_folder');
                        html += '</button>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                $list.html(html);
            },
            error: () => {
                $list.html('<div class="text-sm text-red-400 py-2">Failed to load folders</div>');
            }
        });
    }

    function ftpEnsureBrowseButton() {
        const $baseFolderCard = $('[name="ftp\\[baseFolder\\]"]').closest('.adminSettingCard');
        if ($baseFolderCard.length === 0) {
            return;
        }

        // Add browse button next to baseFolder input if not already present
        if ($('#ftp-browse-btn').length === 0) {
            const $input = $baseFolderCard.find('input[name="ftp\\[baseFolder\\]"]');
            $input.wrap('<div class="flex items-center gap-2 w-full"></div>');
            $input.after(
                '<button type="button" id="ftp-browse-btn" class="hidden flex-shrink-0 h-10 px-3 rounded-lg bg-brand-1 text-white text-sm font-semibold hover:bg-brand-1/80 transition flex items-center gap-2">' +
                    '<i class="fa fa-folder-open"></i> ' +
                    photoboothTools.getTranslation('ftp:browse_folders') +
                    '</button>'
            );
        }

        // Add folder browser container below baseFolder card if not present
        if ($('#ftp-folder-browser').length === 0) {
            let browserHtml =
                '<div id="ftp-folder-browser" class="hidden mt-4 p-4 rounded-xl border-2 border-brand-1/30 bg-gray-50">';
            browserHtml += '<div class="flex items-center justify-between mb-3">';
            browserHtml +=
                '<h4 class="text-sm font-bold text-brand-1"><i class="fa fa-folder-open mr-2"></i>' +
                photoboothTools.getTranslation('ftp:browse_folders') +
                '</h4>';
            browserHtml +=
                '<button type="button" id="ftp-folder-browser-close" class="text-gray-400 hover:text-gray-700 text-lg leading-none">&times;</button>';
            browserHtml += '</div>';
            browserHtml += '<div class="ftp-folder-list"></div>';
            browserHtml += '</div>';
            $baseFolderCard.append(browserHtml);

            $('#ftp-folder-browser-close').on('click', function () {
                $('#ftp-folder-browser').addClass('hidden');
            });
        }

        // Show the browse button
        $('#ftp-browse-btn').removeClass('hidden');
    }

    // Delegated click handlers for folder browser
    $(document).on('click', '.ftp-nav-folder', function (e) {
        e.preventDefault();
        ftpLoadFolders($(this).data('path'));
    });
    $(document).on('click', '.ftp-select-folder', function (e) {
        e.preventDefault();
        const folder = $(this).data('folder');
        $('[name="ftp\\[baseFolder\\]"]').val(folder).trigger('change');
        $('#ftp-folder-browser').addClass('hidden');
    });
    $(document).on('click', '#ftp-browse-btn', function (e) {
        e.preventDefault();
        ftpLoadFolders('/');
    });

    $('#test-connection').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('checking'));

        $.ajax({
            url: '../api/testFtpConnection.php',
            dataType: 'json',
            data: ftpGetFormData(),
            type: 'post',
            success: (resp) => {
                photoboothTools.console.log('resp', resp);

                resp.missing.forEach((el) => {
                    photoboothTools.console.log(el);
                    $('#ftp\\:' + el).addClass('required');
                });
                alert(photoboothTools.getTranslation(resp.message));

                // On successful connection, enable folder browser at baseFolder field
                if (resp.response === 'success') {
                    ftpEnsureBrowseButton();
                }
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking FTP connection: ', jqXHR.responseText);
            },

            complete: (jqXHR, textStatus) => {
                const status = jqXHR.status;
                const resp = jqXHR.responseJSON;
                let classes = 'isActive isSuccess';
                let findClasses = '.success span';
                if (status != 200 || !resp || resp.response != 'success' || textStatus != 'success') {
                    classes = 'isActive isError';
                    findClasses = '.error span';
                }

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass(classes);
                const msg = elem.find(findClasses).html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#diskusage-btn').on('click', function (e) {
        e.preventDefault();
        location.assign('../admin/diskusage');

        return false;
    });

    $('#databaserebuild-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('busy'));

        $.ajax({
            url: '../api/rebuildImageDB.php',
            data: { [csrf.key]: csrf.token },
            // eslint-disable-next-line no-unused-vars
            success: function (resp) {
                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isSuccess');
                const msg = elem.find('.success span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 3000);
            }
        });
    });

    $('#checkversion-btn').on('click', function (ev) {
        ev.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('checking'));

        $.ajax({
            url: '../api/checkVersion.php',
            method: 'GET',
            data: { [csrf.key]: csrf.token },
            success: (data) => {
                $('#checkVersion').empty();
                photoboothTools.console.log('data', data);
                if (!data.updateAvailable) {
                    $('#current_version_text').text(photoboothTools.getTranslation('using_latest_version'));
                } else if (/^\d+\.\d+\.\d+$/u.test(data.availableVersion)) {
                    $('#current_version_text').text(photoboothTools.getTranslation('current_version'));
                    $('#current_version').text(data.currentVersion);
                    $('#available_version_text').text(photoboothTools.getTranslation('available_version'));
                    $('#available_version').text(data.availableVersion);
                } else {
                    $('#current_version_text').text(photoboothTools.getTranslation('test_update_available'));
                }

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isSuccess');
                const msg = elem.find('.success span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            },

            error: (jqXHR) => {
                photoboothTools.console.log('Error checking Version: ', jqXHR.responseText);

                $('.pageLoader').removeClass('isActive');
                $('.adminToast').addClass('isActive isError');
                const msg = elem.find('.error span').html();
                $('.adminToast').find('.headline').html(msg);

                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#reset-print-lock-btn').on('click', function (e) {
        e.preventDefault();
        const elem = $(this);

        // show loader
        $('.pageLoader').addClass('isActive');
        $('.pageLoader').find('label').html(photoboothTools.getTranslation('busy'));

        $.ajax({
            method: 'GET',
            url: '../api/printDB.php',
            data: {
                action: 'unlockPrint',
                [csrf.key]: csrf.token
            },
            success: (data) => {
                $('.pageLoader').removeClass('isActive');
                if (data.success) {
                    $('.adminToast').addClass('isActive isSuccess');
                    const msg = elem.find('.success span').html();
                    $('.adminToast').find('.headline').html(msg);
                } else {
                    $('.adminToast').addClass('isActive isError');
                    const msg = elem.find('.error span').html();
                    $('.adminToast').find('.headline').html(msg);
                }
                setTimeout(function () {
                    $('.adminToast').removeClass('isActive');
                }, 2000);
            }
        });
    });

    $('#soundtest-btn').on('click', function (ev) {
        ev.preventDefault();

        let audioElement = document.getElementById('testaudio');
        if (audioElement === null) {
            audioElement = document.createElement('audio');
            audioElement.id = 'testaudio';
            document.body.append(audioElement);
        }

        let soundfile = null;
        if ($('[name="sound[voice]"]').val() === 'custom') {
            soundfile =
                '/private/sounds/' +
                $('[name="sound[voice]"]').val() +
                '/counter-' +
                Math.floor(Math.random() * 10 + 1) +
                '.mp3';
        } else {
            soundfile =
                '/resources/sounds/' +
                $('[name="sound[voice]"]').val() +
                '/' +
                $('[name="ui[language]"]').val() +
                '/counter-' +
                Math.floor(Math.random() * 10 + 1) +
                '.mp3';
        }
        audioElement.src = soundfile;
        audioElement.play().catch((error) => {
            photoboothTools.console.log('Error with audio.play: ' + error);
        });
        return false;
    });

    $('#debugpanel-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/debug');

        return false;
    });

    $('#translate-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('https://crowdin.com/project/photobooth');

        return false;
    });

    $('#filesupload-btn').on('click', function (ev) {
        ev.preventDefault();
        window.open('../admin/upload');

        return false;
    });

    $('#reboot-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('reboot');

        return false;
    });

    $('#shutdown-btn').on('click', function (ev) {
        ev.preventDefault();
        shellCommand('shutdown');

        return false;
    });

    // Show FTP folder browse button on page load if FTP credentials are already configured
    const ftpHost = $('[name="ftp\\[baseURL\\]"]').val();
    const ftpUser = $('[name="ftp\\[username\\]"]').val();
    const ftpPassPlaceholder = $('[name="ftp\\[password\\]"]').attr('placeholder');
    if (ftpHost && ftpUser && ftpPassPlaceholder === '********') {
        ftpEnsureBrowseButton();
    }
});
