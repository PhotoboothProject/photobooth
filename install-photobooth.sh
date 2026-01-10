#!/bin/bash

export LC_ALL=C
export LANG=C

# Initial Variables
LOGFILE="/var/log/photobooth_install.log"
SILENT=false
UPDATE=false
SKIP_AUTO_UPDATE=false
SKIP_WEBSERVER=false
SKIP_PHP=false
SKIP_NODE=false
SKIP_PYTHON=false
PHOTOBOOTH_FOUND=false
INSTALLFOLDERPATH=""
PHOTOBOOTH_SUBFOLDER=""

# Webbrowser
WEBBROWSER="unknown"

# GitHub
GIT_INSTALLED=false
BRANCH="dev"
REMOTE_BRANCH_API="https://api.github.com/repos/PhotoboothProject/photobooth/branches/${BRANCH}"
REMOTE_BRANCH_SHA=""

# OS environment
FORCE_RASPBERRY_PI=false
RUNNING_ON_PI=false
HAS_SYSTEMD=$([[ -x "$(command -v systemctl)" && "$(ps -p 1 -o comm=)" == "systemd" ]] && echo true || echo false)
LOCAL_ARCH=$(uname -m)
OS_CODENAME="unknown"

# PHP
PHP_VERSION="8.4"
DEBIAN=(
    "bullseye"
    "bookworm"
    "trixie"
)

# Node.js
NODEJS_MAJOR="20"
NODEJS_MINOR="15"

# Packages
COMMON_PACKAGES=(
    "gphoto2"
    "libimage-exiftool-perl"
    "nodejs"
    "python3"
    "rsync"
    "udisks2"
)

APACHE_PACKAGES=(
    "apache2"
    "libapache2-mod-php${PHP_VERSION}"
)

PHP_PACKAGES=(
    "php${PHP_VERSION}"
    "php${PHP_VERSION}-cli"
    "php${PHP_VERSION}-gd"
    "php${PHP_VERSION}-xml"
    "php${PHP_VERSION}-zip"
    "php${PHP_VERSION}-mbstring"
)

EXTRA_PACKAGES=(
    "git"
    "jq"
    "curl"
    "gcc"
    "g++"
    "make"
    "apt-transport-https"
    "lsb-release"
    "ca-certificates"
    "software-properties-common"
)

# go2rtc
DEFAULT_GO2RTC_VERSION="1.9.13"
GO2RTC_VERSIONS=("1.9.13" "1.9.12" "1.9.11" "1.9.10" "1.9.9" "1.9.8" "1.9.7" "1.9.6" "1.9.4" "1.9.2")
GO2RTC_UPDATE_ONLY=false
GO2RTC_EXTRA_PACKAGES=(
    "ffmpeg"
    "fswebcam"
)

# gphoto2 webcam
GPHOTO2_WEBCAM_EXTRA_PACKAGES=(
    "v4l2loopback-dkms"
    "v4l-utils"
    "python3"
    "python3-gphoto2"
    "python3-psutil"
    "python3-zmq"
)

# rembg
REMBG_PACKAGES=(
    "python3"
    "python3-pip"
    "python3-venv"
    "php${PHP_VERSION}-curl"
)

REMBG_PIP_PACKAGES=(
    "rembg[cpu,cli]"
    "pillow"
    "filetype"
    "watchdog"
    "aiohttp"
)

# ==================================================
# Logging / helper functions
# ==================================================

function log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >>"$LOGFILE"
}

function confirm() {
    local title=$1
    local message=$2
    local height=${3:-10}
    local width=${4:-60}
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1: $2" >>"$LOGFILE"
    if [ "$SILENT" = true ]; then
        echo "$title: $message"
        sleep 2
    else
        whiptail --title "$title" --msgbox "$message" "$height" "$width"
    fi
}

function info() {
    local title=$1
    local message=$2
    local height=${3:-10}
    local width=${4:-60}
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1: $2" >>"$LOGFILE"
    if [ "$SILENT" = true ]; then
        echo "$title: $message"
    else
        whiptail --title "$title" --infobox "$message" "$height" "$width"  < /dev/tty > /dev/tty 2>&1
    fi
    sleep 1
}

function warn() {
    local title="Warning"
    local message=$1
    local height=${2:-10}
    local width=${3:-60}
    info "$title" "$message" "$height" "$width"
}

function error() {
    local title="Error"
    local message=$1
    local height=${2:-10}
    local width=${3:-60}
    info "$title" "$message" "$height" "$width"
}

function print_logo() {
    local logo="
               %@@@@.
              @@   @@*
           @@@@@@@@@@@@@@@@@@@@@@
          @@%%%%%%%%%%%%%%%%%%%%%@
          @@       @@@@@@       @@
          @@    @@        @@    @@
          @@  @@            @@  @@
          @@  @@            @@  @@
          @@    @@        @@    @@
          @@       @@@@@@       @@
          @@%%%%%%%%%%%%%%%%%%%%%@

            P H O T O B O O T H

          @@%%%%%%%%%%%%%%%%%%%%%@
"

    if [ "$SILENT" = true ]; then
        echo "$logo"
    else
        local height=22
        local width=50
        whiptail --title "Welcome!" --infobox "$logo" "$height" "$width"
    fi
    sleep 2
}

function show_help() {
    echo "Photobooth Setup Wizard"
    echo ""
    echo "Adjust your setup for Photobooth. Available options:"
    echo ""
    echo "  --branch=<branch>           Specify the Git branch to use for installation or updates."
    echo "  --php=<version>             Set the PHP version for the setup (e.g., --php=8.3)."
    echo "  --silent                    Run the Photobooth Setup Wizard in silent mode"
    echo "                              for automated installation or updates."
    echo "  --username=\"<username>\"     Required if --silent is used."
    echo "                              Provide a username for installation or updates."
    echo "  --raspberry                 Skip automatic Raspberry Pi detection and enable Raspberry Pi specific configuration."
    echo "  --wayland                   Skip automatic Wayland detection and enable Wayland configuration."
    echo "  --update                    Requires --silent to update Photobooth if installed already."
    echo "  --skip-webserver            Skip web server setup"
    echo "                              (if already configured or e.g. Nginx is used as Webserver)."
    echo "  --skip-php                  Skip PHP installation"
    echo "                              (if already configured for used Webserver)."
    echo "  --skip-node                 Skip Node.js and npm installation"
    echo "                              (if already installed as required)."
    echo "  --skip-python               Skip python3 installation"
    echo "                              (if already installed as required)."
    echo "  --skip-auto-update          Skip automatic updates for Photobooth Setup Wizard."
    echo ""
    echo "Examples:"
    echo "  $0 --silent --branch=dev --php=8.3 --username=\"photobooth\" --update"
    echo "  $0 --branch=stable4 --skip-webserver"
    echo ""
    echo "For more information, refer to the documentation at"
    echo "https://photoboothproject.github.io"
    exit 0
}

function photobooth_installed() {
    local search_paths=("/var/www/html/photobooth" "/var/www/html")

    for full_path in "${search_paths[@]}"; do
        if [[ -d "$full_path" && -f "$full_path/lib/configsetup.inc.php" ]]; then
            PHOTOBOOTH_FOUND=true
            INSTALLFOLDERPATH="$full_path"
            PHOTOBOOTH_SUBFOLDER="${INSTALLFOLDERPATH#/var/www/html}"
            return 0
        fi
    done

    return 1
}

function check_installfolderpath() {
    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        if ! photobooth_installed; then
            error "INSTALLFOLDERPATH is not defined or empty!"
            return 1
        fi
    fi

    return 0
}

function is_wayland_env() {
    if [ "${WAYLAND_ENV:-}" = "true" ]; then
        return 0
    fi

    local conf="/etc/lightdm/lightdm.conf"

    if [ -f "$conf" ]; then
        session=$(grep -E "^user-session=" "$conf" | cut -d= -f2)

        case "$session" in
            rpd-labwc|LXDE-pi-labwc|rpd-wayfire|LXDE-pi-wayfire)
                return 0
                ;;
        esac
    fi

    # Fallback: check if wayfire or labwc is running
    if pgrep wayfire >/dev/null || pgrep labwc >/dev/null; then
        return 0
    else
        return 1
    fi
}

function install_system_icon() {
    local icon_dir="/usr/share/icons/hicolor/scalable/apps"
    local icon_file="$icon_dir/photobooth.svg"
    local icon_url="https://github.com/PhotoboothProject/photobooth/raw/refs/heads/dev/resources/img/favicon.svg"
    local local_file=""

    # Return if icon already exists
    if [[ -f "$icon_file" ]]; then
        info "System Icon" "Photobooth icon exists already."
        return 0
    fi

    mkdir -p "$icon_dir"

    # Only set local_file if INSTALLFOLDERPATH is valid
    if check_installfolderpath; then
        local_file="$INSTALLFOLDERPATH/resources/img/favicon.svg"
    fi

    # Prefer local file over download
    if [[ -n "$local_file" && -f "$local_file" ]]; then
        cp "$local_file" "$icon_file"
        info "System Icon" "Copied Photobooth icon."
    elif command -v wget >/dev/null 2>&1; then
        if wget -qO "$icon_file" "$icon_url"; then
            info "System Icon" "Downloaded Photobooth icon."
        else
            error "Failed to download icon!"
            return 1
        fi
    else
        error "wget not available and no local icon found!"
        return 1
    fi

    chmod 644 "$icon_file"

    if command -v update-icon-caches >/dev/null 2>&1; then
        update-icon-caches /usr/share/icons/hicolor > /dev/null 2>&1
    fi

    info "System Icon" "Photobooth system icon installed successfully"
    return 0
}

function install_package() {
    local package=$1

    if dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -q "ok installed"; then
        info "Package installation" "${package} is already installed."
        return 0
    else
        info "[Package]" "Installing missing package: ${package}"

        # Handle PHP versioned packages with fallback
        if [[ "$package" =~ ^php[0-9]+\.[0-9]+- ]] || [[ "$package" =~ ^libapache2-mod-php[0-9]+\.[0-9]+$ ]]; then
            local pkg_generic
            pkg_generic=$(echo "$package" | sed -E "s/[0-9]+\.[0-9]+-/-/; s/[0-9]+\.[0-9]+$//")

            if apt-get -qq install -y "$package" >/dev/null 2>&1; then
                info "Package installation" "Successfully installed ${package}."
                return 0
            else
                warn "Package ${package} not available, falling back to ${pkg_generic}..."
                if apt-get -qq install -y "$pkg_generic" >/dev/null 2>&1; then
                    info "Package installation" "Successfully installed ${pkg_generic}."
                    return 0
                else
                    error "Failed to install ${package} and fallback ${pkg_generic}."
                    return 1
                fi
            fi
        else
            # Regular package install
            if apt-get -qq install -y "$package" >/dev/null 2>&1; then
                info "Package installation" "Successfully installed ${package}."
                return 0
            else
                # Special case: ignore failure on software-properties-common, unavailable on Debian Trixie
                if [[ "$package" == "software-properties-common" ]]; then
                    warn "Ignoring failed install of ${package}."
                    return 0
                fi

                warn "Failed to install ${package}."
                return 1
            fi
        fi
    fi
}

function install_packages() {
    local packages=("$@")
    for package in "${packages[@]}"; do
        if ! install_package "$package"; then
            error "Aborting package installation due to failure: ${package}."
            return 1
        fi
    done
    return 0
}

function remove_package() {
    local package=$1
    if dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -q "ok installed"; then
        info "Package uninstall" "Removing package: ${package}"
        if apt-get -qq remove -y "$package" >/dev/null 2>&1; then
            info "Package uninstall" "Successfully removed ${package}."
            return 0
        else
            error "Failed to remove ${package}."
            return 1
        fi
    else
        info "Package uninstall" "${package} is not installed."
        return 0
    fi
}

function remove_packages() {
    local packages=("$@")
    for package in "${packages[@]}"; do
        if ! remove_package "$package"; then
            error "Aborting package removal due to failure: ${package}."
            return 1
        fi
    done
    return 0
}

function test_command() {
    local cmd="$1"
    eval "$cmd" &>/dev/null
    local status=$?

    if [[ -f "test.mjpeg" ]]; then
        info "go2rtc installation" "Deleting existing test.mjpeg file."
        rm test.mjpeg
    fi

    return $status
}

function add_source_list() {
    local source_entry="$1"
    local source_file="$2"
    if grep -Fxq "$source_entry" "$source_file" 2>/dev/null; then
        info "Source list" "Source list entry already exists: $source_entry"
    else
        echo "$source_entry" >>"$source_file"
        info "Source list" "Added source list entry: $source_entry"
    fi
}

function ensure_add_apt_repository() {
    if ! command -v add-apt-repository >/dev/null 2>&1; then
        info "Setup" "add-apt-repository not found. Installing software-properties-common..."
        if ! apt-get update -y >/dev/null 2>&1; then
            error "Failed to update package lists (needed for software-properties-common)."
            return 1
        fi
        if ! apt-get install -y --no-install-recommends software-properties-common >/dev/null 2>&1; then
            error "Failed to install software-properties-common."
            return 1
        fi
        info "Setup" "Installed software-properties-common successfully."
    fi
    return 0
}

function add_apt_repository_once() {
    local repo="$1"
    local clean_repo="${repo#ppa:}"

    # Sanity check
    if [[ -z "$repo" ]]; then
        error "No repository provided to add_apt_repository_once"
        return 1
    fi

    # Check if repository already exists
    if grep -q "$clean_repo" /etc/apt/sources.list /etc/apt/sources.list.d/*.list 2>/dev/null; then
        info "Add apt repository" "Repository '$repo' is already added."
        return 0
    fi
    if grep -q "^deb .*$repo" /etc/apt/sources.list /etc/apt/sources.list.d/*.list 2>/dev/null; then
        info "Add apt repository" "Repository '$repo' is already added."
        return 0
    fi

    # Ensure add-apt-repository is available
    if ! ensure_add_apt_repository; then
        return 1
    fi

    # Add repository
    info "Add apt repository" "Adding repository: $repo"
    if add-apt-repository -y "$repo" >/dev/null 2>&1; then
        info "Add apt repository" "Successfully added repository: $repo"
        return 0
    else
        error "Failed to add repository: $repo"
        return 1
    fi
}

# ==================================================
# Environment detection
# ==================================================

# Photobooth Setup Wizard update
function self_update() {
    local all_args=("$@")

    local curr_date
    curr_date="$(date +%Y%m%d%H%M%S)"

    local script_name="install-photobooth.sh"
    local script_remote_url="https://raw.githubusercontent.com/PhotoboothProject/photobooth/refs/heads/dev/$script_name"
    local script_temp_file="/tmp/$script_name"
    local script_backup_file="/tmp/${script_name}.bak_${curr_date}"
    local script_abs_path
    script_abs_path="$(realpath "$0")"

    info "Photobooth Setup Wizard" "Checking for Photobooth Setup Wizard updates..."

    if ! wget -q -O "$script_temp_file" "$script_remote_url"; then
        confirm "Error" "Unable to download the latest Photobooth Setup Wizard."
        return 1
    fi

    if ! cmp -s "$script_temp_file" "$script_abs_path"; then
        confirm "Photobooth Setup Wizard" "Updated Photobooth Setup Wizard found!"

        if ! whiptail --title "Photobooth Setup Wizard" \
            --yesno "Update Photobooth Setup Wizard to latest version?" \
            12 60; then
            info "Photobooth Setup Wizard" "Skipping Photobooth Setup Wizard update."
            sleep 2
            return 0
        fi

        info "Photobooth Setup Wizard" "Updating the Photobooth Setup Wizard..."
        if ! cp "$script_abs_path" "$script_backup_file"; then
            confirm "Photobooth Setup Wizard" "Failed to create a backup of $script_abs_path. Update aborted."
            return 1
        fi
        info "Photobooth Setup Wizard" "Backup created: $script_backup_file"

        if mv -f "$script_temp_file" "$script_abs_path"; then
            if ! chmod +x "$script_abs_path"; then
                confirm "Photobooth Setup Wizard" "Failed to add execution permission to $script_abs_path. Update aborted."
                return 1
            fi
            confirm "Photobooth Setup Wizard" "Photobooth Setup Wizard updated successfully."
            info "Photobooth Setup Wizard" "Restarting Photobooth Setup Wizard..."
            sleep 2

            exec "$script_abs_path" "${all_args[@]}"
        else
            confirm "Photobooth Setup Wizard" "Failed to update Photobooth Setup Wizard!"
            return 1
        fi
    else
        info "Photobooth Setup Wizard" "No updates available."
        rm -f "$script_temp_file"
    fi
}

function detect_os_codename() {
    local os=""

    if command -v lsb_release >/dev/null 2>&1; then
        os=$(lsb_release -sc 2>/dev/null)
    elif [[ -r /etc/os-release ]]; then
        # Try VERSION_CODENAME first
        os=$(grep -E '^VERSION_CODENAME=' /etc/os-release | cut -d= -f2)
        if [[ -z "$os" ]]; then
            # Extract from VERSION string as fallback
            os=$(grep -E '^VERSION=' /etc/os-release | sed -E 's/.*\((.*)\).*/\1/')
        fi
    fi

    if [[ -n "$os" ]]; then
        echo "$os"
    fi
}


