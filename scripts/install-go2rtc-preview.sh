#!/bin/bash

GO2RTC_VERSION="v1.8.6-4"
YAML_STREAM="dslr: exec:gphoto2 --capture-movie --stdout#killsignal=sigint"
CAPTURE_CMD="gphoto2"
CAPTURE_ARGS="--set-config output=Off --capture-image-and-download --filename=\$1"
NOTE="don't forget to add --filename=%s."

function info {
    echo -e "\033[0;36m${1}\033[0m"
}

function error {
    echo -e "\033[0;31m${1}\033[0m"
}

if [ "$UID" != 0 ]; then
    error "ERROR: Only root is allowed to execute the installer. Forgot sudo?"
    exit 1
fi

#Param 1: Question / Param 2: Default / silent answer
function ask_yes_no {
    read -p "${1}: " -n 1 -r
}

function mjpeg_preview() {
    local arch
    local goarch
    local os
    local file

    if ! command -v go2rtc &>/dev/null || [[ ! $(go2rtc -version) =~ $GO2RTC_VERSION ]]; then
        info "### Installing go2rtc (version: ${GO2RTC_VERSION})"

        if [[ "$OSTYPE" =~ linux ]]; then
            os=linux
        elif [[ "$OSTYPE" =~ darwin ]]; then
            os=darwin
        elif [[ "$OSTYPE" =~ cygwin|mysys|win32 ]]; then
            os=windows
        else
            error "### $OSTYPE not supported"
            exit 1
        fi

        arch=$(uname -m)
        if [[ "$arch" == "x86_64" ]]; then
            goarch="amd64"
        elif [[ "$arch" == "i386" ]]; then
            goarch="386"
        elif [[ "$arch" == "armv7l" ]]; then
            goarch="armv7"
        elif [[ "$arch" == "armv6l" ]]; then
            goarch="armv6"
        elif [[ "$arch" == "aarch64" ]]; then
            goarch="arm64"
        else
            error "### $arch not supported"
            exit 1
        fi

        if [[ ! -d /usr/local/bin ]]; then
            mkdir -p /usr/local/bin
        fi
        file="go2rtc_${os}_${goarch}.tar.gz"
        wget -P /tmp "https://github.com/dadav/go2rtc/releases/download/${GO2RTC_VERSION}/${file}"
        tar xf "/tmp/${file}" -C /usr/local/bin go2rtc
        rm /tmp/"$file"
        chmod +x /usr/local/bin/go2rtc
    fi

    if [[ ! -f /etc/go2rtc.yaml ]]; then
        info "### Creating /etc/go2rtc.yaml configuration file"
        cat >/etc/go2rtc.yaml <<EOF
---
streams:
  $YAML_STREAM
EOF
    fi

    if [[ ! -f /etc/systemd/system/go2rtc.service ]]; then
        info "### Creating go2rtc systemd service"
        cat >/etc/systemd/system/go2rtc.service <<EOF
[Unit]
Description=go2rtc streaming software

[Service]
User=www-data
ExecStart=/usr/local/bin/go2rtc -config /etc/go2rtc.yaml
KillMode=process
KillSignal=SIGINT

[Install]
WantedBy=multi-user.target
EOF
        systemctl daemon-reload
        systemctl enable --now go2rtc.service
    fi

    if [[ ! -f /etc/sudoers.d/020_www-data-systemctl ]]; then
        info "### Creating /etc/sudoers.d/020_www-data-systemctl"
        cat >/etc/sudoers.d/020_www-data-systemctl <<EOF
# Control streaming software
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl start go2rtc.service, /usr/bin/systemctl stop go2rtc.service
EOF
    fi

    if [[ ! -f /usr/local/bin/capture ]]; then
        info "### Creating /usr/local/bin/capture script"
        cat >/usr/local/bin/capture <<EOF
#!/bin/bash

if [[ \$1 =~ -h|--help ]]; then
  cat <<HELP
This script stops go2rtc, runs $CAPTURE_CMD and starts go2rtc again.
You can use it in your photobooth as capture command.

Usage:

    capture <filename> [or all required $CAPTURE_CMD arguments]

In photobooth, usually 'capture %s' is enough. But if you want to use a more complex command,
$NOTE

HELP
  exit 0
fi

if [[ \$# -eq 1 ]]; then
    args="$CAPTURE_ARGS"
elif [[ \$# -gt 1 ]]; then
    args="\$@"
fi

if systemctl cat go2rtc.service >/dev/null; then
    HAS_GO2RTC=1
fi

[[ -n "\$HAS_GO2RTC" ]] && sudo systemctl stop go2rtc.service
$CAPTURE_CMD \$args
[[ -n "\$HAS_GO2RTC" ]] && sudo systemctl start go2rtc.service
EOF
        chmod +x /usr/local/bin/capture
    fi

    info "### Done!"
    info ""
    info "    Please adjust your Photobooth configuration:"
    info "    Preview mode: from URL"
    info "    Preview-URL: url(\"http://localhost:1984/api/stream.mjpeg?src=dslr\")"
    info "    Take picture command: capture %s"
    warn "    Note: Countdown for pictures and collage should be set to a minimum of 6 seconds!"
    info ""
    info "### Have fun with your Photobooth, but first restart your device!"

    echo -e "\033[0;33m"
    ask_yes_no "### Do you like to reboot now? [y/N] " "N"
    echo -e "\033[0m"
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        info "### Your device will reboot now."
        shutdown -r now
    fi
}

function uninstall() {
    info "### Uninstalling the camera streaming service"

    if systemctl is-active --quiet go2rtc.service; then
        systemctl stop go2rtc.service
    fi

    systemctl disable go2rtc.service
    rm -f /etc/systemd/system/go2rtc.service
    systemctl daemon-reload

    rm -f /etc/go2rtc.yaml
    rm -f /usr/local/bin/go2rtc
    rm -f /usr/local/bin/capture
    rm -f /etc/sudoers.d/020_www-data-systemctl

    info "### Uninstallation complete!"
}

info "Do you want to install a service to"
info "be able to stream your camera to via?"
info ""
echo "Your options are:"
echo "1 Install go2rtc and needed service for gphoto2"
echo "2 Install go2rtc and needed service for rpicam-apps"
echo "3 Install go2rtc and needed service for libcamera-apps"
echo "4 Uninstall go2rtc and the related services"
echo "5 Do nothing"
info ""
ask_yes_no "Please enter your choice:" "5"
info ""
if [[ $REPLY =~ ^[1]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for gphoto2."
elif [[ $REPLY =~ ^[2]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for rpicam-apps."
    YAML_STREAM="dslr: exec:rpicam-vid -t 0 --codec mjpeg -o -#killsignal=sigint"
    CAPTURE_CMD="rpicam-still"
    CAPTURE_ARGS="-n -q 100 -t 1 -o \$1"
    NOTE="don't forget to add -o %s."
elif [[ $REPLY =~ ^[3]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for libcamera-apps."
    YAML_STREAM="dslr: exec:libcamera-vid -t 0 --codec mjpeg -o -#killsignal=sigint"
    CAPTURE_CMD="libcamera-still"
    CAPTURE_ARGS="-n -q 100 -t 1 -o \$1"
    NOTE="don't forget to add -o %s."
elif [[ $REPLY =~ ^[4]$ ]]; then
    uninstall
    exit 0
else
    info "Okay... doing nothing!"
    exit 0
fi

mjpeg_preview
info "Done!"
exit 0
