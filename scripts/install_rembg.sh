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

SCRIPT_DIR="/var/www/html/scripts"
VENV_DIR="$SCRIPT_DIR/rembg_venv"
REQ_FILE="$SCRIPT_DIR/requirements-rembg.txt"

# 1) Install system packages early and simply (without version hassle)
echo_step "Installing system packages (python3, python3-venv, python3-pip)…"
if command -v apt >/dev/null 2>&1; then
  sudo apt update -y
  sudo apt install -y python3 python3-venv python3-pip php-curl
elif command -v dnf >/dev/null 2>&1; then
  sudo dnf install -y python3 python3-pip python3-virtualenv || true
elif command -v yum >/dev/null 2>&1; then
  sudo yum install -y python3 python3-pip python3-virtualenv || true
else
  echo_error "No supported package manager found (apt/dnf/yum)."
  exit 1
fi
echo_success "System packages installed"
echo_success "Python: $(python3 --version)"

# 2) Prepare target directory and set ownership
echo_step "Preparing $SCRIPT_DIR…"
sudo mkdir -p "$SCRIPT_DIR"
if [ "$EUID" -eq 0 ] && [ -n "$SUDO_USER" ]; then
  sudo chown -R "$SUDO_USER":"$SUDO_USER" "$SCRIPT_DIR"
fi

# 3) Create fresh venv (as non-root)
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

# 4) Set venv interpreter/pip (we don't source to avoid root write permissions)
VENV_PY="$VENV_DIR/bin/python"
VENV_PIP="$VENV_DIR/bin/pip"

# 5) Update pip in venv (as non-root)
echo_step "Updating pip in venv…"
if ! RUN_AS_USER "'$VENV_PY' -m pip install --upgrade pip"; then
  echo_error "pip upgrade failed"
  exit 1
fi
echo_success "pip updated: $(RUN_AS_USER "'$VENV_PIP' --version")"

# 6) Install requirements (if file exists), otherwise minimal set
echo_step "Installing requirements…"
if [ -f "$REQ_FILE" ]; then
  if ! RUN_AS_USER "'$VENV_PY' -m pip install -r '$REQ_FILE'"; then
    echo_error "Installation from $REQ_FILE failed"
    exit 1
  fi
else
  echo_warning "No $REQ_FILE found – installing base packages (rembg, flask, pillow)…"
  if ! RUN_AS_USER "'$VENV_PY' -m pip install rembg flask pillow"; then
    echo_error "Base installation failed"
    exit 1
  fi
fi
echo_success "Requirements installed"

# 7) Ensure onnxruntime (sometimes missing in wheel/reqs)
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

# 8) Verification
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

# flask
try:
    print('flask:', v('flask'))
except Exception as e:
    print('FLASK_IMPORT_ERROR:', e, file=sys.stderr)
    raise
PY"

echo ""

# Install systemd service if available
if command -v systemctl &> /dev/null; then
    echo_step "Installing systemd service..."
    sudo tee /etc/systemd/system/rembg.service > /dev/null <<EOF
[Unit]
Description=Rembg Background Removal Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/var/www/html/scripts/rembg_venv/bin/rembg s --host 0.0.0.0 --port 7000 --log_level info

[Install]
WantedBy=multi-user.target
EOF
    sudo systemctl daemon-reload
    sudo systemctl enable rembg
    sudo systemctl start rembg
    echo_success "systemd service installed and started"
else
    echo_warning "systemctl not available, skipping systemd service installation"
fi

echo_header "Installation complete!"
echo_info  "Virtual environment: $VENV_DIR"
echo_info  "To activate manually (in terminal):"
echo_info  "  source $VENV_DIR/bin/activate"
echo ""
echo_info  "Note: Make sure the script rembg_processor.py uses this virtual environment."
echo_success "rembg can now be used in the Photobooth."
