#!/bin/bash

# Script to install rembg and dependencies in a virtual environment
# This script should be run before enabling rembg in the Photobooth menu
# Compatible with Raspberry Pi OS, Debian, Ubuntu (x64 and ARM)

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Status functions
echo_info() {
    echo -e "${BLUE} $1${NC}"
}

echo_success() {
    echo -e "${GREEN} $1${NC}"
}

echo_warning() {
    echo -e "${YELLOW} 1${NC}"
}

echo_error() {
    echo -e "${RED} $1${NC}"
}

echo_step() {
    echo -e "${PURPLE} $1${NC}"
}

echo_header() {
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN} $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Spinner function for long operations
spinner() {
    local pid=$1
    local delay=0.1
    local spinstr='|/-\'
    while [ "$(ps a | awk '{print $1}' | grep $pid)" ]; do
        local temp=${spinstr#?}
        printf " [%c]  " "$spinstr"
        local spinstr=$temp${spinstr%"$temp"}
        sleep $delay
        printf "\b\b\b\b\b\b"
    done
    printf "    \b\b\b\b"
}

echo_header "Installing rembg for Photobooth"
echo_info "This script is compatible with Raspberry Pi OS, Debian, and Ubuntu"
echo ""

# Function to check if running as root or with sudo
check_sudo() {
    echo_step "Checking permissions..."
    if [ "$EUID" -eq 0 ]; then
        echo_success "Running as root"
        SUDO=""
    elif command -v sudo &> /dev/null && sudo -n true 2>/dev/null; then
        echo_success "sudo available and configured"
        SUDO="sudo"
    else
        echo_warning "Not running as root and sudo not available or not configured"
        echo_warning "Some installation steps may fail. Please run as root or configure sudo"
        SUDO="sudo"
    fi
}

# Function to install packages
install_package() {
    local package=$1
    if command -v apt &> /dev/null; then
        echo_step "Installing $package using apt..."
        echo_info "Updating package list..."
        $SUDO apt update > /dev/null 2>&1 &
        spinner $!
        echo_success "Package list updated"
        $SUDO apt install -y $package > /dev/null 2>&1 &
        spinner $!
        echo_success "$package installed successfully"
    elif command -v yum &> /dev/null; then
        echo_step "Installing $package using yum..."
        $SUDO yum install -y $package > /dev/null 2>&1 &
        spinner $!
        echo_success "$package installed successfully"
    elif command -v dnf &> /dev/null; then
        echo_step "Installing $package using dnf..."
        $SUDO dnf install -y $package > /dev/null 2>&1 &
        spinner $!
        echo_success "$package installed successfully"
    else
        echo_error "No supported package manager found (apt, yum, dnf)"
        echo_error "Please install $package manually"
        return 1
    fi
}

# Check sudo/root
check_sudo

# Check if Python 3 is installed
echo_step "Checking Python 3 installation..."
if ! command -v python3 &> /dev/null; then
    echo_warning "Python 3 not found. Installing python3..."
    install_package python3
fi

echo_success "Python 3 found: $(python3 --version)"

# Check if python3-venv is available
echo_step "Checking python3-venv availability..."
if ! python3 -m venv --help &> /dev/null; then
    echo_warning "python3-venv not available. Installing python3-venv..."
    install_package python3-venv
fi
echo_success "python3-venv is available"

# Check if pip is available
echo_step "Checking pip availability..."
if ! command -v pip3 &> /dev/null && ! python3 -m pip --version &> /dev/null; then
    echo_warning "pip not available. Installing python3-pip..."
    install_package python3-pip
fi
echo_success "pip is available"

# Define paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/rembg_venv"

# Create virtual environment if it doesn't exist
echo_step "Setting up virtual environment..."
if [ ! -d "$VENV_DIR" ]; then
    echo_info "Creating virtual environment in $VENV_DIR..."
    python3 -m venv "$VENV_DIR" &
    spinner $!
    echo_success "Virtual environment created"
else
    echo_info "Virtual environment already exists in $VENV_DIR"
fi

# Activate virtual environment
echo_step "Activating virtual environment..."
source "$VENV_DIR/bin/activate"
echo_success "Virtual environment activated"

# Upgrade pip
echo_step "Upgrading pip..."
python3 -m pip install --upgrade pip > /dev/null 2>&1 &
spinner $!
echo_success "pip upgraded to latest version"

# Install rembg and dependencies
echo_step "Installing rembg and dependencies..."
echo_info "This may take a few minutes, especially on Raspberry Pi..."
python3 -m pip install rembg pillow onnxruntime &
spinner $!
echo_success "rembg and dependencies installed"

# Verify installation
echo_step "Verifying installation..."
python3 -c "import rembg; print('rembg version:', rembg.__version__)" 2>/dev/null && echo_success "rembg: $(python3 -c "import rembg; print(rembg.__version__)")" || echo_error "rembg verification failed"
python3 -c "import PIL; print('PIL version:', PIL.__version__)" 2>/dev/null && echo_success "PIL: $(python3 -c "import PIL; print(PIL.__version__)")" || echo_error "PIL verification failed"
python3 -c "import onnxruntime; print('onnxruntime version:', onnxruntime.__version__)" 2>/dev/null && echo_success "onnxruntime: $(python3 -c "import onnxruntime; print(onnxruntime.__version__)")" || echo_error "onnxruntime verification failed"

echo ""
echo_header "Installation completed successfully!"
echo_info "Virtual environment: $VENV_DIR"
echo_info "To activate manually: source $VENV_DIR/bin/activate"
echo ""
echo_info "Note: Make sure the rembg_processor.py script uses this virtual environment."
echo_success "You can now enable rembg in the Photobooth admin panel."