# Check if running on Raspberry Pi
function detect_pi() {
    if [ "$FORCE_RASPBERRY_PI" = false ]; then
        local pi_model
        if [ ! -f /proc/device-tree/model ]; then
            no_raspberry 2
        else
            pi_model=$(tr -d '\0' </proc/device-tree/model)
            if [[ $pi_model != Raspberry* ]]; then
                no_raspberry 3
            else
                RUNNING_ON_PI=true
            fi
        fi
    else
        RUNNING_ON_PI=true
    fi
}

function no_raspberry() {
    info "WARNING" "This script is intended to run on a Raspberry Pi.\nRunning the script on other devices running Debian or a Debian-based distribution is possible, but Raspberry Pi-specific features will be missing!"
    sleep 2
    RUNNING_ON_PI=false
}

# Detect a single user under /home
# Prints the username if exactly one exists, returns 0
# Prints nothing and returns 1 otherwise
function detect_single_home_user() {
    local user_dirs=()

    for dir in /home/*; do
        [[ -d "$dir" ]] && user_dirs+=("$(basename "$dir")")
    done

    if [[ ${#user_dirs[@]} -eq 1 ]]; then
        echo "${user_dirs[0]}"
        return 0
    fi

    return 1
}

function check_username() {
    while true; do
        # Try auto-detect first
        if [ -z "$USERNAME" ]; then
            local detected_user
            detected_user=$(detect_single_home_user)
            if [[ -n "$detected_user" ]]; then
                USERNAME="$detected_user"
                info "Setup Wizard" "Automatically detected username: $USERNAME"
            fi
        fi

        if [ -z "$USERNAME" ] && [ "$SILENT" = false ]; then
            if ! USERNAME=$(whiptail --title "Welcome to Photobooth Setup Wizard" \
                --inputbox "Enter your username to proceed:" \
                8 50 "$(who -m | awk '{ print $1 }')" \
                --cancel-button Exit --ok-button Proof \
                3>&1 1>&2 2>&3); then
                if whiptail --title "Photobooth Setup Wizard" \
                    --yesno "Are you sure you want to exit?" \
                    8 50; then
                    exit 0
                else
                    continue
                fi
            fi
        fi

        # Validate username
        if [ -n "$USERNAME" ]; then
            if id "$USERNAME" &>/dev/null; then
                break
            else
                if [ "$SILENT" = true ]; then
                    confirm "Invalid Username" "Error: The username '$USERNAME' does not exist. Continuing without a defined user."
                    USERNAME=""
                    break
                else
                    confirm "Invalid Username" "The username '$USERNAME' does not exist. Please try again."
                    USERNAME=""
                fi
            fi
        else
            if [ "$SILENT" = true ]; then
                confirm "Setup Wizard" "Username not defined. Ignoring..."
                break
            else
                confirm "Setup Wizard" "Username cannot be empty. Please try again."
            fi
        fi
    done
}

function check_webserver() {
    local servers=("apache2" "nginx" "lighttpd")
    local installed_but_not_running=false

    for server in "${servers[@]}"; do
        # Check if package is installed
        if dpkg-query -W -f='${Status}' "$server" 2>/dev/null | grep -q "ok installed"; then
            # Check if systemctl exists and service is active
            if [[ "$HAS_SYSTEMD" == true ]] && systemctl is-active --quiet "$server"; then
                info "Webserver Check" "$server is installed and running."
                case $server in
                    apache2) return 1 ;;
                    nginx) return 2 ;;
                    lighttpd) return 3 ;;
                esac
            else
                info "Webserver Check" "$server is installed but not running (or systemctl unavailable)."
                installed_but_not_running=true
            fi
        fi
    done

    if [[ "$installed_but_not_running" == true ]]; then
        info "Webserver Check" "One or more webservers are installed but not running."
        return 4
    fi

    info "Webserver Check" "No webserver is installed and running."
    return 0
}

function prepare_php_environment() {
    if detected_os=$(detect_os_codename) && [[ $detected_os ]]; then
        OS_CODENAME="$detected_os"
        info "OS Detection" "Detected distribution codename: $OS_CODENAME"
    else
        confirm "Warning" "Could not detect OS codename."
    fi
    info "PHP preparation" "Detected OS: $OS_CODENAME"

    # Add PHP repository based on OS
    if [[ "${DEBIAN[*]}" =~ $OS_CODENAME ]]; then
        info "PHP preparation" "Adding Sury PHP repository for Debian."
        wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg >/dev/null 2>&1
        echo "deb https://packages.sury.org/php/ $OS_CODENAME main" \
            | tee /etc/apt/sources.list.d/php.list >/dev/null 2>&1
    elif [[ "$OS_CODENAME" == "mantic" ]]; then
        info "PHP preparation" "No source lists available for 'mantic'."
    else
        if [[ "$OS_CODENAME" == "jammy" ]]; then
            info "PHP preparation" "Checking for 'jammy-updates' in sources list."
            add_source_list "deb http://archive.ubuntu.com/ubuntu/ jammy-updates main restricted" /etc/apt/sources.list
        fi

        if ! ensure_add_apt_repository; then
            error "Failed to install software-properties-common (needed for add-apt-repository)."
            return 2
        fi

        info "PHP preparation" "Adding Ondrej PHP PPA."
        if ! add_apt_repository_once "ppa:ondrej/php"; then
            error "Failed to add Ondrej PHP PPA."
            return 1
        fi
    fi

    if apt-get -qq update 2>&1 | grep -q "does not have a Release file"; then
        info "PPA ondrej/php is not valid for $OS_CODENAME, removing..."
        add-apt-repository --remove ppa:ondrej/php
        rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php*.list
    fi

    if ! apt-get -qq update >/dev/null 2>&1; then
        error "Update after adding repositories failed."
        return 1
    fi

    info "PHP preparation" "PHP preparation completed successfully."
    return 0
}

function set_php_version_cli() {
    local version="$1"

    if [[ -z "$version" ]]; then
        error "No PHP version provided to set_php_version_cli" >&2
        return 1
    fi

    local php_bin="/usr/bin/php${version}"

    if [[ ! -x "$php_bin" ]]; then
        error "$php_bin not found (is php${version} installed?)" >&2
        return 1
    fi

    local priority
    priority=$(echo "$version" | tr -d '.')

    update-alternatives --install /usr/bin/php php "$php_bin" "$priority" >/dev/null 2>&1 || warn "Failed to install PHP via update-alternatives."
    update-alternatives --set php "$php_bin" >/dev/null 2>&1 || error "Failed to set default PHP CLI version."

    info "PHP CLI" "CLI php now points to: $(php -v | head -n1)"
    return 0
}

function set_php_version_apache() {
    local version="$1"

    if [[ -z "$version" ]]; then
        error "No PHP version provided." >&2
        return 1
    fi

    a2dismod -f php* >/dev/null 2>&1 || true

    if a2enmod "php${version}" >/dev/null 2>&1; then
        confirm "Apache Webserver" "Apache is now configured to use PHP ${version} "
        if [[ "$HAS_SYSTEMD" == true ]]; then
            if systemctl is-active --quiet apache2; then
                # Restart if already running
                if ! systemctl restart apache2 &>/dev/null; then
                    confirm "Apache Webserver" "Failed to restart Apache Webserver. Please reboot to apply."
                fi
            fi
        fi
        return 0
    else
        error "Could not enable php${version} for Apache" >&2
        return 1
    fi
}

# ==================================================
# Change defaults for install / update
# ==================================================

function set_branch() {
    local new_branch
    if new_branch=$(whiptail --title "Set Git Branch" \
        --inputbox "Enter the branch you want to use (e.g., dev):" 10 50 "$BRANCH" 3>&1 1>&2 2>&3); then
        BRANCH="$new_branch"
        info "Git Branch" "Branch set to $BRANCH"
    else
        info "Git Branch" "No changes made to branch."
    fi
}

function set_php_version() {
    local new_php_version
    if new_php_version=$(whiptail --title "Set PHP Version" \
        --inputbox "Enter the PHP version you want to use (e.g., 8.3):" 10 50 "$PHP_VERSION" 3>&1 1>&2 2>&3); then
        PHP_VERSION="$new_php_version"
        info "PHP Version" "PHP version set to $PHP_VERSION"
    else
        info "PHP Version" "No changes made to PHP version."
    fi
}

function toggle_skip_webserver() {
    if whiptail --title "Web Server Setup" \
        --yesno "Current value: Skip web server setup = $SKIP_WEBSERVER\n\nToggle this option?" 10 50; then
        SKIP_WEBSERVER=$([ "$SKIP_WEBSERVER" = true ] && echo false || echo true)
        info "Web Server Setup" "Skip web server setup toggled to $SKIP_WEBSERVER"
    else
        info "Web Server Setup" "No changes made."
    fi
}

function toggle_skip_php() {
    if whiptail --title "PHP Setup" \
        --yesno "Current value: Skip PHP setup = $SKIP_PHP\n\nToggle this option?" 10 50; then
        SKIP_PHP=$([ "$SKIP_PHP" = true ] && echo false || echo true)
        info "PHP Setup" "Skip PHP setup toggled to $SKIP_PHP"
    else
        info "PHP Setup" "No changes made."
    fi
}

function toggle_skip_node() {
    if whiptail --title "Node.js Setup" \
        --yesno "Current value: Skip Node.js and npm setup = $SKIP_NODE\n\nToggle this option?" 10 50; then
        SKIP_NODE=$([ "$SKIP_NODE" = true ] && echo false || echo true)
        info "Node.js Setup" "Skip Node.js and npm setup toggled to $SKIP_NODE"
    else
        info "Node.js Setup" "No changes made."
    fi
}

function toggle_skip_python() {
    if whiptail --title "Python3 Setup" \
        --yesno "Current value: Skip Python3 setup = $SKIP_PYTHON\n\nToggle this option?" 10 50; then
        SKIP_PYTHON=$([ "$SKIP_PYTHON" = true ] && echo false || echo true)
        info "Python3 Setup" "Skip Python3 setup toggled to $SKIP_PYTHON"
    else
        info "Python3 Setup" "No changes made."
    fi
}

# ==================================================
#
# ==================================================
function detect_browser() {
    local browser=""

    if update-alternatives --query x-www-browser &>/dev/null; then
        browser=$(update-alternatives --display x-www-browser \
            | grep 'currently' | awk -F/ '{print $4}')
    fi

    case "$browser" in
        chromium-browser|chromium|google-chrome|google-chrome-stable|google-chrome-beta)
            WEBBROWSER="$browser"
            CHROME_FLAGS=true
            ;;
        firefox|firefox-esr)
            WEBBROWSER="$browser"
            CHROME_FLAGS=false
            ;;
        *)
            for b in chromium chromium-browser google-chrome google-chrome-stable google-chrome-beta firefox firefox-esr; do
                if command -v "$b" >/dev/null; then
                    WEBBROWSER="$b"
                    [[ "$b" =~ chrome|chromium ]] && CHROME_FLAGS=true || CHROME_FLAGS=false
                    return
                fi
            done

            WEBBROWSER="unknown"
            CHROME_FLAGS=false
            ;;
    esac
}

# Returns the kiosk flag to be used
function setup_kiosk_browser() {
    local kiosk_flag="${1:---kiosk http://localhost}"
    echo "$kiosk_flag"
}

# Returns the full Chrome command flags including kiosk
# Usage: setup_chrome_flags <local_env> [kiosk_flag] [chrome_default_flags]
function setup_chrome_flags() {
    local local_env="${1:-default}"
    local kiosk_flag="${2:-$(setup_kiosk_browser "--kiosk http://localhost")}"
    local chrome_default_flags="${3:---noerrdialogs --disable-infobars --disable-features=Translate --no-first-run --check-for-update-interval=31536000 --touch-events=enabled --password-store=basic}"

    local flags=""

    case "$local_env" in
        pi-wayland)
            flags="$chrome_default_flags --ozone-platform=wayland --start-maximized"
            ;;
        pi)
            flags="$chrome_default_flags --use-gl=egl"
            ;;
        *)
            flags="$chrome_default_flags"
            ;;
    esac

    echo "$flags $kiosk_flag"
}

function browser_shortcut() {
    local flags=""
    local shortcut="$1"
    local local_env="default"

    if [[ -z "$shortcut" ]]; then
        confirm "Browser Shortcut" "Error: Shortcut path is required! Cannot create shortcut!"
        return 1
    fi

    if ! photobooth_installed; then
        confirm "Browser Shortcut" "Error: Photobooth not installed!"
        return 1
    fi

    # Ensure parent directory exists
    mkdir -p "$(dirname "$shortcut")" || {
        confirm "Browser Shortcut" "Error: Could not create $(dirname "$shortcut")"
        return 1
    }

    detect_browser
    if [ "$WEBBROWSER" = "unknown" ]; then
        confirm "Browser Shortcut" "No browser detected. Browser shortcut cannot proceed."
        return 2
    fi

    if [ "$CHROME_FLAGS" = true ]; then
        if [ "$RUNNING_ON_PI" = true ] && is_wayland_env; then
            local_env="pi-wayland"
        elif [ "$RUNNING_ON_PI" = true ]; then
            local_env="pi"
        fi
        flags="$(setup_chrome_flags "$local_env")"
    else
        flags="$(setup_kiosk_browser)"
    fi

    cat >"$shortcut" <<EOF
[Desktop Entry]
Version=1.3
Terminal=false
Type=Application
Name=Photobooth
Exec=$WEBBROWSER $flags$PHOTOBOOTH_SUBFOLDER
Icon=photobooth
StartupNotify=false
EOF

    if ! chmod 644 "$shortcut"; then
        confirm "Browser Shortcut" "Error: Failed to set permissions on browser shortcut!"
        return 3
    fi

    return 0
}

function browser_desktop_shortcut() {
    local shortcut="$1"
    local username="$2"

    if [[ -z "$shortcut" ]]; then
        confirm "Desktop Shortcut" "Error: Shortcut path is required! Can not create desktop shortcut!"
        return 1
    fi

    if [ -z "$username" ]; then
        local detected_user
        detected_user=$(detect_single_home_user)
        if [[ -n "$detected_user" ]]; then
            username="$detected_user"
            info "Desktop Shortcut" "Automatically detected username: $username"
        else
            confirm "Desktop Shortcut" "Error: A username is required! Can not create desktop shortcut!"
            return 1
        fi
    fi

    if [ -d "/home/$username/Desktop" ]; then
        if browser_shortcut "$shortcut"; then
            chmod +x "$shortcut"
            chown "$username:$username" "$shortcut"
            confirm "Desktop Shortcut Created" "Photobooth desktop shortcut has been created."
            return 0
        else
            confirm "Desktop Shortcut" "Failed to create Photobooth desktop shortcut."
            return 1
        fi
    else
        confirm "Desktop Shortcut" "Desktop directory not found for user $username."
        return 1
    fi
}

function browser_autostart() {
    if ! browser_shortcut "/etc/xdg/autostart/photobooth.desktop"; then
        return $?
    fi
    info "Autostart Shortcut" "Photobooth autostart entry created."
    return 0
}

# ==================================================
# Permissions
# ==================================================

function set_private_acl() {
    if ! check_installfolderpath; then
        return 1
    fi

    local folder="$INSTALLFOLDERPATH/private"

    # Ensure folder exists
    if [ ! -d "$folder" ]; then
        warn "Folder $folder does not exist."
        return 1
    fi

    # Set default ACLs for new files/folders
    setfacl -d -m u::rwx "$folder" >/dev/null 2>&1 || \
        warn "Failed to set default ACL for owner on $folder"
    setfacl -d -m g::rwx "$folder" >/dev/null 2>&1 || \
        warn "Failed to set default ACL for group on $folder"
    setfacl -d -m o::r   "$folder" >/dev/null 2>&1 || \
        warn "Failed to set default ACL for others on $folder"

    # Apply ACLs recursively to existing files/folders
    setfacl -R -m u::rwx "$folder" >/dev/null 2>&1 || \
        warn "Failed to apply ACL for owner recursively on $folder"
    setfacl -R -m g::rwx "$folder" >/dev/null 2>&1 || \
        warn "Failed to apply ACL for group recursively on $folder"
    setfacl -R -m o::r   "$folder" >/dev/null 2>&1 || \
        warn "Failed to apply ACL for others recursively on $folder"

    info "ACL Setup" "Default and recursive ACLs applied to $folder"
    return 0
}

function general_permissions() {
    info "Permissions" "Setting general permissions."

    if ! check_installfolderpath; then
        return 1
    fi

    # Change ownership of the installation folder
    chown -R www-data:www-data "$INSTALLFOLDERPATH"/ >/dev/null 2>&1 || warn "Failed to set ownership for $INSTALLFOLDERPATH"

    # Set permissions on private folder
    chmod 2775 "$INSTALLFOLDERPATH/private" >/dev/null 2>&1 || warn "Failed to set permissions and setgid bit on $INSTALLFOLDERPATH/private"

    if set_private_acl; then
        info "Permissions" "ACLs successfully applied"
    else
        warn "Failed to apply ACLs"
    fi

    # Add `www-data` to necessary groups
    gpasswd -a www-data plugdev >/dev/null 2>&1 || warn "Failed to add www-data to plugdev group"
    gpasswd -a www-data video >/dev/null 2>&1 || warn "Failed to add www-data to video group"
    if [ -n "$USERNAME" ]; then
        gpasswd -a "$USERNAME" www-data >/dev/null 2>&1 || warn "Failed to add $USERNAME to www-data group!"
    else
        warn "No username defined! Can not add user to www-data group!"
    fi

    # Fix permissions on cache folder
    info "Permissions" "Fixing permissions on cache folder."
    mkdir -p "/var/www/.cache" >/dev/null 2>&1 || warn "Failed to create /var/www/.cache directory"
    chown -R www-data:www-data "/var/www/.cache" >/dev/null 2>&1 || warn "Failed to set ownership for /var/www/.cache"

    # Fix permissions on npm folder
    info "Permissions" "Fixing permissions on npm folder."
    mkdir -p "/var/www/.npm" >/dev/null 2>&1 || warn "Failed to create /var/www/.npm directory"
    chown -R www-data:www-data "/var/www/.npm" >/dev/null 2>&1 || warn "Failed to set ownership for /var/www/.npm"

    # Disable camera automount
    info "Permissions" "Disabling camera automount."
    chmod -x /usr/lib/gvfs/gvfs-gphoto2-volume-monitor >/dev/null 2>&1 || warn "Failed to disable camera automount"

    return 0
}

function gpio_permission() {
    if [ "$RUNNING_ON_PI" = false ]; then
        return
    fi

    local boot_config
    info "Remote Buzzer GPIO Configuration" "Removing deprecated GPIO settings and configuration"

    # Determine the correct boot configuration file
    if [ -f '/boot/firmware/config.txt' ]; then
        boot_config="/boot/firmware/config.txt"
    else
        boot_config="/boot/config.txt"
    fi

    # Add the www-data user to the GPIO group
    usermod -a -G gpio www-data >/dev/null 2>&1 || warn "Failed to add www-data to gpio group"

    sed -i '/# Photobooth/,/# Photobooth End/d' "$boot_config" >/dev/null 2>&1 || warn "Failed to remove old Photobooth GPIO configuration"

    # Remove old artifacts from the node-rpio library, if present
    if [ -f '/etc/udev/rules.d/20-photobooth-gpiomem.rules' ]; then
        info "Remote Buzzer Update" "Old artifacts from the node-rpio library detected. Removing obsolete configuration."
        rm -f /etc/udev/rules.d/20-photobooth-gpiomem.rules >/dev/null 2>&1 || warn "Failed to remove old udev rules"
        sed -i '/dtoverlay=gpio-no-irq/d' "$boot_config" >/dev/null 2>&1 || warn "Failed to remove dtoverlay from $boot_config"
    fi

    # Update artifacts in the user configuration for the new implementation
    if [ -f "$INSTALLFOLDERPATH/config/my.config.inc.php" ]; then
        sed -i '/remotebuzzer/{n;n;s/enabled/usebuttons/}' "$INSTALLFOLDERPATH/config/my.config.inc.php" >/dev/null 2>&1 || warn "Failed to update remotebuzzer configuration in my.config.inc.php"
    fi

    info "Remote Buzzer GPIO Configuration" "Setup complete. Reboot your Raspberry Pi to apply the changes."

    return 0
}

function remove_gpio_permission() {
    if [ "$RUNNING_ON_PI" = false ]; then
        return
    fi

    local boot_config
    info "Remote Buzzer GPIO Feature" "Removing GPIO access for www-data user."

    # Determine the correct boot configuration file
    if [ -f "/boot/firmware/config.txt" ]; then
        boot_config="/boot/firmware/config.txt"
    elif [ -f "/boot/config.txt" ]; then
        boot_config="/boot/config.txt"
    else
        error "Could not find a valid Raspberry Pi boot config file."
        return 1
    fi

    # Check if www-data is part of the gpio group
    if groups www-data | grep -q "\bgpio\b"; then
        if gpasswd -d www-data gpio >/dev/null 2>&1; then
            info "Remote Buzzer GPIO Feature" "Successfully removed www-data user from the gpio group."
        else
            warn "Failed to remove www-data user from the gpio group."
        fi
    else
        info "Remote Buzzer GPIO Feature" "www-data user is not a member of the gpio group. No action needed."
    fi

    # Remove old artifacts from the node-rpio library, if present
    if [ -f '/etc/udev/rules.d/20-photobooth-gpiomem.rules' ]; then
        info "Remote Buzzer Update" "Old artifacts from the node-rpio library detected. Removing obsolete configuration."
        rm -f /etc/udev/rules.d/20-photobooth-gpiomem.rules >/dev/null 2>&1 || warn "Failed to remove old udev rules"
        sed -i '/dtoverlay=gpio-no-irq/d' "$boot_config" >/dev/null 2>&1 || warn "Failed to remove dtoverlay from $boot_config"
    fi

    # Remove configuration required for the onoff library
    info "Remote Buzzer GPIO Configuration" "GPIO settings removed in $boot_config"
    sed -i '/# Photobooth/,/# Photobooth End/d' "$boot_config" >/dev/null 2>&1 || warn "Failed to clean old Photobooth GPIO configuration."

    return 0
}

function setup_printer_groups() {
    # Add www-data to lp group
    if gpasswd -a www-data lp >/dev/null 2>&1; then
        info "Printer Group Setup" "Added www-data to lp group."
    else
        warn "Failed to add www-data to lp group."
        return 1
    fi

    # Add www-data to lpadmin group
    if gpasswd -a www-data lpadmin >/dev/null 2>&1; then
        info "Printer Group Setup" "Added www-data to lpadmin group."
    else
        warn "Failed to add www-data to lpadmin group."
        return 2
    fi

    return 0
}

function remove_printer_groups() {
    # Remove www-data from lp group
    if groups www-data | grep -q "\blp\b"; then
        if gpasswd -d www-data lp >/dev/null 2>&1; then
            info "Printer Group Removal" "Removed www-data from lp group."
        else
            warn "Failed to remove www-data from lp group."
            return 1
        fi
    else
        info "Printer Group Removal" "www-data is not a member of lp group. No action needed."
    fi

    # Remove www-data from lpadmin group
    if groups www-data | grep -q "\blpadmin\b"; then
        if gpasswd -d www-data lpadmin >/dev/null 2>&1; then
            info "Printer Group Removal" "Removed www-data from lpadmin group."
        else
            warn "Printer Group Removal" "Failed to remove www-data from lpadmin group."
            return 2
        fi
    else
        info "www-data is not a member of lpadmin group. No action needed."
    fi

    return 0
}

function install_wwwdata_sudoers() {
    local sudoers_file="/etc/sudoers.d/020_www-data-shutdown"

    cat >"$sudoers_file" <<'EOF'
# Photobooth buttons for www-data to shutdown or reboot the system
www-data ALL=(ALL) NOPASSWD: /sbin/shutdown
www-data ALL=(ALL) NOPASSWD: /sbin/reboot
EOF

    chmod 440 "$sudoers_file"

    # Validate syntax (safe check, optional but recommended)
    if visudo -cf "$sudoers_file" >/dev/null 2>&1; then
        info "Setup" "Installed sudoers rule for www-data at $sudoers_file"
        return 0
    else
        error "Invalid sudoers file created at $sudoers_file. Please check manually."
        return 1
    fi
}

function create_polkit_usb_rule() {
    local PKLA_DIR="/etc/polkit-1/localauthority/50-local.d"
    local RULES_DIR="/etc/polkit-1/rules.d"

    if [[ -d "$PKLA_DIR" ]]; then
        cat >"$PKLA_DIR/photobooth.pkla" <<EOF
[Allow www-data to mount drives with udisks2]
Identity=unix-user:www-data
Action=org.freedesktop.udisks2.filesystem-mount*;org.freedesktop.udisks2.filesystem-unmount*
ResultAny=yes
ResultInactive=yes
ResultActive=yes
EOF
        return 0

    elif [[ -d "$RULES_DIR" ]]; then
        cat >"$RULES_DIR/photobooth.rules" <<'EOF'
polkit.addRule(function(action, subject) {
    if (subject.isUser && subject.user == "www-data" &&
        (action.id.indexOf("org.freedesktop.udisks2.filesystem-mount") == 0 ||
         action.id.indexOf("org.freedesktop.udisks2.filesystem-unmount") == 0)) {
        return polkit.Result.YES;
    }
});
EOF
        return 0

    else
        return 1
    fi
}

function remove_polkit_usb_rule() {
    local PKLA_FILE="/etc/polkit-1/localauthority/50-local.d/photobooth.pkla"
    local RULES_FILE="/etc/polkit-1/rules.d/photobooth.rules"
    local REMOVED=false

    [[ -f "$PKLA_FILE" ]] && rm -f "$PKLA_FILE" && REMOVED=true
    [[ -f "$RULES_FILE" ]] && rm -f "$RULES_FILE" && REMOVED=true

    $REMOVED && return 0 || return 1
}

function disable_automount() {
    local configured=false
    local pcmanfm_conf=""

    if [[ -f "/etc/xdg/pcmanfm/default/pcmanfm.conf" ]]; then
        pcmanfm_conf="/etc/xdg/pcmanfm/default/pcmanfm.conf"
        if ! grep -q "^\[volume\]" "$pcmanfm_conf"; then
            echo "[volume]" >>"$pcmanfm_conf"
        fi
        for key in mount_on_startup mount_removable autorun; do
            if grep -q "^$key=" "$pcmanfm_conf"; then
                sed -i "s/^$key=.*/$key=0/" "$pcmanfm_conf"
            else
                echo "$key=0" >>"$pcmanfm_conf"
            fi
        done
        configured=true
        info "Auto mount Disable" "System default adjusted."
    fi

    if [ -z "$USERNAME" ]; then
        local detected_user
        detected_user=$(detect_single_home_user)
        if [[ -n "$detected_user" ]]; then
            USERNAME="$detected_user"
            info "Auto mount Disable" "Automatically detected username: $USERNAME"
        else
            warn "Auto mount Disable" "No username detected."
        fi
    fi

    if [ -n "$USERNAME" ]; then
        local xdg_config="${XDG_CONFIG_HOME:-/home/$USERNAME/.config}"
        local config_base="$xdg_config/pcmanfm"
        local profile_folder=""

        if [[ -d "$config_base" ]]; then
            profile_folder=$(find "$config_base" -mindepth 1 -maxdepth 1 -type d | head -n 1)
        fi

        if [[ -n "$profile_folder" ]]; then
            pcmanfm_conf="$profile_folder/pcmanfm.conf"
            if [[ -f "$pcmanfm_conf" ]]; then
                if ! grep -q "^\[volume\]" "$pcmanfm_conf"; then
                    echo "[volume]" >>"$pcmanfm_conf"
                fi
                for key in mount_on_startup mount_removable autorun; do
                    if grep -q "^$key=" "$pcmanfm_conf"; then
                        sed -i "s/^$key=.*/$key=0/" "$pcmanfm_conf"
                    else
                        echo "$key=0" >>"$pcmanfm_conf"
                    fi
                done
                chown "$USERNAME:$USERNAME" "$pcmanfm_conf" 2>/dev/null
                configured=true
                info "Auto mount Disable" "User config adjusted."
            fi
        fi
    fi

    if $configured; then
        return 0
    else
        return 1
    fi
}

