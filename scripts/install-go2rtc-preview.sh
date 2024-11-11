#!/bin/bash

GO2RTC_VERSIONS=("1.9.2" "1.9.4" "1.9.6" "1.9.7")
GO2RTC_VERSION="1.9.6"
YAML_STREAM="photobooth: exec:gphoto2 --capture-movie --stdout#killsignal=2"
CAPTURE_CMD="gphoto2"
CAPTURE_ARGS="--set-config output=Off --capture-image-and-download --filename=\$1"
NOTE="don't forget to add --filename=%s."
UPDATE_ONLY=false

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

function test_command {
    eval "$1"
    if [ $? -ne 0 ]; then
        error "### Test failed: $1"
        error "### Preview via go2rtc can't be generated!"
        ask_yes_no "### Do you want to continue anyway? [y/N]" "N"
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "### Exiting script."
            exit 1
        else
            info "### Continuing..."
        fi
    else
        info "### Test successful: $1"
    fi
}

function ask_version() {
    echo "## Available go2rtc versions:"
    for i in "${!GO2RTC_VERSIONS[@]}"; do
        echo "$((i + 1))) ${GO2RTC_VERSIONS[i]}"
    done
    read -p "Please select a version (1-${#GO2RTC_VERSIONS[@]}): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[1-${#GO2RTC_VERSIONS[@]}]$ ]]; then
        GO2RTC_VERSION=${GO2RTC_VERSIONS[$((REPLY - 1))]}
        info "### Selected go2rtc version: $GO2RTC_VERSION"
    else
        error "### Invalid selection. Using default version: $GO2RTC_VERSION"
    fi
}

function install_go2rtc() {
    local arch
    local goarch
    local os
    local file
    local install_bin
    local installed_version

    if [[ -f /etc/systemd/system/go2rtc.service ]]; then
        if systemctl is-active --quiet go2rtc.service; then
            systemctl stop go2rtc.service
        fi
    fi

    if command -v go2rtc &>/dev/null; then
        installed_version=$(go2rtc -version 2>&1 | grep -oP 'version=\K[0-9]+\.[0-9]+\.[0-9]+' || go2rtc -version 2>&1 | grep -oP 'go2rtc version \K[0-9]+\.[0-9]+\.[0-9]+')
        if [[ $installed_version == $GO2RTC_VERSION ]]; then
            info "### go2rtc version ${GO2RTC_VERSION} installed already!"
            install_bin=false
        else
            info "### Found go2rtc version: ${installed_version}"
            info "### Updating go2rtc to version: ${GO2RTC_VERSION}"
            install_bin=true
        fi
    else
        if [ "$UPDATE_ONLY" = true ]; then
            error "### go2rtc not installed! Can not update!"
            exit 1
        fi
        info "### Installing go2rtc (version: ${GO2RTC_VERSION})"
        install_bin=true
    fi

    if [ "$install_bin" = true ]; then
        is_zip=false
        if [[ "$OSTYPE" =~ linux ]]; then
            os=linux
        elif [[ "$OSTYPE" =~ darwin ]]; then
            os=mac
            is_zip=true
        else
            error "### $OSTYPE not supported"
            exit 1
        fi

        arch=$(uname -m)
        if [[ "$arch" == "x86_64" ]]; then
            goarch="amd64"
        elif [[ "$arch" == "i386" ]]; then
            goarch="i386"
        elif [[ "$arch" == "armv7l" ]]; then
            goarch="arm"
        elif [[ "$arch" == "armv6l" ]]; then
            goarch="armv6"
        elif [[ "$arch" == "aarch64" ]]; then
            goarch="arm64"
        elif [[ "$arch" == "mips" ]]; then
            goarch="mipsel"
        else
            error "### $arch not supported"
            exit 1
        fi

        if [[ ! -d /usr/local/bin ]]; then
            mkdir -p /usr/local/bin
        fi
        if [ "$is_zip" = true ]; then
            file="go2rtc_${os}_${goarch}.zip"
            wget -O /tmp/go2rtc.zip "https://github.com/AlexxIT/go2rtc/releases/download/v${GO2RTC_VERSION}/${file}"
            unzip -p /tmp/go2rtc.zip go2rtc >/usr/local/bin/go2rtc
            rm /tmp/go2rtc.zip
        else
            file="go2rtc_${os}_${goarch}"
            wget -O /usr/local/bin/go2rtc "https://github.com/AlexxIT/go2rtc/releases/download/v${GO2RTC_VERSION}/${file}"
        fi
        chmod +x /usr/local/bin/go2rtc
    fi

    if [[ -f /etc/systemd/system/go2rtc.service ]]; then
        systemctl start go2rtc.service
    fi
}

