#!/usr/bin/env python3
import os
import json
import subprocess
from flask import Flask, request, jsonify

app = Flask(__name__)

JOB_FILE = "/var/www/html/private/photobooth_current_print.json"
# NEU: Pfad zum neuen Wrapper
PRINT_SCRIPT = "/var/www/html/api/sumup_print_wrapper.php" 


@app.route("/sumup/webhook", methods=["POST"])
def sumup_webhook():
    try:
        data = request.get_json(force=True, silent=True) or {}

        print("\n=== WEBHOOK ===")
        print(data)

        event_type = data.get("event_type")
        status = data.get("status")

        if event_type == "CHECKOUT_STATUS_CHANGED" and status == "SUCCESSFUL":
            print("QR-Zahlung erfolgreich erkannt")

            if not os.path.exists(JOB_FILE):
                print("JOB_FILE fehlt")
                return jsonify({"status": "no_job"}), 200

            with open(JOB_FILE, "r", encoding="utf-8") as f:
                job = json.load(f)

            if job.get("printed"):
                print("Job wurde bereits gedruckt")
                return jsonify({"status": "already_printed"}), 200

            filename = job.get("filename")
            copies = job.get("copies", 1)

            if not filename:
                print("Kein filename im Job")
                return jsonify({"status": "invalid_job"}), 200

            print(f"Starte Druck via Wrapper: {filename} ({copies}x)")

            cmd = [
                "php",
                PRINT_SCRIPT,
                str(filename),
                str(copies)
            ]

            # KORREKTUR: Hinzufügen von cwd="/var/www/html/api" für relative Pfade
            result = subprocess.run(
                cmd, 
                capture_output=True, 
                text=True, 
                cwd="/var/www/html/api"
            )

            print("PRINT OUTPUT STDOUT:")
            print(result.stdout)
            print("PRINT OUTPUT STDERR:")
            print(result.stderr)
            print("RETURN CODE:", result.returncode)

            if result.returncode == 0:
                job["printed"] = True
                job["paid"] = True
                print("Job erfolgreich gedruckt")
            else:
                print("Druck FEHLGESCHLAGEN – Job bleibt offen")

            with open(JOB_FILE, "w", encoding="utf-8") as f:
                json.dump(job, f, indent=4)

            print("Job gespeichert")

            return jsonify({"status": "printed"}), 200

        return jsonify({"status": "ignored"}), 200

    except Exception as e:
        print("ERROR:", str(e))
        return jsonify({"status": "error", "message": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