function set_usb_sync() {
    if whiptail --title "USB Sync" \
        --yesno "Setup USB Sync policy?\n\nThis is needed to use the USB Sync feature of Photobooth.\nUSB Sync can be activated via Adminpanel." \
        12 60; then

        if create_polkit_usb_rule; then
            if disable_automount; then
                confirm "USB Sync" "USB Sync policy created and auto mount settings updated successfully."
            else
                confirm "USB Sync" "USB Sync policy created, but no configuration file was found to adjust auto mount."
            fi
        else
            confirm "USB Sync" "Failed to create USB Sync policy!"
        fi

    else
        if remove_polkit_usb_rule; then
            confirm "USB Sync" "USB Sync policy removed."
        else
            confirm "USB Sync" "No USB Sync policy file found to remove."
        fi
    fi
}

# ==================================================
# UI
# ==================================================
function hide_mouse() {
    if is_wayland_env; then
        if [ -f "/usr/share/icons/PiXflat/cursors/left_ptr" ]; then
            mv /usr/share/icons/PiXflat/cursors/left_ptr /usr/share/icons/PiXflat/cursors/left_ptr.bak
            confirm "Hide mouse" "Mouse cursor hidden for Wayland by backing up 'left_ptr' icon."
        elif [ -f "/usr/share/icons/PiXtrix/cursors/left_ptr" ]; then
            mv /usr/share/icons/PiXtrix/cursors/left_ptr /usr/share/icons/PiXtrix/cursors/left_ptr.bak
            confirm "Hide mouse" "Mouse cursor hidden for Wayland by backing up 'left_ptr' icon."
        else
            confirm "Hide mouse" "Cursor already hidden or 'left_ptr' icon not found."
        fi
    else

        local lxde_autostart_file="/etc/xdg/lxsession/LXDE-pi/autostart"
        if [ ! -f "$lxde_autostart_file" ]; then
            confirm "Hide mouse" "Aborting. LXDE-pi autostart not found."
            return 1
        fi

        if ! install_package "unclutter"; then
            confirm "Hide mouse" "Aborting. Can not install unclutter."
            return 2
        fi

        # Remove existing Photobooth-related configurations to avoid duplicates
        sed -i '/# Photobooth/,/# Photobooth End/d' "$lxde_autostart_file"

        # Append new settings to autostart
        cat >>"$lxde_autostart_file" <<EOF
# Photobooth
# Turn off display power management system
@xset -dpms
# Turn off screen blanking
@xset s noblank
# Turn off screen saver
@xset s off
# Hide mouse cursor after 3 seconds of inactivity
@unclutter -idle 3
# Photobooth End
EOF

        confirm "Hide mouse" "Mouse cursor hidden and power management settings applied in LXDE autostart."
    fi

    return 0
}

function restore_mouse() {
    if is_wayland_env; then
        # Restore the cursor icon in Wayland
        if [ -f "/usr/share/icons/PiXflat/cursors/left_ptr.bak" ]; then
            mv /usr/share/icons/PiXflat/cursors/left_ptr.bak /usr/share/icons/PiXflat/cursors/left_ptr
            confirm "Show mouse" "Mouse cursor restored for Wayland by restoring 'left_ptr' icon."
        elif [ -f "/usr/share/icons/PiXtrix/cursors/left_ptr.bak" ]; then
            mv /usr/share/icons/PiXtrix/cursors/left_ptr.bak /usr/share/icons/PiXtrix/cursors/left_ptr
            confirm "Show mouse" "Mouse cursor restored for Wayland by restoring 'left_ptr' icon."
        else
            confirm "Show mouse" "Backup of 'left_ptr' icon not found. Mouse cursor may already be restored."
        fi
    else
        if ! remove_package "unclutter"; then
            confirm "Show mouse" "Failed to uninstall unclutter!"
        fi

        local lxde_autostart_file="/etc/xdg/lxsession/LXDE-pi/autostart"
        if [ ! -f "$lxde_autostart_file" ]; then
            confirm "Show mouse" "Aborting. LXDE-pi autostart not found."
            return 1
        fi

        # Remove Photobooth-specific entries from LXDE autostart
        sed -i '/# Photobooth/,/# Photobooth End/d' /etc/xdg/lxsession/LXDE-pi/autostart
        confirm "Show mouse" "Mouse cursor settings restored in LXDE autostart."
    fi

    return 0
}


