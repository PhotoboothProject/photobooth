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

cat > /etc/sudoers.d/020_www-data-shutdown << EOF
# Photobooth Remotebuzzer shutdown button for www-data to shutdown the system
www-data ALL=(ALL) NOPASSWD: /sbin/shutdown
EOF

