#!/bin/bash

# Stop on the first sign of trouble
set -e

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

if [ $UID != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

if [[ ! -z $1 ]]; then
    booth_source=$1
    info "Updating Photobooth located at: ${booth_source}"
else
    error "ERROR: Usage: update-booth.sh 'Photobooth path'"
    exit 2
fi

OLDFILES=(
    'login.php'
    'logout.php'
    'admin/config.json'
    'resources/fonts/style.css'
    'resources/js/l10n.js'
    'resources/lang/de.js'
    'resources/lang/en.js'
    'resources/lang/es.js'
    'resources/lang/fr.js'
    'resources/lang/gr.js'
)

if [[ ! -d "${booth_source}" ]]; then
    mkdir -p "${booth_source}"
fi

cp -rf ./* "${booth_source}/"
chown -R www-data:www-data ${booth_source}

for file in "${OLDFILES[@]}"; do
    if [ -f "${booth_source}/${file}" ]; then
        info "Deleting unused file: ${booth_source}/${file}"
        rm "${booth_source}/${file}"
    fi
done

info "Updated Photobooth"