# ==================================================
#
# ==================================================
function apache_webserver() {
    info "Webserver" "Installing Apache Webserver and dependencies..."

    # Install required packages
    if ! install_packages "${APACHE_PACKAGES[@]}"; then
        error "Failed to install required apache2 packages. Aborting Apache Webserver installation."
        return 1
    fi

    if [[ "$HAS_SYSTEMD" == true ]] && ! systemctl is-active --quiet "apache2"; then
        # Enable and start apache2 service
        if ! systemctl enable --now apache2 &>/dev/null; then
            warn "Failed to enable and start Apache Webserver."
            return 2
        fi
    fi

    info "Webserver" "Apache Webserver installed and running successfully."

    return 0
}

function check_nodejs() {
    NODE_VERSION=$(node -v 2>/dev/null || echo "0")
    IFS=. read -r -a VER <<<"${NODE_VERSION##*v}"
    major=${VER[0]}
    minor=${VER[1]}

    info "Node.js" "Node.js on Photobooth is only supported on v$NODEJS_MAJOR.$NODEJS_MINOR!"
    info "Node.js" "Found Node.js $NODE_VERSION."

    if [[ -z "$NODE_VERSION" || "$NODE_VERSION" == "0" ]]; then
        info "Node.js" "Node.js is not installed."
        return 2
    fi

    # Check major and minor version compatibility
    if [[ -n "$major" ]] && [[ "$major" -ge "$NODEJS_MAJOR" ]]; then
        if [[ "$major" -ge "21" ]]; then
            warn "Node.js downgrade suggested."
            return 3
        elif [[ "$major" -eq "$NODEJS_MAJOR" && "$minor" -lt "$NODEJS_MINOR" ]]; then
            warn "Node.js update required."
            return 1
        else
            info "Node.js" "Node.js $NODE_VERSION matches our requirements."
            return 0
        fi
    else
        warn "Node.js version too low."
        return 1
    fi
}

function update_nodejs() {
    info "Node.js update" "Removing old Node.js packages if installed."
    apt-get -qq purge -y nodejs nodejs-doc libnode72 npm >/dev/null 2>&1
    apt-get -qq autoremove --purge -y >/dev/null 2>&1
    rm -f /usr/bin/node /usr/local/bin/node

    info "Node.js" "Installing Node.js v$NODEJS_MAJOR.$NODEJS_MINOR."
    apt-get -qq install -y ca-certificates curl gnupg >/dev/null 2>&1
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg >/dev/null 2>&1
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
    apt-get -qq update >/dev/null 2>&1
    if ! apt-get -qq install -y nodejs >/dev/null 2>&1; then
        error "Failed to install Node.js."
        return 1
    fi

    info "Node.js" "Node.js updated successfully."
    return 0
}

function proof_npm() {
    npm_version=$(npm -v 2>/dev/null)
    npm_major=$(echo "$npm_version" | cut -d. -f1)
    npm_minor=$(echo "$npm_version" | cut -d. -f2)

    info "npm" "Found npm version: $npm_version"

    if [[ "$npm_major" -gt 10 ]] || [[ "$npm_major" -eq 10 && "$npm_minor" -ge 6 ]]; then
        info "npm" "npm version matches our requirements."
    else
        warn "npm version $npm_version does not meet the requirements. Attempting to update..."
        apt-get -qq --only-upgrade install npm >/dev/null 2>&1 || warn "[WARN] Failed to upgrade npm with apt-get."
        npm install npm@10.7.0 -g >/dev/null 2>&1 || warn "[WARN] Failed to upgrade npm globally using npm."

        hash -r
        npm_version_updated=$(npm -v 2>/dev/null)
        npm_major_updated=$(echo "$npm_version_updated" | cut -d. -f1)
        npm_minor_updated=$(echo "$npm_version_updated" | cut -d. -f2)

        if [[ "$npm_major_updated" -gt 10 ]] || [[ "$npm_major_updated" -eq 10 && "$npm_minor_updated" -ge 6 ]]; then
            info "npm" "npm version updated successfully to $npm_version_updated."
        else
            warn "Failed to meet npm version requirements even after attempting updates."
            confirm "npm Update Error" "npm version requirements were not met. Installation may fail."
            return 1
        fi
    fi

    return 0
}

function check_npm() {
    if command -v npm &>/dev/null; then
        info "npm" "npm is installed."
    else
        warn "npm is not installed. Attempting to install..."
        if ! apt-get -qq update >/dev/null 2>&1; then
            warn "Failed to run apt-get update."
            confirm "npm Installation Error" "npm could not be installed. Installation of Photobooth may fail."
            return 1
        fi
        if ! apt-get -qq install -y npm >/dev/null 2>&1; then
            warn "Failed to install npm via apt-get."
            confirm "npm Installation Error" "npm could not be installed. Installation of Photobooth may fail."
            return 1
        fi
    fi

    if ! proof_npm; then
        confirm "npm Update Error" "npm version requirements were not met. Installation may fail."
    fi

    return 0
}

function check_python() {
    PYTHON_VERSION=$(python3 --version 2>&1 || echo "Python not found")
    info "Python" "Python version: $PYTHON_VERSION"

    if [[ "$PYTHON_VERSION" == "Python not found" ]]; then
        error "Python3 is not installed. Please install Python3 to ensure compatibility."
        return 2
    fi

    PYTHON_MAJOR_VERSION=$(python3 -c "import sys; print(sys.version_info.major)" 2>/dev/null)
    PYTHON_MINOR_VERSION=$(python3 -c "import sys; print(sys.version_info.minor)" 2>/dev/null)

    if [[ -z "$PYTHON_MAJOR_VERSION" || -z "$PYTHON_MINOR_VERSION" ]]; then
        error "Unable to detect Python version."
        return 3
    fi

    if [ "$PYTHON_MAJOR_VERSION" -eq 3 ] && [ "$PYTHON_MINOR_VERSION" -ge 12 ]; then
        info "Python" "Python version is 3.12 or newer. Attempting to install python3-distutils..."

        # Attempt to install python3-distutils silently
        if apt-get -qq install python3-distutils -y >/dev/null 2>&1; then
            info "Python" "python3-distutils installed successfully."
        else
            warn "Failed to install python3-distutils. Attempting to install python3-setuptools..."
            if apt-get -qq install python3-setuptools -y >/dev/null 2>&1; then
                info "Python" "python3-setuptools installed successfully."
            else
                warn "Installation of python3-distutils and python3-setuptools failed. Photobooth installation might continue, but could encounter issues."
            fi
        fi
    else
        info "Python" "Python version is older than 3.12. No need to install distutils separately."
    fi

    return 0
}

# ==================================================
# Printer setup
# ==================================================
function restart_cups() {
    if /etc/init.d/cups restart >/dev/null 2>&1; then
        info "CUPS Service" "Restarted CUPS service successfully."
        return 0
    else
        warn "Failed to restart the CUPS service."
        return 2
    fi
}

function cups_enable_remote_any() {
    if cupsctl --remote-any >/dev/null 2>&1; then
        info "CUPS Remote Print" "Enabled printing from any address."
        restart_cups
        return $?
    else
        warn "Failed to enable printing from any address."
        return 1
    fi
}

function cups_disable_remote_any() {
    if cupsctl --no-remote-any >/dev/null 2>&1; then
        info "CUPS Remote Print" "Disabled printing from any address."
        restart_cups
        return $?
    else
        warn "Failed to disable printing from any address."
        return 1
    fi
}

function cups_enable_share() {
    if cupsctl --share-printers >/dev/null 2>&1; then
        info "CUPS Printer Sharing" "Enabled printer sharing."
        restart_cups
        return $?
    else
        warn "Failed to enable printer sharing."
        return 1
    fi
}

function cups_disable_share() {
    if cupsctl --no-share-printers >/dev/null 2>&1; then
        info "CUPS Printer Sharing" "Disabled printer sharing."
        restart_cups
        return $?
    else
        warn "Failed to disable printer sharing."
        return 1
    fi
}

function cups_enable_remote_admin() {
    if cupsctl --remote-admin >/dev/null 2>&1; then
        info "CUPS Remote Admin" "Enabled remote administration."
        restart_cups
        return $?
    else
        warn "Failed to enable remote administration."
        return 1
    fi
}

function cups_disable_remote_admin() {
    if cupsctl --no-remote-admin >/dev/null 2>&1; then
        info "CUPS Remote Admin" "Disabled remote administration."
        restart_cups
        return $?
    else
        warn "Failed to disable remote administration."
        return 1
    fi
}

# ==================================================
#
# ==================================================
function update_php_ini() {
    local php_ini=$1
    local bu_date
    bu_date=$(date +%Y%m%d%H%M%S)

    # If no path provided OR file doesn't exist --> auto-detect
    if [ -z "$php_ini" ] || [ ! -f "$php_ini" ]; then
        if [ ! -f "$php_ini" ]; then
            warn "The file '$php_ini' does not exist. Trying to auto-detect..."
        fi
        local php_version
        php_version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
        local php_base="/etc/php/$php_version"
        local candidates=(
            "$php_base/apache2/php.ini"
            "$php_base/fpm/php.ini"
        )

        for candidate in "${candidates[@]}"; do
            if [ -f "$candidate" ]; then
                php_ini="$candidate"
                info "PHP INI Update" "Auto-detected php.ini at '$php_ini'."
                break
            fi
        done

        if [ -z "$php_ini" ] || [ ! -f "$php_ini" ]; then
            warn "Could not locate php.ini (checked apache2 and fpm)."
            return 2
        fi
    fi

    local php_ini_bak=$php_ini$bu_date.bak

    # Backup the original file
    if ! cp "$php_ini" "$php_ini_bak"; then
        error "Failed to create a backup of '$php_ini'."
        return 3
    fi
    info "PHP INI Update" "Backup of PHP INI created at '$php_ini_bak'."

    # Update upload_max_filesize
    if sed -i 's/^upload_max_filesize =.*/upload_max_filesize = 20M/' "$php_ini"; then
        info "PHP INI Update" "Updated upload_max_filesize to 20M in '$php_ini'."
    else
        warn "Failed to update upload_max_filesize in '$php_ini'."
        return 4
    fi

    # Update post_max_size
    if sed -i 's/^post_max_size =.*/post_max_size = 20M/' "$php_ini"; then
        info "PHP INI Update" "Updated post_max_size to 20M in '$php_ini'."
    else
        warn "Failed to update post_max_size in '$php_ini'."
        return 5
    fi

    if [[ "$HAS_SYSTEMD" == true ]]; then
        if [[ "$php_ini" == *"/apache2/"* ]]; then
            systemctl restart apache2 && info "PHP INI Update" "Restarted Apache2."
        elif [[ "$php_ini" == *"/fpm/"* ]]; then
            systemctl restart php"$php_version"-fpm && info "PHP INI Update" "Restarted PHP-FPM."
        else
            warn "No service restart performed."
        fi
    fi

    return 0
}

# ==================================================
# Preview
# ==================================================
function setup_go2rtc_gphoto2() {
    GO2RTC_YAML_STREAM="photobooth: exec:gphoto2 --capture-movie --stdout#killsignal=2"
    GO2RTC_CAPTURE_CMD="gphoto2"
    GO2RTC_CAPTURE_ARGS="--set-config output=Off --capture-image-and-download --filename=\$1"
    GO2RTC_NOTE="don't forget to add --filename=%s."
}

function setup_go2rtc_rpicam() {
    local codec_format="$1"

    GO2RTC_YAML_STREAM="photobooth: exec:rpicam-vid -t 0 $codec_format --width 2304 --height 1296 -o -#killsignal=2"
    GO2RTC_CAPTURE_CMD="rpicam-still"
    GO2RTC_CAPTURE_ARGS="-n -q 100 -o \$1"
    GO2RTC_NOTE="don't forget to add -o %s."
}

function setup_go2rtc_libcamera() {
    local codec_format="$1"

    GO2RTC_YAML_STREAM="photobooth: exec:libcamera-vid -t 0 $codec_format --width 2304 --height 1296 -o -#killsignal=2"
    GO2RTC_CAPTURE_CMD="libcamera-still"
    GO2RTC_CAPTURE_ARGS="-n -q 100 -o \$1"
    GO2RTC_NOTE="don't forget to add -o %s."
}

function setup_go2rtc_fswebcam() {
    GO2RTC_CAPTURE_CMD="sleep 1;fswebcam"
    GO2RTC_CAPTURE_ARGS="--no-banner -d /dev/video0 -r 1280x720 \$1"
    GO2RTC_NOTE="don't forget to add %s."
    GO2RTC_YAML_STREAM="photobooth: exec:ffmpeg -hide_banner -v error -f v4l2 -input_format mjpeg -video_size 1280x720 -i /dev/video0 -c copy -f mjpeg -#killsignal=2"
}

function ask_go2rtc_version() {
    local options=()
    GO2RTC_VERSION=''
    while true; do
        for i in "${!GO2RTC_VERSIONS[@]}"; do
            options+=("$((i + 1))" "${GO2RTC_VERSIONS[i]}")
        done

        if CHOICE=$(whiptail --title "Select go2rtc Version" \
        --menu "Available go2rtc versions:" 20 60 10 \
        "${options[@]}" 3>&1 1>&2 2>&3); then
            GO2RTC_VERSION=${GO2RTC_VERSIONS[$((CHOICE - 1))]}
            info "Selected go2rtc version: $GO2RTC_VERSION"
            return 0
        else
            error "No go2rtc version selected. Aborting setup."
            return 1
        fi
    done
}

function install_go2rtc() {
    local arch="${1:-}"
    local go2rtc_version="${2:-$DEFAULT_GO2RTC_VERSION}"
    local go2rtc_update_only="${3:-false}"

    # If no arch passed, auto-detect
    if [ -z "$arch" ]; then
        arch="$(uname -m)"
    fi

    info "Install go2rtc" "Starting installation of go2rtc version ${go2rtc_version}..."

    local os
    local file
    local install_bin=false
    local installed_version
    local is_zip=false

    # Stop service if possible
    if [[ "$HAS_SYSTEMD" == true ]] && [[ -f /etc/systemd/system/go2rtc.service ]] && systemctl is-active --quiet go2rtc.service; then
        if ! systemctl stop go2rtc.service &>/dev/null; then
            error "Failed to stop go2rtc service."
            return 5
        fi
    fi

    # Check if go2rtc is already installed
    if command -v go2rtc &>/dev/null; then
        installed_version=$(go2rtc -version 2>&1 | grep -oP 'version=\K[0-9]+\.[0-9]+\.[0-9]+' || go2rtc -version 2>&1 | grep -oP 'go2rtc version \K[0-9]+\.[0-9]+\.[0-9]+')
        if [[ $installed_version == "$go2rtc_version" ]]; then
            info "Install go2rtc" "go2rtc version ${go2rtc_version} already installed."
            return 0
        fi
        info "Install go2rtc" "Found go2rtc version: $installed_version. Updating to version: $go2rtc_version"
    else
        if [[ "$go2rtc_update_only" == true ]]; then
            error "go2rtc not installed. Cannot update!"
            return 1
        fi
        info "Install go2rtc" "Installing go2rtc version: $go2rtc_version"
    fi

    # Detect OS
    if [[ "$OSTYPE" =~ linux ]]; then
        os="linux"
    elif [[ "$OSTYPE" =~ darwin ]]; then
        os="mac"
        is_zip=true
    else
        error "Unsupported OS for go2rtc: $OSTYPE"
        return 2
    fi

    case "$arch" in
        x86_64|amd64) goarch="amd64" ;;
        i386) goarch="i386" ;;
        armv7l|arm) goarch="arm" ;;
        armv6l) goarch="armv6" ;;
        aarch64|arm64) goarch="arm64" ;;
        mips|mipsel) goarch="mipsel" ;;
        *)
            error "Unsupported go2rtc architecture: $arch"
            return 3
            ;;
    esac

    # Download and install go2rtc
    extension=""
    if [[ "$is_zip" == true ]]; then
        extension=".zip"
    fi
    file="go2rtc_${os}_${goarch}${extension}"

    install_bin=true

    if [[ "$install_bin" == true ]]; then
        mkdir -p /usr/local/bin
        if [[ "$is_zip" == true ]]; then
            if ! wget -qO /tmp/go2rtc.zip "https://github.com/AlexxIT/go2rtc/releases/download/v${GO2RTC_VERSION}/${file}" &>/dev/null; then
                error "Failed to download go2rtc zip file."
                return 4
            fi
            if ! unzip -p /tmp/go2rtc.zip go2rtc >/usr/local/bin/go2rtc; then
                error "Failed to unzip go2rtc binary."
                return 4
            fi
            rm /tmp/go2rtc.zip
        else
            if ! wget -qO /usr/local/bin/go2rtc "https://github.com/AlexxIT/go2rtc/releases/download/v${GO2RTC_VERSION}/${file}" &>/dev/null; then
                error "Failed to download go2rtc binary."
                return 4
            fi
        fi
        chmod +x /usr/local/bin/go2rtc
        info "Install go2rtc" "go2rtc binary installed successfully."
    fi

    # Service handling
    if [[ -f /etc/systemd/system/go2rtc.service ]]; then
        if [[ "$HAS_SYSTEMD" == true ]]; then
            if ! systemctl start go2rtc.service &>/dev/null; then
                error "Failed to start go2rtc service."
                return 5
            fi
            if ! systemctl enable go2rtc.service &>/dev/null; then
                error "Failed to enable go2rtc service."
            fi
            info "Install go2rtc" "go2rtc service started and enabled."
        else
            mkdir -p /etc/systemd/system/multi-user.target.wants
            if [[ ! -L /etc/systemd/system/multi-user.target.wants/go2rtc.service ]]; then
                ln -s /etc/systemd/system/go2rtc.service /etc/systemd/system/multi-user.target.wants/go2rtc.service
                info "Install go2rtc" "Created symlink for go2rtc.service (systemctl not available)."
            fi
        fi
    fi

    if [[ ! -f /etc/sudoers.d/020_www-data-systemctl ]]; then
        info "Install go2rtc" "Creating /etc/sudoers.d/020_www-data-systemctl"
        cat >/etc/sudoers.d/020_www-data-systemctl <<EOF
