#!/bin/bash

# Script to install rembg and dependencies in a virtual environment
# This script should be run before enabling rembg in the Photobooth menu
# Compatible with Raspberry Pi OS, Debian, Ubuntu (x64 and ARM)

set -e  # Exit on any error

echo "Installing rembg for Photobooth..."
echo "This script is compatible with Raspberry Pi OS, Debian, and Ubuntu"
echo ""

# Function to check if running as root or with sudo
check_sudo() {
    if [ "$EUID" -eq 0 ]; then
        echo "Running as root"
        SUDO=""
    elif command -v sudo &> /dev/null && sudo -n true 2>/dev/null; then
        echo "sudo available and configured"
        SUDO="sudo"
    else
        echo "Warning: Not running as root and sudo not available or not configured."
        echo "Some installation steps may fail. Please run as root or configure sudo."
        SUDO="sudo"
    fi
}

# Function to install packages
install_package() {
    local package=$1
    if command -v apt &> /dev/null; then
        echo "Installing $package using apt..."
        $SUDO apt update
        $SUDO apt install -y $package
    elif command -v yum &> /dev/null; then
        echo "Installing $package using yum..."
        $SUDO yum install -y $package
    elif command -v dnf &> /dev/null; then
        echo "Installing $package using dnf..."
        $SUDO dnf install -y $package
    else
        echo "Error: No supported package manager found (apt, yum, dnf)"
        echo "Please install $package manually"
        return 1
    fi
}

# Check sudo/root
check_sudo

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Python 3 not found. Installing python3..."
    install_package python3
fi

echo "Python 3 found: $(python3 --version)"

# Check if python3-venv is available
if ! python3 -m venv --help &> /dev/null; then
    echo "python3-venv not available. Installing python3-venv..."
    install_package python3-venv
fi

# Check if pip is available
if ! command -v pip3 &> /dev/null && ! python3 -m pip --version &> /dev/null; then
    echo "pip not available. Installing python3-pip..."
    install_package python3-pip
fi

# Define paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/rembg_venv"

# Create virtual environment if it doesn't exist
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating virtual environment in $VENV_DIR..."
    python3 -m venv "$VENV_DIR"
else
    echo "Virtual environment already exists in $VENV_DIR"
fi

# Activate virtual environment
echo "Activating virtual environment..."
source "$VENV_DIR/bin/activate"

# Upgrade pip
echo "Upgrading pip..."
python3 -m pip install --upgrade pip

# Install rembg and dependencies
echo "Installing rembg and dependencies..."
echo "This may take a few minutes, especially on Raspberry Pi..."
python3 -m pip install rembg pillow onnxruntime

# Verify installation
echo "Verifying installation..."
python3 -c "import rembg; print('rembg version:', rembg.__version__)"
python3 -c "import PIL; print('PIL version:', PIL.__version__)"
python3 -c "import onnxruntime; print('onnxruntime version:', onnxruntime.__version__)"

echo ""
echo "rembg installation completed successfully!"
echo "Virtual environment: $VENV_DIR"
echo "To activate manually: source $VENV_DIR/bin/activate"
echo ""
echo "Note: Make sure the rembg_processor.py script uses this virtual environment."
echo "You can now enable rembg in the Photobooth admin panel."