function mjpeg_preview() {
    CREATE_CAPTURE_WRAPPER=true
    CREATE_GO2RTC_CFG=true

    install_go2rtc

    if [ -f "/etc/go2rtc.yaml" ]; then
        echo -e "\033[0;33m"
        ask_yes_no "### go2rtc config exists already. Recreate? [y/N] " "N"
        echo -e "\033[0m"
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            info "### Recreating go2rtc config."
        else
            CREATE_GO2RTC_CFG=false
            error "### Skipping go2rtc config..."
        fi
    fi

    if [ "$CREATE_GO2RTC_CFG" = true ]; then
        info "### Creating /etc/go2rtc.yaml configuration file"
        cat >/etc/go2rtc.yaml <<EOF
---
streams:
  $YAML_STREAM

log:
  exec: trace
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

    if [ -f "/usr/local/bin/capture" ]; then
        echo -e "\033[0;33m"
        ask_yes_no "### Capture wrapper exists already. Recreate? [y/N] " "N"
        echo -e "\033[0m"
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            info "### Recreating capture wrapper."
        else
            CREATE_CAPTURE_WRAPPER=false
            error "### Skipping capture wrapper..."
        fi
    fi

    if [ "$CREATE_CAPTURE_WRAPPER" = true ]; then
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
    info "    Preview-URL: url(\"http://localhost:1984/api/stream.mjpeg?src=photobooth\")"
    info "    Take picture command: capture %s"
    error "    Note: Countdown for pictures and collage should be set to a minimum of 6 seconds!"
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

    if [[ -f /etc/systemd/system/go2rtc.service ]]; then
        if systemctl is-active --quiet go2rtc.service; then
            systemctl stop go2rtc.service
        fi

        systemctl disable go2rtc.service
        rm -f /etc/systemd/system/go2rtc.service
        systemctl daemon-reload
    fi

    rm -f /etc/go2rtc.yaml
    rm -f /usr/local/bin/go2rtc
    rm -f /usr/local/bin/capture
    rm -f /etc/sudoers.d/020_www-data-systemctl

    info "### Uninstallation complete!"
}

info "Do you want to install a service to"
info "be able to stream your camera to via?"
info ""
error "NOTE: For preview via gphoto2 make sure"
error "gphoto2 -capture-movie"
error "and for PiCamera"
error "rpicam-vid or libcamera-vid"
error "works via terminal before setting up!"
info ""
echo "Your options are:"
echo "1 Install go2rtc and needed service for gphoto2"
echo "2 Install go2rtc and needed service for rpicam-apps"
echo "3 Install go2rtc and needed service for libcamera-apps"
echo "4 Install go2rtc and needed service for fswebcam"
echo "5 Update or downgrade go2rtc only"
echo "6 Uninstall go2rtc and the related services"
echo "7 Do nothing"
info ""
ask_yes_no "Please enter your choice" "6"
info ""

CODEC_FORMAT="--codec mjpeg"

if [[ $REPLY =~ ^[1]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for gphoto2."
    test_command "gphoto2 --capture-movie=5s"
elif [[ $REPLY =~ ^[2]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for rpicam-apps."
    test_command "rpicam-vid -t 5000 $CODEC_FORMAT -o test.mjpeg"
    YAML_STREAM="photobooth: exec:rpicam-vid -t 0 $CODEC_FORMAT --width 2304 --height 1296 -o -#killsignal=2"
    CAPTURE_CMD="rpicam-still"
    CAPTURE_ARGS="-n -q 100 -o \$1"
    NOTE="don't forget to add -o %s."
elif [[ $REPLY =~ ^[3]$ ]]; then
    info "### We will install a service to set up a mjpeg stream for libcamera-apps."
    test_command "libcamera-vid -t 5000 $CODEC_FORMAT -o test.mjpeg"
    YAML_STREAM="photobooth: exec:libcamera-vid -t 0 $CODEC_FORMAT --width 2304 --height 1296 -o -#killsignal=2"
    CAPTURE_CMD="libcamera-still"
    CAPTURE_ARGS="-n -q 100 -o \$1"
    NOTE="don't forget to add -o %s."
elif [[ $REPLY =~ ^[4]$ ]]; then
    CAPTURE_CMD="sleep 1;fswebcam"
    CAPTURE_ARGS="--no-banner -d /dev/video0 -r 1280x720 \$1"
    NOTE="don't forget to add %s."
    YAML_STREAM="photobooth: exec:ffmpeg -hide_banner -v error -f v4l2 -input_format mjpeg -video_size 1280x720 -i /dev/video0 -c copy -f mjpeg -#killsignal=2"
    apt install ffmpeg -y
    apt install fswebcam -y
elif [[ $REPLY =~ ^[5]$ ]]; then
    UPDATE_ONLY=true
    ask_version
    install_go2rtc
    sed -i 's/--libav-format h264/--codec mjpeg/g' /etc/go2rtc.yaml
    info "Done!"
    exit 0
elif [[ $REPLY =~ ^[6]$ ]]; then
    uninstall
    exit 0
else
    info "### Okay... doing nothing!"
    exit 0
fi

if [ -f "test.mjpeg" ]; then
    info "### Deleting existing test.mjpeg file."
    rm test.mjpeg
fi

ask_version
mjpeg_preview
info "### Done!"
exit 0