# Control streaming software
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl start go2rtc.service, /usr/bin/systemctl stop go2rtc.service
EOF
    fi

    info "Install go2rtc" "go2rtc installation complete!"
    return 0
}

function uninstall_go2rtc() {
    info "go2rtc" "Uninstalling the camera streaming service..."

    # Stop and remove the service if it exists
    if [[ -f /etc/systemd/system/go2rtc.service ]]; then
        if [[ "$HAS_SYSTEMD" == true ]]; then
            if systemctl is-active --quiet go2rtc.service; then
                systemctl stop go2rtc.service > /dev/null 2>&1 || error "Failed to stop go2rtc.service"
            fi
            systemctl disable go2rtc.service &>/dev/null || error "Failed to disable go2rtc.service"
            systemctl daemon-reload &>/dev/null || error "Failed to reload systemd daemon"
        else
            # Remove symlink for chroot / systemd not available
            SYMLINK="/etc/systemd/system/multi-user.target.wants/go2rtc.service"
            if [[ -L "$SYMLINK" ]]; then
                rm -f "$SYMLINK" && info "Removed symlink $SYMLINK"
            fi
        fi

        # Remove the service file itself
        rm -f /etc/systemd/system/go2rtc.service || error "Failed to remove /etc/systemd/system/go2rtc.service"
    fi

    # Remove related files
    rm -f /etc/go2rtc.yaml || error "Failed to remove /etc/go2rtc.yaml"
    rm -f /usr/local/bin/go2rtc || error "Failed to remove /usr/local/bin/go2rtc"
    rm -f /usr/local/bin/capture || error "Failed to remove /usr/local/bin/capture"
    rm -f /etc/sudoers.d/020_www-data-systemctl || error "Failed to remove /etc/sudoers.d/020_www-data-systemctl"

    info "go2rtc" "Uninstallation complete!"
}

function go2rtc_config() {
    local backend="$1"
    local codec_format="$2"
    local create_capture_wrapper=true create_go2rtc_cfg=true

    # Validate arguments
    if [[ -z "$backend" ]]; then
        error "go2rtc config" "No backend specified (gphoto2, rpicam, libcamera, fswebcam)."
        return 1
    fi
    if [[ -z "$codec_format" ]]; then
        codec_format=""
    fi

    # Initialize GO2RTC_* vars via setup function
    case "$backend" in
        gphoto2)
            setup_go2rtc_gphoto2
            ;;
        rpicam)
            setup_go2rtc_rpicam "$codec_format"
            ;;
        libcamera)
            setup_go2rtc_libcamera "$codec_format"
            ;;
        fswebcam)
            setup_go2rtc_fswebcam
            ;;
        *)
            error "go2rtc config" "Unknown backend: $backend"
            return 1
            ;;
    esac

    # Handle configuration
    if [[ -f "/etc/go2rtc.yaml" ]]; then
        if [[ "$SILENT" == true ]]; then
            create_go2rtc_cfg=true
            info "go2rtc config" "Silent mode: Recreating go2rtc configuration at /etc/go2rtc.yaml"
        else
            if ! whiptail --yesno "go2rtc configuration file exists. Recreate it?" 10 60; then
                create_go2rtc_cfg=false
                error "Skipping go2rtc configuration..."
            fi
        fi
    fi

    if [[ "$create_go2rtc_cfg" == true ]]; then
        info "go2rtc config" "Creating go2rtc configuration at /etc/go2rtc.yaml"
        cat >/etc/go2rtc.yaml <<EOF
---
streams:
  $GO2RTC_YAML_STREAM
log:
  exec: trace
EOF
    fi

    # Create or validate systemd service
    if [[ ! -f /etc/systemd/system/go2rtc.service ]]; then
        info "go2rtc service" "Creating go2rtc systemd service."
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
        if [[ "$HAS_SYSTEMD" == true ]]; then
            systemctl daemon-reload > /dev/null 2>&1
            if systemctl enable --now go2rtc.service > /dev/null 2>&1; then
                info "go2rtc service" "go2rtc service is now active."
            else
                error "Failed to enable or start go2rtc service."
            fi
        else
            mkdir -p /etc/systemd/system/multi-user.target.wants
            ln -sf /etc/systemd/system/go2rtc.service /etc/systemd/system/multi-user.target.wants/go2rtc.service
            info "go2rtc service" "Systemd not available. Created symlink for future enablement."
        fi
    fi

    # Handle capture script
    if [[ -f "/usr/local/bin/capture" ]]; then
        if [[ "$SILENT" == true ]]; then
            create_capture_wrapper=true
            info "go2rtc wrapper" "Silent mode: Recreating go2rtc capture script."
        else
            if ! whiptail --yesno "Capture script exists. Recreate it?" 10 60; then
                create_capture_wrapper=false
                error "Skipping capture script..."
            fi
        fi
    fi

    if [[ "$create_capture_wrapper" == true ]]; then
        info "go2rtc wrapper" "Creating capture script."
        cat >/usr/local/bin/capture <<EOF
#!/bin/bash
if [[ \$1 =~ -h|--help ]]; then
  cat <<HELP
This script stops go2rtc, runs $GO2RTC_CAPTURE_CMD and starts go2rtc again.
You can use it in your photobooth as capture command.
Usage:
    capture <filename> [or all required $GO2RTC_CAPTURE_CMD arguments]
In photobooth, usually 'capture %s' is enough. But if you want to use a more complex command,
$GO2RTC_NOTE
HELP
  exit 0
fi
if [[ \$# -eq 1 ]]; then
    args="$GO2RTC_CAPTURE_ARGS"
elif [[ \$# -gt 1 ]]; then
    args="\$@"
fi
if systemctl cat go2rtc.service >/dev/null; then
    HAS_GO2RTC=1
fi
[[ -n "\$HAS_GO2RTC" ]] && sudo systemctl stop go2rtc.service
$GO2RTC_CAPTURE_CMD \$args
[[ -n "\$HAS_GO2RTC" ]] && sudo systemctl start go2rtc.service

EOF
        chmod +x /usr/local/bin/capture
    fi

    info "go2rtc" "Configuration complete!"
    return 0
}

function add_cameracontrol_cronjob() {
    if ! check_installfolderpath; then
        return 1
    fi

    local cron_job="@reboot /usr/bin/python3 \"$INSTALLFOLDERPATH/api/cameracontrol.py\" -b"
    local current_crontab

    current_crontab=$(sudo -u www-data crontab -l 2>/dev/null)

    if echo "$current_crontab" | grep -qF "$cron_job"; then
        info "Cron job" "Cron job already exists."
    else
        if (echo "$current_crontab"; echo "$cron_job") | sudo -u www-data crontab -; then
            info "Cron job" "Cron job added to start cameracontrol.py at boot in bsm mode as www-data user"
        else
            error "Failed to add cron job."
            return 1
        fi
    fi

    return 0
}

function remove_cameracontrol_cronjob() {
    local current_crontab
    current_crontab=$(sudo -u www-data crontab -l 2>/dev/null) || return 1

    [[ -z "$current_crontab" ]] && return 2

    echo "$current_crontab" | grep -v -E '/var/www/html/api/cameracontrol.py|/var/www/html/photobooth/api/cameracontrol.py' | sudo -u www-data crontab - >/dev/null 2>&1 || return 3

    return 0
}

function create_ffmpeg_webcam_service() {
    if ! check_installfolderpath; then
        return 1
    fi

    if ! cat >/etc/systemd/system/ffmpeg-webcam.service <<EOF
[Unit]
Description=ffmpeg webcam service
After=network.target

[Service]
Type=simple
Restart=on-failure
ExecStart=/usr/bin/python3 $INSTALLFOLDERPATH/api/cameracontrol.py --forceRecreateCam
ExecStop=/usr/bin/python3 $INSTALLFOLDERPATH/api/cameracontrol.py --exit

[Install]
WantedBy=multi-user.target
EOF
    then
        return 1
    fi

    if [[ "$HAS_SYSTEMD" == true ]]; then
        if ! systemctl daemon-reload >/dev/null 2>&1; then
            return 2
        fi
        if ! systemctl enable --now ffmpeg-webcam.service >/dev/null 2>&1; then
            return 3
        fi
    else
        mkdir -p /etc/systemd/system/multi-user.target.wants
        if ! ln -sf /etc/systemd/system/ffmpeg-webcam.service \
                    /etc/systemd/system/multi-user.target.wants/ffmpeg-webcam.service; then
            return 4
        fi
    fi

    return 0
}

function remove_ffmpeg_webcam_service() {
    if [[ -f /etc/systemd/system/ffmpeg-webcam.service ]]; then
        if [[ "$HAS_SYSTEMD" == true ]]; then
            if ! systemctl disable --now ffmpeg-webcam.service >/dev/null 2>&1; then
                return 1
            fi
            if ! rm -f /etc/systemd/system/ffmpeg-webcam.service >/dev/null 2>&1; then
                return 2
            fi
            if ! systemctl daemon-reload >/dev/null 2>&1; then
                return 3
            fi
        else
            local symlink="/etc/systemd/system/multi-user.target.wants/ffmpeg-webcam.service"
            if [[ -L "$symlink" ]] && ! rm -f "$symlink"; then
                return 4
            fi
            if ! rm -f /etc/systemd/system/ffmpeg-webcam.service; then
                return 5
            fi
        fi
    fi

    if [[ -f /usr/ffmpeg-webcam.sh ]]; then
        if ! rm -f /usr/ffmpeg-webcam.sh >/dev/null 2>&1; then
            return 6
        fi
    fi

    return 0
}

function setup_gphoto_webcam() {
    if ! mkdir -p /etc/modules-load.d /etc/modprobe.d >/dev/null 2>&1; then
        return 1
    fi

    if ! echo "v4l2loopback" >/etc/modules-load.d/v4l2loopback.conf; then
        return 2
    fi

    if ! cat >/etc/modprobe.d/v4l2loopback.conf <<EOF
options v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam"
blacklist bcm2835-isp
EOF
    then
        return 3
    fi

    if ! modprobe v4l2loopback exclusive_caps=1 card_label="GPhoto2 Webcam" >/dev/null 2>&1; then
        return 4
    fi

    if lsmod | grep -q "bcm2835-isp"; then
        if rmmod bcm2835-isp; then
            info "gphoto2 webcam" "Removed bcm2835-isp kernel module."
        else
            return 5
        fi
    else
        info "gphoto2 webcam" "bcm2835-isp kernel module is not currently loaded."
    fi

    return 0
}

function remove_gphoto_webcam() {
    local error_count=0

    info "gphoto2 webcam" "Removing gphoto2 webcam setup..."

    if [[ -f /etc/modprobe.d/v4l2loopback.conf ]]; then
        if rm /etc/modprobe.d/v4l2loopback.conf; then
            info "gphoto2 webcam" "Removed v4l2loopback configuration file."
        else
            error "Failed to remove v4l2loopback configuration file."
            ((error_count++))
        fi
    else
        info "gphoto2 webcam" "No v4l2loopback configuration file found to remove."
    fi

    if lsmod | grep -q "v4l2loopback"; then
        if rmmod v4l2loopback; then
            info "gphoto2 webcam" "Removed v4l2loopback kernel module."
        else
            error "Failed to remove v4l2loopback module. It might still be in use."
            ((error_count++))
        fi
    else
        info "gphoto2 webcam" "v4l2loopback kernel module is not currently loaded."
    fi

    remove_ffmpeg_webcam_service
    case $? in
        0)
            info "FFmpeg webcam service" "FFmpeg services cleaned successfully."
            ;;
        1)
            error "Failed to disable the FFmpeg webcam service."
            ((error_count++))
            ;;
        2)
            error "Failed to remove the FFmpeg webcam service file."
            ((error_count++))
            ;;
        3)
            error "Failed to reload the systemd daemon."
            ((error_count++))
            ;;
        4)
            error "Failed to remove the FFmpeg webcam symlink (non-systemd)."
            ((error_count++))
            ;;
        5)
            error "Failed to remove the FFmpeg webcam service file (non-systemd)."
            ((error_count++))
            ;;
        6)
            error "Failed to remove the ffmpeg-webcam.sh script."
            ((error_count++))
            ;;
    esac

    remove_cameracontrol_cronjob
    case $? in
        0)
            info "Cron job" "Cron job removed successfully."
            ;;
        1)
            error "Failed to retrieve the crontab."
            ((error_count++))
            ;;
        2)
            info "Cron job" "No existing cron job to remove."
            ;;
        3)
            error "Failed to update the crontab."
            ((error_count++))
            ;;
    esac

    info "gphoto2 webcam" "gphoto2 webcam setup removal completed."
    return $error_count
}

# ==================================================
# Rembg
# ==================================================
function rembg_install() {
    local script_dir="/var/www/rembg"
    local venv_dir="$script_dir/rembg_venv"

    mkdir -p "$script_dir"
    chown -R www-data:www-data "$script_dir"

    info "Rembg" "Installing dependencies..."
    if command -v apt >/dev/null 2>&1; then
        if ! install_packages "${REMBG_PACKAGES[@]}"; then
            return 1
        fi
    else
        error "Rembg: Unsupported package manager."
        return 2
    fi

    rm -rf "$venv_dir"

    info "Rembg" "Creating virtual environment..."
    sudo -u www-data bash -lc "python3 -m venv '$venv_dir'" || return 3

    local venv_py="$venv_dir/bin/python"

    info "Rembg" "Upgrading pip..."
    sudo -u www-data bash -lc "'$venv_py' -m pip install --upgrade pip >/dev/null 2>&1" || return 4

    info "Rembg" "Installing Python dependencies..."
    local pkg
    for pkg in "${REMBG_PIP_PACKAGES[@]}"; do
        sudo -u www-data bash -lc "'$venv_py' -m pip install \"$pkg\" >/dev/null 2>&1" || return 5
    done

    info "Rembg" "Installing systemd service..."
    cat >/etc/systemd/system/rembg.service <<EOF
[Unit]
Description=Rembg Background Removal Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$script_dir
ExecStart=$venv_dir/bin/rembg s --host 0.0.0.0 --port 7000 --log_level info
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

    if [[ "$HAS_SYSTEMD" == true ]]; then
        systemctl daemon-reload >/dev/null 2>&1
        systemctl enable rembg.service >/dev/null 2>&1 || return 6
        systemctl start rembg.service >/dev/null 2>&1 || return 7

    else
        local symlink="/etc/systemd/system/multi-user.target.wants/rembg.service"

        if [[ -L "$symlink" ]]; then
            rm -f "$symlink" && info "Rembg" "Removed old symlink: $symlink"
        fi

        if [[ -d "/etc/systemd/system/multi-user.target.wants" ]]; then
            ln -s ../rembg.service "$symlink" \
            && info "Rembg" "Created symlink: $symlink"
        else
            info "Rembg" "Symlink directory missing, skipping manual enable."
        fi
    fi

    confirm "Rembg Installation" "Rembg installed successfully."
    return 0
}

