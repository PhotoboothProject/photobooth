#!/bin/bash
set -e

# ── Colors ─────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; PURPLE='\033[0;35m'; CYAN='\033[0;36m'; NC='\033[0m'
echo_info()    { echo -e "${BLUE} $1${NC}"; }
echo_success() { echo -e "${GREEN} $1${NC}"; }
echo_warning() { echo -e "${YELLOW} $1${NC}"; }
echo_error()   { echo -e "${RED} $1${NC}"; }
echo_step()    { echo -e "${PURPLE} $1${NC}"; }
echo_header()  {
  echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  echo -e "${CYAN} $1${NC}"
  echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# ── Run commands as original user (not root) ───────────────────────────────────
RUN_AS_USER() {
  if [ "$EUID" -eq 0 ] && [ -n "$SUDO_USER" ] && [ "$SUDO_USER" != "root" ]; then
    sudo -u "$SUDO_USER" -- bash -lc "$*"
  else
    bash -lc "$*"
  fi
}

# ── Start ──────────────────────────────────────────────────────────────────────
echo_header "Installing rembg for Photobooth"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/rembg_venv"
REQ_FILE="$SCRIPT_DIR/requirements-rembg.txt"

# 1) Check if python3 is available
if ! command -v python3 >/dev/null 2>&1; then
    echo_error "python3 not found. Installing..."
fi

# 2) Install system packages early and simply (without version hassle)
echo_step "Installing system packages (python3, python3-venv, python3-pip)…"
if command -v apt >/dev/null 2>&1; then
  sudo apt update -y
  sudo apt install -y python3 python3-venv python3-pip php-curl
elif command -v dnf >/dev/null 2>&1; then
  sudo dnf install -y python3 python3-pip python3-virtualenv php-curl || true
elif command -v yum >/dev/null 2>&1; then
  sudo yum install -y python3 python3-pip python3-virtualenv php-curl || true
else
  echo_error "No supported package manager found (apt/dnf/yum)."
  exit 1
fi
echo_success "System packages installed"

# Verify python3 is now available
if ! command -v python3 >/dev/null 2>&1; then
    echo_error "python3 installation failed"
    exit 1
fi
echo_success "Python: $(python3 --version)"

# Get Python version
PYVER="$(python3 -c 'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")')"
echo_info "Python version: $PYVER"

# 3) Prepare target directory and set ownership
echo_step "Preparing $SCRIPT_DIR…"
sudo mkdir -p "$SCRIPT_DIR"
if [ "$EUID" -eq 0 ] && [ -n "$SUDO_USER" ]; then
  sudo chown -R "$SUDO_USER":"$SUDO_USER" "$SCRIPT_DIR"
fi

# 4) Create fresh venv (as non-root)
cd "$SCRIPT_DIR"
if [ -d "$VENV_DIR" ]; then
  echo_warning "Existing venv found → removing $VENV_DIR…"
  rm -rf "$VENV_DIR"
fi

echo_step "Creating virtual environment in $VENV_DIR…"
if ! RUN_AS_USER "python3 -m venv '$VENV_DIR'"; then
  echo_error "Creating venv failed. Check if 'python3-venv' is correctly installed."
  exit 1
fi
echo_success "venv created"

# 5) Set venv interpreter/pip (we don't source to avoid root write permissions)
VENV_PY="$VENV_DIR/bin/python"
VENV_PIP="$VENV_DIR/bin/pip"

# Verify venv binaries exist
if [ ! -f "$VENV_PY" ]; then
  echo_error "Virtual environment seems broken: $VENV_PY not found"
  exit 1
fi
if [ ! -f "$VENV_PIP" ]; then
  echo_error "Virtual environment seems broken: $VENV_PIP not found"
  exit 1
fi

# 6) Update pip in venv (as non-root)
echo_step "Updating pip in venv…"
if ! RUN_AS_USER "'$VENV_PY' -m pip install --upgrade pip"; then
  echo_error "pip upgrade failed"
  exit 1
