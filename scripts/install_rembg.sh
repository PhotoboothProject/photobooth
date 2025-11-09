#!/bin/bash
set -e

# ── Farben ─────────────────────────────────────────────────────────────────────
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

# ── Kommandos als ursprünglicher Nutzer (nicht root) ───────────────────────────
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

# 1) Systempakete früh und simpel installieren (ohne Versions-Fummelei)
echo_step "Installiere Systempakete (python3, python3-venv, python3-pip)…"
if command -v apt >/dev/null 2>&1; then
  sudo apt update -y
  sudo apt install -y python3 python3-venv python3-pip
elif command -v dnf >/dev/null 2>&1; then
  sudo dnf install -y python3 python3-pip python3-virtualenv || true
elif command -v yum >/dev/null 2>&1; then
  sudo yum install -y python3 python3-pip python3-virtualenv || true
else
  echo_error "Kein unterstützter Paketmanager gefunden (apt/dnf/yum)."
  exit 1
fi
echo_success "Systempakete installiert"
echo_success "Python: $(python3 --version)"

# 2) Zielordner vorbereiten und Eigentümer setzen
echo_step "Vorbereiten von $SCRIPT_DIR…"
sudo mkdir -p "$SCRIPT_DIR"
if [ "$EUID" -eq 0 ] && [ -n "$SUDO_USER" ]; then
  sudo chown -R "$SUDO_USER":"$SUDO_USER" "$SCRIPT_DIR"
fi

# 3) Venv frisch erstellen (als Nicht-Root)
cd "$SCRIPT_DIR"
if [ -d "$VENV_DIR" ]; then
  echo_warning "Bestehende venv gefunden → entferne $VENV_DIR…"
  rm -rf "$VENV_DIR"
fi

echo_step "Erstelle virtuelle Umgebung in $VENV_DIR…"
if ! RUN_AS_USER "python3 -m venv '$VENV_DIR'"; then
  echo_error "Erstellen der venv fehlgeschlagen. Prüfe, ob 'python3-venv' korrekt installiert ist."
  exit 1
fi
echo_success "venv erstellt"

# 4) Venv-Interpreter/Pip festlegen (wir sourcen nicht, um Root-Schreibrechte zu vermeiden)
VENV_PY="$VENV_DIR/bin/python"
VENV_PIP="$VENV_DIR/bin/pip"

# 5) pip in der venv aktualisieren (als Nicht-Root)
echo_step "Aktualisiere pip in der venv…"
if ! RUN_AS_USER "'$VENV_PY' -m pip install --upgrade pip"; then
  echo_error "pip-Upgrade fehlgeschlagen"
  exit 1
fi
echo_success "pip aktualisiert: $(RUN_AS_USER "'$VENV_PIP' --version")"

# 6) Anforderungen installieren (falls Datei vorhanden), sonst Minimal-Set
echo_step "Installiere Anforderungen…"
if [ -f "$REQ_FILE" ]; then
  if ! RUN_AS_USER "'$VENV_PY' -m pip install -r '$REQ_FILE'"; then
    echo_error "Installation aus $REQ_FILE fehlgeschlagen"
    exit 1
  fi
else
  echo_warning "Keine $REQ_FILE gefunden – installiere Basis-Pakete (rembg, flask, pillow)…"
  if ! RUN_AS_USER "'$VENV_PY' -m pip install rembg flask pillow"; then
    echo_error "Basis-Installation fehlgeschlagen"
    exit 1
  fi
fi
echo_success "Anforderungen installiert"

# 7) onnxruntime sicherstellen (manchmal fehlt es in wheel/reqs)
echo_step "Stelle onnxruntime sicher…"
if ! RUN_AS_USER "'$VENV_PY' - <<'PY'
try:
    import onnxruntime  # noqa: F401
except Exception:
    raise SystemExit(1)
PY"; then
  echo_warning "onnxruntime fehlt – installiere…"
  if ! RUN_AS_USER "'$VENV_PY' -m pip install onnxruntime"; then
    echo_error "onnxruntime-Installation fehlgeschlagen"
    exit 1
  fi
fi
echo_success "onnxruntime verfügbar"

# 8) Verifikation
echo_step "Verifiziere Installation…"
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
echo_header "Installation abgeschlossen!"
echo_info  "Virtual environment: $VENV_DIR"
echo_info  "Manuell aktivieren (im Terminal):"
echo_info  "  source $VENV_DIR/bin/activate"
echo_success "rembg kann jetzt im Photobooth genutzt werden."