function rembg_remove() {
    local script_dir="$INSTALLFOLDERPATH/rembg"

    if [[ "$HAS_SYSTEMD" == true ]]; then
        if systemctl is-active --quiet rembg.service 2>/dev/null; then
            systemctl stop rembg.service >/dev/null 2>&1 || true
        fi

        if systemctl is-enabled --quiet rembg.service 2>/dev/null; then
            systemctl disable rembg.service >/dev/null 2>&1 || true
        fi
        systemctl daemon-reload >/dev/null 2>&1

    else
        local SYMLINK="/etc/systemd/system/multi-user.target.wants/rembg.service"

        [[ -L "$SYMLINK" ]] && rm -f "$SYMLINK"
    fi

    rm -f /etc/systemd/system/rembg.service
    rm -rf "$script_dir"

    confirm "Rembg Removal" "Rembg and its service were removed."
    return 0
}

# ==================================================
# Installation / update functions
# ==================================================

function check_git_install() {
    if photobooth_installed; then
        if ! cd "$INSTALLFOLDERPATH"; then
            confirm "GitHub check" "Failed to navigate to $INSTALLFOLDERPATH. Please check the path."
            GIT_INSTALLED=false
            return 1
        fi
        if [ "$(sudo -u www-data git rev-parse --is-inside-work-tree 2>/dev/null)" = true ]; then
            info "GitHub check" "Photobooth installed via git."
            GIT_INSTALLED=true
            return 0
        else
            info "GitHub check" "Not a git Installation."
            GIT_INSTALLED=false
            return 2
        fi
    else
        info "GitHub check" "Photobooth installation not found."
        GIT_INSTALLED=false
        return 3
    fi
}

function check_remote_sha() {
    local json_data
    local commit_sha

    json_data=$(curl -s "$REMOTE_BRANCH_API")
    if [[ -z "$json_data" ]]; then
        info "Error" "Failed to retrieve remote branch data."
        return 1
    fi

    commit_sha=$(echo "$json_data" | grep -oP '"sha":\s*"\K[0-9a-f]{40}' | head -n 1)
    if [[ -z "$commit_sha" ]]; then
        info "Error" "Failed to retrieve the latest commit SHA."
        return 2
    fi

    REMOTE_BRANCH_SHA="$commit_sha"
    return 0
}

function check_photobooth_version() {
    if [ "$PHOTOBOOTH_FOUND" = true ]; then
        local version="Unknown"
        local git_hash=""
        local update_available=""

        # Attempt to navigate to the installation folder
        if cd "$INSTALLFOLDERPATH" >/dev/null 2>&1; then

            # Extract version from package.json if it exists
            if [ -f "package.json" ]; then
                version=$(grep -oP '(?<="version": ")[^"]*' package.json 2>/dev/null || echo "Unknown")
            else
                confirm "Warning" "package.json not found. Unable to retrieve the version."
            fi

            # If installed via Git, append the Git hash
            if [ "$GIT_INSTALLED" = true ]; then
                git_hash=$(sudo -u www-data git log --pretty=format:'%h' -n 1 2>/dev/null)
                if [ -n "$git_hash" ]; then
                    version="$version (Git: $git_hash)"

                    # Check for remote updates
                    if check_remote_sha; then
                        if [[ "$REMOTE_BRANCH_SHA" == "$git_hash"* ]]; then
                            update_available="No updates available compared with the selected branch ${BRANCH}."
                        else
                            update_available="Update available on the selected branch ${BRANCH}."
                        fi
                    else
                        update_available="Unable to fetch latest changes for the selected branch ${BRANCH}."
                    fi
                fi
            fi

            # Display Photobooth version information
            confirm "Photobooth Version" "Photobooth detected.\nVersion: $version\n$update_available"
        else
            confirm "Error" "Failed to access the installation folder: $INSTALLFOLDERPATH."
            return 2
        fi
    else
        confirm "Error" "Photobooth not detected. Please install Photobooth."
        return 3
    fi

    return 0
}

function add_git_remote() {
    info "GitHub remote" "Checking needed remote information..."
    if sudo -u www-data git config remote.photoboothproject.url >/dev/null; then
        info "GitHub remote" "photoboothproject remote exist already"
    else
        info "GitHub remote" "Adding photoboothproject remote..."
        if ! sudo -u www-data git remote add photoboothproject https://github.com/PhotoboothProject/photobooth.git; then
            error "Adding photoboothproject remote failed."
            confirm "GitHub remote Error" "Failed to add the Photobooth remote repository."
            return 1
        fi
    fi
    return 0
}

function do_git_clone() {
    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        error "INSTALLFOLDERPATH is not defined or empty!"
        confirm "Error" "The target installation path is missing.\nPlease define INSTALLFOLDERPATH first."
        return 1
    fi

    mkdir -p "$INSTALLFOLDERPATH"
    chown www-data:www-data "$INSTALLFOLDERPATH"

    info "GitHub clone" "Cloning the Photobooth repository..."
    if ! sudo -u www-data git clone https://github.com/PhotoboothProject/photobooth "$INSTALLFOLDERPATH" >/dev/null 2>&1; then
        error "Failed to clone the Photobooth repository."
        confirm "Error: git clone failed" "Failed to clone the Photobooth repository.\nPlease check your network connection and permissions, then retry."
        return 1
    fi
    info "GitHub clone" "Repository cloned successfully."
    return 0
}

function start_git_install() {
    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        error "INSTALLFOLDERPATH is not defined or empty!"
        confirm "Error" "The target installation path is missing.\nPlease define INSTALLFOLDERPATH first."
        return 1
    fi

    cd "$INSTALLFOLDERPATH" || { error "Failed to navigate to $INSTALLFOLDERPATH"; return 1; }

    # Add Git remote (if applicable)
    add_git_remote >/dev/null 2>&1

    # Configure Git settings and fetch the specified branch
    info "GitHub installation" "Installing/Updating Photobooth via git."
    sudo -u www-data git config core.fileMode false >/dev/null 2>&1
    sudo -u www-data git fetch photoboothproject "$BRANCH" >/dev/null 2>&1
    if ! sudo -u www-data git checkout photoboothproject/"$BRANCH" >/dev/null 2>&1; then
        error "Failed to fetch or checkout the branch: $BRANCH."
        return 2
    fi

    # Update Git submodules
    if ! sudo -u www-data git submodule update --init >/dev/null 2>&1; then
        error "Failed to update Git submodules."
        return 2
    fi

    # Handle local changes, if any
    if [ -f "0001-backup-changes.patch" ]; then
        info "GitHub installation" "Attempting to reapply local changes..."
        if sudo -u www-data git am --whitespace=nowarn "0001-backup-changes.patch" >/dev/null 2>&1; then
            info "GitHub installation" "Local changes reapplied successfully."
            sudo -u www-data git reset --soft HEAD^ >/dev/null 2>&1
        else
            error "Failed to reapply local changes."
            sudo -u www-data git am --abort >/dev/null 2>&1
        fi

        sudo -u www-data mv "0001-backup-changes.patch" "$INSTALLFOLDERPATH/private/$(date +%Y%m%d%H%M%S)-backup-changes.patch" >/dev/null 2>&1
    fi

    # Notify about npm installation and build
    info "GitHub installation" "Preparing for npm installation and build. This may take up to 15 minutes."

    # Create necessary directories and set permissions
    mkdir -p /var/www/.npm /var/www/.cache >/dev/null 2>&1
    chown www-data:www-data /var/www/.npm /var/www/.cache >/dev/null 2>&1

    # Run npm installation and build
    if ! sudo -u www-data npm install >/dev/null 2>&1; then
        error "npm installation failed."
        return 3
    fi

    if ! sudo -u www-data npm run build >/dev/null 2>&1; then
        error "npm build process failed."
        return 3
    fi

    info "GitHub installation" "Photobooth installation completed successfully."
    return 0
}

function fix_git_modules() {
    local PHOTOBOOTH_SUBMODULES=(
        'vendor/rpihotspot'
        'vendor/Seriously'
    )

    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        error "INSTALLFOLDERPATH is not defined or empty!"
        confirm "Error" "The target installation path is missing.\nPlease define INSTALLFOLDERPATH first."
        return 1
    fi

    cd "$INSTALLFOLDERPATH" || {
        error "Failed to change directory to $INSTALLFOLDERPATH"
        return 1
    }

    sudo -u www-data git config --global --add safe.directory "$INSTALLFOLDERPATH" >/dev/null 2>&1

    for submodule in "${PHOTOBOOTH_SUBMODULES[@]}"; do
        if [ -d "${INSTALLFOLDERPATH}/${submodule}" ]; then
            if grep -q "$submodule" "./.gitmodules"; then
                info "GitHub submodules" "Adding global safe.directory: ${INSTALLFOLDERPATH}/${submodule}"
                sudo -u www-data git config --global --add safe.directory "$INSTALLFOLDERPATH/$submodule" >/dev/null 2>&1
            else
                warn "${INSTALLFOLDERPATH}/${submodule} does not belong to our modules anymore."
                rm -rf "${INSTALLFOLDERPATH:?}/$submodule"
            fi
        fi
    done

    # Reset submodules
    if ! sudo -u www-data git submodule foreach --recursive git reset --hard >/dev/null 2>&1; then
        error "Failed to reset submodules."
        return 2
    fi

    # Deinitialize submodules
    if ! sudo -u www-data git submodule deinit -f . >/dev/null 2>&1; then
        error "Failed to deinitialize submodules."
        return 3
    fi

    # Initialize and update submodules
    if ! sudo -u www-data git submodule update --init --recursive >/dev/null 2>&1; then
        error "Failed to update submodules."
        return 4
    fi

    info "GitHub submodules" "Submodules fixed successfully."
    return 0
}

function commit_git_changes() {
    local backupbranch

    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        error "INSTALLFOLDERPATH is not defined or empty!"
        confirm "Error" "The target installation path is missing.\nPlease define INSTALLFOLDERPATH first."
        return 1
    fi

    # Change ownership of the installation folder
    chown -R www-data:www-data "$INSTALLFOLDERPATH"/ >/dev/null 2>&1 || warn "Failed to set ownership for $INSTALLFOLDERPATH"

    cd "$INSTALLFOLDERPATH" || {
        error "Failed to change directory to $INSTALLFOLDERPATH"
        return 1
    }

    # Fix Git submodules before proceeding
    fix_git_modules
    case $? in
        0)
            info "GitHub submodules" "Submodules fixed successfully."
            ;;
        1)
            error "Failed to access the installation folder. Check directory permissions."
            return 2
            ;;
        2)
            error "Failed to reset submodules. Ensure the Git repository is intact."
            return 2
            ;;
        3)
            error "Failed to deinitialize submodules. Check Git configuration."
            return 2
            ;;
        4)
            error "Failed to update submodules. Verify Git submodule setup."
            return 2
            ;;
        *)
            error "An unexpected error occurred while fixing submodules."
            return 2
            ;;
    esac

    # Ensure Git user.name is set
    if [ -z "$(sudo -u www-data git config user.name)" ]; then
        warn "Git user.name not set! Setting it to 'Photobooth'."
        sudo -u www-data git config user.name "Photobooth" >/dev/null 2>&1
    fi

    # Ensure Git user.email is set
    if [ -z "$(sudo -u www-data git config user.email)" ]; then
        warn "Git user.email not set! Setting it to 'Photobooth@localhost'."
        sudo -u www-data git config user.email "Photobooth@localhost" >/dev/null 2>&1
    fi

    info "Photobooth GitHub Update" "Git user.name: $(sudo -u www-data git config user.name)"
    info "Photobooth GitHub Update" "Git user.email: $(sudo -u www-data git config user.email)"

    # Check for uncommitted changes
    if [ "$(sudo -u www-data git status --porcelain)" = "" ]; then
        info "Photobooth GitHub Update" "No uncommitted changes detected."
    else
        backupbranch="backup-$(date +%Y%m%d%H%M%S)"
        if [ "$SILENT" = false ]; then
            # Ask user whether to proceed with committing changes
            if ! whiptail --title "Uncommitted Changes Detected" \
                --yesno "Uncommitted changes detected. Do you want to commit and keep them in a backup branch?\n\nNOTE: Changes will be kept in a local branch named '$backupbranch'." \
                12 60; then
                error "Uncommitted changes detected. Update aborted."
                return 4
            fi
        fi
        info "Photobooth GitHub Update" "Commiting changes and backing them up."

        # Commit changes
        sudo -u www-data git add --all >/dev/null 2>&1
        if ! sudo -u www-data git commit -a -m "backup changes" >/dev/null 2>&1; then
            error "Failed to commit changes."
            return 3
        fi

        GITHUB_PATCH=true
        if [ "$SILENT" = false ]; then
            if ! whiptail --title "Local changes" \
                --yesno "Local changes committed successfully!\nDo you want to reapply local changes after Update (if possible)?" \
                12 60; then
                GITHUB_PATCH=false
            fi
        fi
        if [ "$GITHUB_PATCH" = true ]; then
            info "Photobooth GitHub Update" "Creating patch and trying to reapply after Update."
            if ! sudo -u www-data git format-patch -1 >/dev/null 2>&1; then
                warn "Failed to create patch for changes. Proceeding without patch."
            fi
        fi

        if ! sudo -u www-data git checkout -b "$backupbranch" >/dev/null 2>&1; then
            error "Failed to create backup branch: $backupbranch"
            return 5
        fi
        info "Photobooth GitHub Update" "Backup branch created: $backupbranch"
    fi

    return 0
}