fi
echo_success "pip updated: $(RUN_AS_USER "'$VENV_PIP' --version")"

# 7) Install requirements (if file exists), otherwise minimal set
echo_step "Installing requirements…"
if [ -f "$REQ_FILE" ]; then
  if ! RUN_AS_USER "'$VENV_PY' -m pip install -r '$REQ_FILE'"; then
    echo_error "Installation from $REQ_FILE failed"
    exit 1
  fi
else
  echo_warning "No $REQ_FILE found – installing base packages (rembg, pillow)…"
  if ! RUN_AS_USER "'$VENV_PY' -m pip install rembg pillow"; then
    echo_error "Base installation failed"
    exit 1
  fi
fi
echo_success "Requirements installed"

# 8) Ensure onnxruntime (sometimes missing in wheel/reqs)
echo_step "Ensuring onnxruntime…"
if ! RUN_AS_USER "'$VENV_PY' - <<'PY'
try:
    import onnxruntime  # noqa: F401
except Exception:
    raise SystemExit(1)
PY"; then
  echo_warning "onnxruntime missing – installing…"
  if ! RUN_AS_USER "'$VENV_PY' -m pip install onnxruntime"; then
    echo_error "onnxruntime installation failed"
    exit 1
  fi
fi
echo_success "onnxruntime available"

# 9) Verification
echo_step "Verifying installation…"
RUN_AS_USER "'$VENV_PY' - <<'PY'
import importlib, sys
def v(pkg):
    try:
        return importlib.metadata.version(pkg)
    except Exception:
        return 'unknown'

# rembg
try:
    import rembg  # noqa: F401
    print('rembg:', v('rembg'))
except Exception as e:
    print('REMBG_IMPORT_ERROR:', e, file=sys.stderr)
    raise

# onnxruntime
try:
    import onnxruntime as ort  # noqa: F401
    print('onnxruntime:', v('onnxruntime'))
except Exception as e:
    print('ONNXRUNTIME_IMPORT_ERROR:', e, file=sys.stderr)
    raise

# pillow
try:
    import PIL  # noqa: F401
    print('Pillow:', v('Pillow'))
except Exception as e:
    print('PIL_IMPORT_ERROR:', e, file=sys.stderr)
    raise
PY"

echo ""

# 10) Install systemd service if available
if command -v systemctl &> /dev/null; then
    echo_step "Installing systemd service..."

    # Backup existing service if present
    if [ -f /etc/systemd/system/rembg.service ]; then
        echo_warning "Existing rembg.service found - backing up..."
        sudo cp /etc/systemd/system/rembg.service /etc/systemd/system/rembg.service.bak
    fi

    sudo tee /etc/systemd/system/rembg.service > /dev/null <<EOF
[Unit]
Description=Rembg Background Removal Service
After=network.target

[Service]
Type=simple
User=$SUDO_USER
WorkingDirectory=$SCRIPT_DIR
ExecStart=$VENV_DIR/bin/rembg s --host 0.0.0.0 --port 7000 --log_level info
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

    echo_step "Reloading systemd daemon..."
    sudo systemctl daemon-reload

    echo_step "Enabling rembg service..."
    if ! sudo systemctl enable rembg.service; then
        echo_error "Failed to enable rembg service"
        exit 1
    fi

    echo_step "Starting rembg service..."
    if ! sudo systemctl start rembg.service; then
        echo_error "Failed to start rembg service"
        echo_info "Check logs with: sudo journalctl -u rembg.service -n 20"
        exit 1
    fi

    echo_success "systemd service installed and started"
    echo_info "Check status with: sudo systemctl status rembg.service"
else
    echo_warning "systemctl not found - skipping service installation"
fi

echo ""
echo_header "Installation Complete!"
echo_success "rembg is now installed and ready to use"
echo_info "Service endpoint: http://localhost:7000/api"
