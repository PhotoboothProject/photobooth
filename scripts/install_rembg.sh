#!/bin/bash

# Script to install rembg and dependencies in a virtual environment
# This script should be run before enabling rembg in the Photobooth menu

set -e  # Exit on any error

echo "Installing rembg for Photobooth..."

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Error: Python 3 is not installed. Please install Python 3 first."
    exit 1
fi

echo "Python 3 found: $(python3 --version)"

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
pip install --upgrade pip

# Install rembg and dependencies
echo "Installing rembg and dependencies..."
pip install rembg pillow onnxruntime

# Verify installation
echo "Verifying installation..."
python3 -c "import rembg; print('rembg version:', rembg.__version__)"
python3 -c "import PIL; print('PIL version:', PIL.__version__)"
python3 -c "import onnxruntime; print('onnxruntime version:', onnxruntime.__version__)"

echo "rembg installation completed successfully!"
echo "Virtual environment: $VENV_DIR"
echo "To activate manually: source $VENV_DIR/bin/activate"
echo ""
echo "Note: Make sure the rembg_processor.py script uses this virtual environment."