function install_or_update_photobooth() {
    local update=${1:-$GIT_INSTALLED}
    local setup_apache=true
    local exit_code=0
    BACKUPFOLDER="photobooth-backup-$(date +%Y%m%d%H%M%S)"

    if [ "$update" = true ]; then
        check_photobooth_version
        info "Photobooth installation" "Trying to update your Photobooth.\nUpdating requirements first if needed..."
    else
        if [ "$SILENT" = false ]; then
            if whiptail --title "Photobooth Installation" \
                --yesno "Is Photobooth the only website on this system?\n\nNOTE: If typing yes, the whole /var/www/html folder will be renamed to /var/www/$BACKUPFOLDER if it exists!" \
                10 60; then
                INSTALLFOLDER="html"
                INSTALLFOLDERPATH="/var/www/html"
                info "Photobooth Installation" "Installing in root directory."
            else
                INSTALLFOLDER="photobooth"
                INSTALLFOLDERPATH="/var/www/html/$INSTALLFOLDER"
                info "Photobooth Installation" "Installing in subfolder."
            fi
        else
            # Silent mode: default to root directory
            INSTALLFOLDER="html"
            INSTALLFOLDERPATH="/var/www/html"
            info "Photobooth Installation" "Silent mode enabled. Installing in root directory."
        fi
    fi

    if [[ -z "$INSTALLFOLDERPATH" ]]; then
        confirm "Error" "The target installation path is missing.\nPlease define INSTALLFOLDERPATH first."
        return 1
    fi

    if install_packages "${EXTRA_PACKAGES[@]}"; then
        info "Photobooth installation" "All extra packages installed successfully."
    else
        confirm "Package installation" "Installation process stopped due to an error."
        return 1
    fi

    if [ "$SKIP_PHP" = true ]; then
        info "Photobooth installation" "Skipping PHP setup."
        sleep 2
    else
        if ! prepare_php_environment; then
            confirm "PHP environment" "System preparation for PHP failed. Exiting."
            return 1
        fi

        if install_packages "${PHP_PACKAGES[@]}"; then
            info "Photobooth installation" "All PHP packages installed successfully."
        else
            confirm "PHP Package installation" "Installation process stopped due to an error."
            return 1
        fi
        if ! set_php_version_cli "$PHP_VERSION"; then
            confirm "PHP CLI" "Failed to setup PHP CLI. Ignoring..."
        fi
    fi

    if [ "$SKIP_WEBSERVER" = true ]; then
        info "Photobooth installation" "Skipping Apache Webserver setup."
        sleep 2
    else
        check_webserver
        case $? in
            1)
                info "Webserver" "Apache2 is installed and running."
                setup_apache=true
                ;;
            2)
                confirm "Webserver" "Nginx is installed and running. Please configure your Webserver manually if needed."
                setup_apache=false
                ;;
            3)
                confirm "Webserver" "Lighttpd is installed and running. Please configure your Webserver manually if needed."
                setup_apache=false
                ;;
            4)
                if [ "$SILENT" = false ]; then
                    if ! whiptail --title "Webserver" \
                        --yesno "One or more webservers are installed but not running. Continue installing Apache webserver?" \
                        12 60; then
                        return 1
                    fi
                 fi
                info "Webserver" "One or more webservers are installed but not running. Continuing."
                setup_apache=true
                ;;
            0)
                info "Webserver" "No webserver detected."
                setup_apache=true
                ;;
            *)
                confirm "Webserver" "Unexpected result while checking web server."
                return 1
            ;;
        esac

        if [[ "$setup_apache" = true ]]; then
            apache_webserver
            case $? in
                0)
                    info "Photobooth installation" "Apache Webserver installed and running successfully."
                    ;;
                1)
                    confirm "Photobooth installation" "Failed to install Apache Webserver packages. Further actions are halted. Check logs for details."
                    return 1
                    ;;
                2)
                    confirm "Photobooth installation" "Apache service could not be enabled or started. Ignoring..."
                    ;;
                *)
                    confirm "Photobooth installation" "An unknown error occurred during Apache Webserver installation."
                    return 1
                    ;;
            esac
            if ! set_php_version_apache "$PHP_VERSION"; then
                confirm "Apache Webserver" "Failed to setup PHP for $PHP_VERSION. Ignoring..."
            fi
        fi
    fi

    if install_packages "${COMMON_PACKAGES[@]}"; then
        info "Photobooth installation" "All common packages installed successfully."
    else
        confirm "Package installation" "Installation process stopped due to an error."
        return 1
    fi

   if [ "$SKIP_NODE" = true ]; then
        info "Photobooth installation" "Skipping Node.js and npm setup."
        sleep 2
    else
        check_nodejs
        case $? in
            0)
                info "Photobooth installation" "Node.js is ready for use."
                ;;
            1|3)
                # For both update or downgrade required cases, update Node.js
                if ! update_nodejs; then
                    confirm "Node.js" "Failed to update/downgrade Node.js. Further actions are halted."
                    exit_code=1
                fi
                ;;
            2)
                info "Node.js" "Node.js is not installed. Installing..."
                if ! update_nodejs; then
                    confirm "Node.js" "Failed to install Node.js. Further actions are halted."
                    exit_code=1
                fi
                ;;
            *)
                confirm "Node.js" "An unknown error occurred while checking Node.js. Further actions are halted."
                exit_code=1
                ;;
        esac

        if [[ $exit_code -eq 1 ]]; then
            confirm "Photobooth installation" "Stopping the script due to errors in Node.js handling."
            return 1
        fi
        info "Photobooth installation" "Node.js setup completed successfully."

        if check_npm; then
            info "Photobooth installation" "npm is ready."
        else
            warn "npm check failed. Proceeding with caution."
        fi
    fi

   if [ "$SKIP_PYTHON" = true ]; then
        info "Photobooth installation" "Skipping Python3 setup."
        sleep 2
    else
        check_python
        case $? in
            0)
                info "Photobooth installation" "Python environment ready for installation."
                ;;
            2)
                error "Python3 not installed. Please install it manually."
                ;;
            3)
                error "Python version detection failed. Continuing installation might cause issues."
                ;;
        esac
    fi

    chown www-data:www-data /var/www

    if [ "$update" = true ]; then
        commit_git_changes
        case $? in
            0)
                info "Git changes" "Commit process completed successfully."
                ;;
            1)
                confirm "Git changes" "Failed to access installation directory. Check directory permissions."
                return 1
                ;;
            2)
                confirm "Git changes" "Submodule fixing failed. Review submodule configuration."
                return 1
                ;;
            3)
                confirm "Git changes" "Failed to commit changes."
                return 1
                ;;
            4)
                confirm "Git changes" "Uncommitted changes detected. Update aborted by user."
                return 1
                ;;
            5)
                confirm "Git changes" "Failed to create backup branch."
                return 1
                ;;
            *)
                confirm "Git changes" "An unexpected error occurred during the commit process."
                return 1
                ;;
        esac
    else
        if [ -d "$INSTALLFOLDERPATH" ]; then
            info "Photobooth installation" "${INSTALLFOLDERPATH} found. Creating backup as ${BACKUPFOLDER}."
            if ! mv "$INSTALLFOLDERPATH" "/var/www/$BACKUPFOLDER"; then
                confirm "Backup" "Failed to create backup at /var/www/${BACKUPFOLDER}!"
                return 1
            fi
        else
            info "Photobooth installation" "$INSTALLFOLDERPATH not found. Proceeding with a fresh installation."
        fi

        if ! do_git_clone; then
            return 1
        fi
    fi

    start_git_install
    case $? in
        0)
            info "Photobooth installation" "Photobooth installation completed successfully."
            ;;
        1)
            confirm "Installation Error" "General failure during installation. Check logs for details."
            return 1
            ;;
        2)
            confirm "Git Error" "Git operations failed. Verify repository access and branch details."
            return 1
            ;;
        3)
            confirm "npm Error" "npm installation or build failed. Ensure npm is correctly configured."
            return 1
            ;;
        *)
            confirm "Unexpected Error" "An unexpected error occurred. Exit code: $RESULT"
            return 1
            ;;
    esac

    general_permissions

    if [ "$update" = true ]; then
        fix_git_modules
        case $? in
            0)
                info "GitHub submodules" "Submodules fixed successfully."
                ;;
            1)
                confirm "GitHub submodules" "Failed to access the installation folder. Check directory permissions."
                return 1
                ;;
            2)
                confirm "GitHub submodules" "Failed to reset submodules. Ensure the Git repository is intact."
                return 1
                ;;
            3)
                confirm "GitHub submodules" "Failed to deinitialize submodules. Check Git configuration."
                return 1
                ;;
            4)
                confirm "GitHub submodules" "Failed to update submodules. Verify Git submodule setup."
                return 1
                ;;
            *)
                confirm "GitHub submodules" "An unexpected error occurred while fixing submodules."
                return 1
                ;;
        esac

        confirm "Photobooth Update" "Update done!"
    else
        confirm "Photobooth installation" "Installation done!"
    fi

    return 0
}

# ==================================================
# Menu
# ==================================================
function configure_mouse() {
    while true; do
        if ! CHOICE=$(whiptail --title "Mouse Configuration" \
            --menu "Choose an option:" 15 60 3 --cancel-button Back --ok-button Select \
            "1" "Hide Mouse Cursor" \
            "2" "Restore Mouse Cursor" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                hide_mouse
                ;;
            2)
                restore_mouse
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac
    done
}

function printer_setup() {
    while true; do
       MENU_OPTIONS=("1" "Enable printing from any address")
       MENU_OPTIONS+=("2" "Disable printing from any address")
       MENU_OPTIONS+=("3" "Enable printer sharing")
       MENU_OPTIONS+=("4" "Disable printer sharing")
       MENU_OPTIONS+=("5" "Enable remote administration")
       MENU_OPTIONS+=("6" "Disable remote administration")
       MENU_OPTIONS+=("7" "Grant Photobooth permissions for print")
       MENU_OPTIONS+=("8" "Remove Photobooth permissions for print")

        if ! CHOICE=$(whiptail --title "Photobooth Printer Setup" \
            --menu "Choose an option:" 20 60 10 \
            --cancel-button Back --ok-button Select \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                cups_enable_remote_any
                case $? in
                   0)
                       confirm "Printer Setup" "Enabled printing from any address with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to enable printing from any address."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during setup to print from any address."
                       ;;
               esac
               ;;
            2)
                cups_disable_remote_any
                case $? in
                   0)
                       confirm "Printer Setup" "Disabled printing from any address with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to disable printing from any address."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during setup to disable print from any address."
                       ;;
               esac
               ;;
            3)
                cups_enable_share
                case $? in
                   0)
                       confirm "Printer Setup" "Enabled printer sharing with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to enable printer sharing."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during printer sharing."
                       ;;
               esac
               ;;
            4)
                cups_disable_share
                case $? in
                   0)
                       confirm "Printer Setup" "Disabled printer sharing with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to disable printer sharing."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during disabling printer sharing."
                       ;;
               esac
               ;;
            5)
                cups_enable_remote_admin
                case $? in
                   0)
                       confirm "Printer Setup" "Enabled remote administration with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to enable remote administration."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during remote administration setup."
                       ;;
               esac
               ;;
            6)
                cups_disable_remote_admin
                case $? in
                   0)
                       confirm "Printer Setup" "Disabled remote administration with success."
                       ;;
                   1)
                       confirm "Printer Setup" "Failed to disable remote administration."
                       ;;
                   2)
                       confirm "Printer Setup" "Failed to restart the CUPS service."
                       ;;
                   *)
                       confirm "Printer Setup" "An unknown error occurred during setup to disable remote administration."
                       ;;
               esac
               ;;
            7)
                setup_printer_groups
                case $? in
                    0)
                        confirm "Printer Setup" "Printer groups setup completed successfully."
                        ;;
                    1)
                        confirm "Printer Setup" "Failed to add www-data to lp group."
                        ;;
                    2)
                        confirm "Printer Setup" "Failed to add www-data to lpadmin group."
                        ;;
                   *)
                        confirm "Printer Setup" "An unknown error occurred during group setup."
                        ;;
                esac
                ;;
            8)
                remove_printer_groups
                case $? in
                    0)
                        confirm "Printer Setup" "Printer groups removed without errors."
                        ;;
                    1)
                        confirm "Printer Setup" "Error: Failed to remove www-data from lp group."
                        ;;
                    2)
                        confirm "Printer Setup" "Error: Failed to remove www-data from lpadmin group."
                        ;;
                    *)
                        confirm "Printer Setup" "Unknown error occurred during printer group removal."
                        ;;
                esac
                ;;
            *)
                # Handle invalid input
                confirm "Error" "Invalid option. Please try again."
                ;;
        esac
    done
}

function manage_permissions() {
    local SUDOERS_FILE="/etc/sudoers.d/020_www-data-shutdown"

    if ! photobooth_installed; then
        confirm "Photobooth detection" "Photobooth not found. Some options won't be available."
    fi

    while true; do
        if [ -f "$SUDOERS_FILE" ]; then
            MENU_OPTIONS=("1" "Remove reboot/shutdown permissions")
        else
            MENU_OPTIONS=("1" "Grant reboot/shutdown permissions")
        fi
        if [ "$PHOTOBOOTH_FOUND" = true ]; then
            MENU_OPTIONS+=("2" "Fix general permissions")
            MENU_OPTIONS+=("3" "Adjust PHP Upload limit to 20 MB")
            MENU_OPTIONS+=("4" "USB Sync policy")
            if [ "$RUNNING_ON_PI" = true ]; then
               MENU_OPTIONS+=("5" "Add GPIO permission for www-data")
               MENU_OPTIONS+=("6" "Remove GPIO permission for Remotebuzzer Server")
            fi
        fi

        if ! CHOICE=$(whiptail --title "Photobooth Permissions" \
            --menu "Choose an option:" 20 60 10 \
            --cancel-button Back --ok-button Select \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                if [ -f "$SUDOERS_FILE" ]; then
                    # Remove permissions if the file exists
                    info "Reboot/shutdown" "Removing reboot/shutdown permissions..."
                    if rm -f "$SUDOERS_FILE"; then
                        confirm "Reboot/shutdown" "Reboot/shutdown permissions have been removed successfully."
                    else
                        confirm "Reboot/shutdown" "Failed to remove permissions."
                    fi
                else
                    info "Reboot/shutdown" "Granting reboot/shutdown permissions..."
                    if install_wwwdata_sudoers; then
                        confirm "Reboot/shutdown" "Reboot/shutdown permissions have been granted successfully."
                    else
                        confirm "Reboot/shutdown" "Failed to grant permissions."
                    fi
                fi
                ;;
            2)
                general_permissions
                confirm "General permissions" "Permission adjustments complete."
                ;;
            3)
                update_php_ini "/etc/php/$PHP_VERSION/apache2/php.ini"
                case $? in
                    0)
                        confirm "PHP INI Update" "PHP INI updated successfully."
                        ;;
                    1)
                        confirm "PHP INI Update" "Error: No PHP INI file path provided."
                        ;;
                    2)
                        confirm "PHP INI Update" "Error: PHP INI file does not exist."
                        ;;
                    3)
                        confirm "PHP INI Update" "Error: Failed to create a backup of the PHP INI file."
                        ;;
                    4)
                        confirm "PHP INI Update" "Error: Failed to update upload_max_filesize."
                        ;;
                    5)
                        confirm "PHP INI Update" "Error: Failed to update post_max_size."
                        ;;
                    6)
                        confirm "PHP INI Update" "Error: Failed to restart Apache2 Webserver."
                        ;;
                    *)
                        confirm "PHP INI Update" "An unknown error occurred."
                        ;;
                esac
                ;;
            4)
                set_usb_sync
                ;;
            5)
                gpio_permission
                confirm "GPIO permissions" "GPIO permission adjustments complete. Please reboot your device to take effect."
                ;;
            6)
                remove_gpio_permission
                confirm "GPIO permissions" "GPIO permission removed. Please reboot your device to take effect."
                ;;
            *)
                # Handle invalid input
                confirm "Error" "Invalid option. Please try again."
                ;;
        esac
    done
}

function configure_shortcuts() {
    local MENU_OPTIONS=()
    local autostart_shortcut="/etc/xdg/autostart/photobooth.desktop"
    local desktop_shortcut=""

    if [ -z "$USERNAME" ]; then
        local detected_user
        detected_user=$(detect_single_home_user)
        if [[ -n "$detected_user" ]]; then
            USERNAME="$detected_user"
        fi
    fi

    if [ -n "$USERNAME" ]; then
        desktop_shortcut="/home/$USERNAME/Desktop/photobooth.desktop"
    fi

    photobooth_installed

    detect_browser

    # Ensure either $desktop_shortcut or $autostart_shortcut exists
    if [ -z "$desktop_shortcut" ] && [ ! -f "$autostart_shortcut" ]; then
        # Check if the browser is unknown
        if [ "$WEBBROWSER" = "unknown" ]; then
            confirm "Photobooth Configuration" "No browser detected. Browser configuration cannot proceed."
            return
        fi

        # Check if Photobooth is not found
        if [ "$PHOTOBOOTH_FOUND" = false ]; then
            confirm "Photobooth Configuration" "No Photobooth installation detected. Browser configuration cannot proceed."
            return
        fi
    fi

    while true; do
        if [ -n "$desktop_shortcut" ]; then
            if [ -f "$desktop_shortcut" ]; then
                MENU_OPTIONS=(
                    "1" "Remove Photobooth Desktop Shortcut"
                )
            elif [ "$WEBBROWSER" != "unknown" ] && [ "$PHOTOBOOTH_FOUND" = true ]; then
                MENU_OPTIONS=(
                    "1" "Create Photobooth Desktop Shortcut ($WEBBROWSER)"
                )
            fi
        fi

        if [ -f "$autostart_shortcut" ]; then
            MENU_OPTIONS+=(
                "2" "Disable Browser Autostart"
            )
        elif ! is_wayland_env && [ "$WEBBROWSER" != "unknown" ] && [ "$PHOTOBOOTH_FOUND" = true ]; then
            MENU_OPTIONS+=(
                "2" "Enable Autostart in Kiosk Mode ($WEBBROWSER)"
            )
        fi

        if ! CHOICE=$(whiptail --title "Photobooth Configuration" \
             --menu "Choose an option:" 15 60 4 \
             --cancel-button Back --ok-button Select \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                if [ -f "$desktop_shortcut" ]; then
                    if rm -f "$desktop_shortcut"; then
                        confirm "Desktop Shortcut" "Desktop Shortcut removed successfully."
                    else
                        confirm "Desktop Shortcut" "Failed to remove Desktop Shortcut!"
                    fi
                else
                    browser_desktop_shortcut "$desktop_shortcut" "$USERNAME"
                fi
                ;;
            2)
                if [ -f "$autostart_shortcut" ]; then
                    if rm -f "$autostart_shortcut"; then
                        confirm "Autostart Disabled" "Browser autostart in kiosk mode has been disabled."
                    else
                        confirm "Autostart Disabled" "Failed to disable browser autostart in kiosk mode!"
                    fi
                elif ! is_wayland_env; then
                    if browser_autostart; then
                        confirm "Autostart Enabled" "Browser autostart in kiosk mode has been enabled."
                    else
                        confirm "Failed to enable Autostart" "Browser autostart in kiosk mode could not be created."
                    fi
                fi
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac
    done
}

