"""Exercise installer failure paths without installing packages or changing the host."""

import subprocess
import unittest
from pathlib import Path


SOURCE = (Path(__file__).resolve().parents[1] / "install-photobooth.sh").read_text()
CLONE = SOURCE.split("function do_git_clone() {", 1)[1].split(
    "function start_git_install()", 1
)[0]
CLONE = "function do_git_clone() {" + CLONE
SILENT = SOURCE.rsplit('if [ "$SILENT" = true ]; then', 1)[1].split(
    "else\n    # Non-silent mode", 1
)[0]


class InstallerFailureTest(unittest.TestCase):
    def run_bash(self, script):
        return subprocess.run(["bash", "-c", script], capture_output=True, text=True)

    def run_clone(self, status):
        result = self.run_bash(
            CLONE
            + """
INSTALLFOLDERPATH=/unused
mkdir() { :; }
chown() { :; }
info() { :; }
error() { :; }
confirm() { :; }
"""
            + f"sudo() {{ echo 'simulated git diagnostic' >&2; return {status}; }}\n"
            + "do_git_clone\n"
        )
        return result

    def test_clone_failure_is_visible(self):
        result = self.run_clone(128)
        self.assertEqual(result.returncode, 1)
        self.assertIn("simulated git diagnostic", result.stderr)

    def test_clone_success(self):
        self.assertEqual(self.run_clone(0).returncode, 0)

    def test_silent_install_status(self):
        for status in (0, 1, 2):
            with self.subTest(status=status):
                result = self.run_bash(
                    """
PHOTOBOOTH_FOUND=false
UPDATE=false
photobooth_installed() { return 1; }
check_photobooth_version() { :; }
install_system_icon() { echo 'post-install'; }
"""
                    + f"install_or_update_photobooth() {{ return {status}; }}\n"
                    + SILENT
                )
                self.assertEqual(result.returncode == 0, status == 0)
                self.assertEqual("post-install" in result.stdout, status == 0)


if __name__ == "__main__":
    unittest.main()