function go2rtc_setup() {
    local codec_format="--codec mjpeg"
    local test_cmd=''
    local backend=''

    while true; do
        MENU_OPTIONS=(
            "1" "Install go2rtc and service for gphoto2" \
            "2" "Install go2rtc and service for rpicam-apps" \
            "3" "Install go2rtc and service for libcamera-apps" \
            "4" "Install go2rtc and service for fswebcam" \
            "5" "Update or downgrade go2rtc only" \
            "6" "Uninstall go2rtc and the related services" \
        )

        if ! CHOICE=$(whiptail --title "go2rtc setup" \
            --menu "Choose an option:" 20 60 10 \
            --ok-button Select --cancel-button Back \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                info "go2rtc installation" "Installing service to set up a MJPEG stream for gphoto2."
                test_cmd="gphoto2 --capture-movie=5s"
                backend="gphoto2"
                ;;
            2)
                info "go2rtc installation" "Installing service to set up a MJPEG stream for rpicam-apps."
                test_cmd="rpicam-vid -t 5000 $codec_format -o test.mjpeg"
                backend="rpicam"
                ;;
            3)
                info "go2rtc installation" "Installing service to set up a MJPEG stream for libcamera-apps."
                test_cmd="libcamera-vid -t 5000 $codec_format -o test.mjpeg"
                backend="libcamera"
                ;;
            4)
                info "go2rtc installation" "Installing service to set up a MJPEG stream for fswebcam."
                if ! install_packages "${GO2RTC_EXTRA_PACKAGES[@]}"; then
                    confirm "go2rtc installation" "Dependency installation failed. Stopping setup."
                    continue
                fi
                test_cmd="fswebcam -d /dev/video0 -r 1280x720 test.jpg"
                backend="fswebcam"
                ;;
            5)
                info "go2rtc installation" "Updating or downgrading go2rtc."
                GO2RTC_UPDATE_ONLY=true
                ;;
            6)
                info "go2rtc" "Uninstalling go2rtc and related services."
                uninstall_go2rtc
                confirm "go2rtc" "go2rtc and configuration uninstalled."
                continue
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac

        if [[ -n "$test_cmd" ]]; then
            if ! test_command "$test_cmd"; then
                if ! whiptail --title "Command Test Failed" \
                    --yesno "The preview generation test failed:\n\n$test_cmd\n\nDo you want to continue anyway?" \
                    12 70; then
                    test_cmd=''
                    confirm "go2rtc installation" "Test failed. Stopping setup."
                    continue
                fi
            fi
            test_cmd=''
        fi

        # Attempt to install go2rtc and handle errors
        if ! ask_go2rtc_version; then
            confirm "go2rtc" "No version selected. Setup aborted."
            GO2RTC_UPDATE_ONLY=false
            continue
        fi
        remove_gphoto_webcam
        install_go2rtc "$LOCAL_ARCH" "$GO2RTC_VERSION" "$GO2RTC_UPDATE_ONLY"
        case $? in
            0)
                if [ "$GO2RTC_UPDATE_ONLY" = false ]; then
                    go2rtc_config "$backend" "$codec_format"
                fi
                sed -i 's/--libav-format h264/--codec mjpeg/g' /etc/go2rtc.yaml
                confirm "go2rtc installation" "Installation completed successfully."
                ;;
            1)
                confirm "go2rtc installation" "go2rtc not installed. Cannot update!"
                ;;
            2)
                confirm "go2rtc installation" "Unsupported OS detected. Installation failed."
                ;;
            3)
                confirm "go2rtc installation" "Unsupported architecture detected. Installation failed."
                ;;
            4)
                confirm "go2rtc installation" "Failed to download or extract go2rtc binary."
                ;;
            5)
                confirm "go2rtc installation" "Failed to start go2rtc service."
                ;;
            *)
                confirm "go2rtc installation" "An unknown error occurred during installation."
                ;;
        esac

        GO2RTC_UPDATE_ONLY=false
    done
}

function gphoto2_webcam_setup() {
    while true; do
        if [ "$PHOTOBOOTH_FOUND" = true ]; then
            MENU_OPTIONS=(
                "1" "Install gphoto2 webcam with service" \
                "2" "Install gphoto2 webcam with cronjob" \
            )
        fi

        MENU_OPTIONS+=(
            "3" "Uninstall gphoto2 webcam" \
        )

        if ! CHOICE=$(whiptail --title "gphoto2 webcam setup" \
            --menu "Choose an option:" 20 60 10 \
            --ok-button Select --cancel-button Back \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                info "go2rtc" "Uninstalling go2rtc and related services if installed."
                uninstall_go2rtc
                info "go2rtc" "go2rtc and configuration uninstalled."
                remove_gphoto_webcam
                info "gphoto2 webcam" "Installing gphoto2 webcam and needed service."
                if ! install_packages "${GPHOTO2_WEBCAM_EXTRA_PACKAGES[@]}"; then
                    confirm "gphoto2 webcam" "Dependency installation failed. Stopping setup."
                    continue
                fi
                setup_gphoto_webcam
                case $? in
                    0)
                        info "gphoto2 webcam" "GPhoto2 preview set up successfully."
                        ;;
                    1)
                        confirm "gphoto2 webcam" "Failed to create necessary directories."
                        continue
                        ;;
                    2)
                        confirm "gphoto2 webcam" "Failed to write to modules-load.d."
                        continue
                        ;;
                    3)
                        confirm "gphoto2 webcam" "Failed to write to modprobe.d."
                        continue
                        ;;
                    4)
                        confirm "gphoto2 webcam" "Failed to load the kernel module."
                        continue
                        ;;
                    5)
                        confirm "gphoto2 webcam" "Failed to remove bcm2835-isp module. It might still be in use."
                        continue
                        ;;
                esac

                create_ffmpeg_webcam_service
                case $? in
                    0)
                        info "FFmpeg webcam service" "FFmpeg webcam service created successfully."
                        ;;
                    1)
                        confirm "FFmpeg webcam service" "Failed to create the FFmpeg webcam service file."
                        continue
                        ;;
                    2)
                        confirm "FFmpeg webcam service" "Failed to reload the systemd daemon."
                        continue
                        ;;
                    3)
                        confirm "FFmpeg webcam service" "Failed to enable/start the FFmpeg webcam service."
                        continue
                        ;;
                    4)
                        confirm "FFmpeg webcam service" "Failed to create symlink for FFmpeg webcam service (non-systemd)."
                        continue
                        ;;
                esac

                confirm "gphoto2 webcam" "Installed gphoto2 webcam and needed FFmpeg webcam service successfully. Please reboot to take effect."
                ;;
            2)
                info "go2rtc" "Uninstalling go2rtc and related services if installed."
                uninstall_go2rtc
                info "go2rtc" "go2rtc and configuration uninstalled."
                remove_gphoto_webcam
                info "gphoto2 webcam" "Installing gphoto2 webcam and needed cronjob."
                if ! install_packages "${GPHOTO2_WEBCAM_EXTRA_PACKAGES[@]}"; then
                    confirm "gphoto2 webcam" "Dependency installation failed. Stopping setup."
                    continue
                fi
                setup_gphoto_webcam
                case $? in
                    0)
                        info "gphoto2 webcam" "GPhoto2 preview set up successfully."
                        ;;
                    1)
                        confirm "gphoto2 webcam" "Failed to create necessary directories."
                        continue
                        ;;
                    2)
                        confirm "gphoto2 webcam" "Failed to write to modules-load.d."
                        continue
                        ;;
                    3)
                        confirm "gphoto2 webcam" "Failed to write to modprobe.d."
                        continue
                        ;;
                    4)
                        confirm "gphoto2 webcam" "Failed to load the kernel module."
                        continue
                        ;;
                    5)
                        confirm "gphoto2 webcam" "Failed to remove bcm2835-isp module. It might still be in use."
                        continue
                        ;;
                esac

                if add_cameracontrol_cronjob; then
                    info "Cron job" "Cron job setup successfully."
                    sleep 2
                    confirm "gphoto2 webcam" "Installed gphoto2 webcam and needed cronjob successfully. Please reboot to take effect."
                else
                    confirm "Cron job" "Failed to set up the cron job."
                fi
                ;;
            3)
                info "gphoto2 webcam" "Uninstalling gphoto2 webcam and related services / cronjob if installed."
                if remove_gphoto_webcam; then
                    confirm "gphoto2 webcam" "gphoto2 webcam and related services / cronjob uninstalled successfully."
                else
                    confirm "gphoto2 webcam" "Failed to completely remove gphoto2 webcam setup. Some components may still remain."
                fi
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac
    done
}

function show_install_configuration() {
    while true; do
        MENU_OPTIONS=(
            "1" "Set Git Branch (current: $BRANCH)" \
            "2" "Set PHP Version (current: $PHP_VERSION)" \
            "3" "Skip Webserver setup (current: $SKIP_WEBSERVER)" \
            "4" "Skip PHP setup (current: $SKIP_PHP)" \
            "5" "Skip Node.js and npm setup (current: $SKIP_NODE)" \
            "6" "Skip Python3 setup (current: $SKIP_PYTHON)" \
        )

        if ! CHOICE=$(whiptail --title "Installation configuration" \
            --menu "Choose an option to configure:" 20 60 10 \
            --ok-button Select --cancel-button Back \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                set_branch
                ;;
            2)
                set_php_version
                ;;
            3)
                toggle_skip_webserver
                ;;
            4)
                toggle_skip_php
                ;;
            5)
                toggle_skip_node
                ;;
            6)
                toggle_skip_python
                ;;
            *)
                info "Installation configuration" "Invalid choice, please try again."
                ;;
        esac
    done
}

function misc_menu() {
    while true; do
        MENU_OPTIONS=(
            "1" "Autostart and shortcut"
            "2" "Mouse cursor"
            "3" "Printer Setup"
        )

        if ! CHOICE=$(whiptail --title "Photobooth Misc" \
            --menu "Choose an option:" 20 60 10 \
            --cancel-button Back --ok-button Select \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            break
        fi

        case $CHOICE in
            1)
                configure_shortcuts
                ;;
            2)
                configure_mouse
                ;;
            3)
                if dpkg-query -W -f='${Status}' "cups" 2>/dev/null | grep -q "ok installed"; then
                    printer_setup
                else
                    confirm "Printer Setup" "CUPS must be installed first."
                fi
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac
    done
}

# ==================================================
# Rembg Setup Menu
# ==================================================
function rembg_setup_menu() {
    while true; do
        local choice

        choice=$(whiptail --title "Rembg Setup" \
            --menu "Choose an option:" 20 60 10 \
            --ok-button Select --cancel-button Back \
            "1" "Install rembg (background removal)" \
            "2" "Remove rembg" \
            3>&1 1>&2 2>&3)

        local status=$?
        [[ $status -ne 0 ]] && return 0

        case "$choice" in
            1)
                if ! rembg_install; then
                    confirm "Rembg" "Installation failed. Check logs and try again."
                fi
                ;;
            2)
                if ! rembg_remove; then
                    confirm "Rembg" "Removal failed."
                fi
                ;;
        esac
    done
}

function start_page() {
    if photobooth_installed; then
        check_git_install
    fi

    install_system_icon || warn "Failed to install Photobooth system icon"

    while true; do
        MENU_OPTIONS=(
            "1" "Installation configuration"
        )

        if [ "$PHOTOBOOTH_FOUND" = false ]; then
            MENU_OPTIONS+=(
                "2" "Start Installation"
            )
        else
            MENU_OPTIONS+=(
                "2" "Update Photobooth"
            )
        fi

        MENU_OPTIONS+=(
            "3" "Photobooth version"
            "4" "go2rtc"
            "5" "gphoto2 webcam"
            "6" "Permissions"
            "7" "Rembg Setup"
            "8" "Misc"
        )

        if ! CHOICE=$(whiptail --title "Photobooth Setup Wizard" \
            --menu "Choose an option:" 20 60 10 \
            --cancel-button Exit --ok-button Select \
            "${MENU_OPTIONS[@]}" 3>&1 1>&2 2>&3); then
            if whiptail --title "Exit Setup" \
                --yesno "Are you sure you want to exit?" \
                8 50; then
                exit 0
            else
                continue
            fi
        fi

        case $CHOICE in
            1)
                show_install_configuration
                ;;
            2)
                if [ "$PHOTOBOOTH_FOUND" = false ]; then
                    install_or_update_photobooth false
                else
                    if [ "$GIT_INSTALLED" = true ]; then
                        install_or_update_photobooth "$GIT_INSTALLED"
                    else
                        confirm "Photobooth Update" "Can not update Photobooth. Photobooth must be installed via git."
                    fi
                fi

                if photobooth_installed; then
                    check_git_install
                fi
                check_photobooth_version
                ;;
            3)
                check_photobooth_version
                ;;
            4)
                go2rtc_setup
                ;;
            5)
                gphoto2_webcam_setup
                ;;
            6)
                manage_permissions
                ;;
            7)
                rembg_setup_menu
                ;;
            8)
                misc_menu
                ;;
            *)
                confirm "Invalid Option" "Please select a valid option."
                ;;
        esac
    done
}

# ==================================================
# Argument parsing
# ==================================================

if [ "$UID" != 0 ]; then
    error "Only root is allowed to execute the Photobooth Setup Wizard.\n\nForgot sudo?"
    exit 1
fi

log "############ Photobooth Setup Wizard started"

# Parse args
for arg in "$@"; do
    case $arg in
        --help)
            show_help
            ;;
        --silent)
            SILENT=true
            SKIP_AUTO_UPDATE=true
            ;;
        --branch=*)
            BRANCH="${arg#*=}"
            ;;
        --php=*)
            PHP_VERSION="${arg#*=}"
            info "Setup" "PHP set to v$PHP_VERSION"
            ;;
        --raspberry)
            FORCE_RASPBERRY_PI=true
            RUNNING_ON_PI=true
            ;;
        --username=*)
            USERNAME="${arg#*=}"
            ;;
        --update)
            UPDATE=true
            ;;
        --skip-webserver)
            SKIP_WEBSERVER=true
            ;;
        --skip-php)
            SKIP_PHP=true
            ;;
        --skip-node)
            SKIP_NODE=true
            ;;
        --skip-python)
            SKIP_PYTHON=true
            ;;
        --skip-auto-update)
            SKIP_AUTO_UPDATE=true
            ;;
        --wayland)
            WAYLAND_ENV=true
            ;;
        *)
            info "Photobooth Setup Wizard" "Unknown option: $arg"
            confirm "Usage"  "Execute $0 --help for a list of available options."
            ;;
    esac
done

if [ "$SILENT" = true ]; then
    info "Photobooth Setup Wizard" "Running Photobooth Setup Wizard in silent mode."
else
    info "Photobooth Setup Wizard" "Running Photobooth Setup Wizard in interactive mode. Use --silent for automated options."
    if [ "$UPDATE" = true ]; then
        confirm "Warning"  "--update can only be used in silent mode.\n\nExecute $0 --help for a list of available options."
        UPDATE=false
    fi
fi

print_logo

if [ "$SKIP_AUTO_UPDATE" = true ]; then
    info "Photobooth Setup Wizard" "Skipping update of Photobooth Setup Wizard."
    sleep 2
else
    self_update "$@"
fi

if detected_os=$(detect_os_codename) && [[ $detected_os ]]; then
    OS_CODENAME="$detected_os"
    info "OS Detection" "Detected distribution codename: $OS_CODENAME"
else
    confirm "Warning" "Could not detect OS codename."
fi

detect_pi
check_username

if [ "$SILENT" = true ]; then
    if photobooth_installed; then
        check_git_install
    fi

    if [ "$PHOTOBOOTH_FOUND" = false ]; then
        if [ "$UPDATE" = true ]; then
            confirm "Photobooth Update" "Can not update Photobooth. Photobooth must be installed via git."
            exit 1
        fi
    else
        if [ "$UPDATE" = false ]; then
            confirm "Photobooth Installation" "Photobooth installed already. Trying to update Photobooth."
            UPDATE=true
        fi

        if [ "$GIT_INSTALLED" = false ]; then
            confirm "Photobooth Update" "Can not update Photobooth. Photobooth must be installed via git."
            exit 1
        fi
    fi

    install_or_update_photobooth "$UPDATE"
    photobooth_installed
    check_photobooth_version
    install_system_icon || warn "Failed to install Photobooth system icon"
    exit
else
    # Non-silent mode → whiptail only
    # Preserve terminal for interactive menus
    exec 3>&1 4>&2
fi

detect_browser
start_page
