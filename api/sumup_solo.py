#!/usr/bin/env python3
import sys
import time
import json
import os
import subprocess
from datetime import datetime
import requests

TOKEN_FILE = "/var/www/html/config/sumup_token.txt"
JOB_FILE = "/var/www/html/private/photobooth_current_print.json"
PRINT_SCRIPT = "/var/www/html/api/sumup_print_wrapper.php"

ACCESS_TOKEN = ""
MERCHANT_CODE = None
READER_ID = None
AFFILIATE_KEY = None
AMOUNT_CENTS = 100
CURRENCY = "EUR"
DESCRIPTION = "Fotobox Ausdruck"

POLL_INTERVAL = 3
MAX_WAIT_SECONDS = 180

API_BASE_V01 = "https://api.sumup.com/v0.1"
API_BASE_V21 = "https://api.sumup.com/v2.1"


def load_access_token():
    global ACCESS_TOKEN

    try:
        with open(TOKEN_FILE, "r", encoding="utf-8") as f:
            ACCESS_TOKEN = f.read().strip()
    except Exception as e:
        print(f"ERROR: token file unreadable: {e}", file=sys.stderr)
        sys.exit(1)

    if not ACCESS_TOKEN:
        print("ERROR: token file is empty", file=sys.stderr)
        sys.exit(1)


def auth_headers():
    return {
        "Authorization": f"Bearer {ACCESS_TOKEN}",
        "Content-Type": "application/json",
    }


def parse_args():
    global MERCHANT_CODE, READER_ID, AFFILIATE_KEY, AMOUNT_CENTS

    if len(sys.argv) < 5:
        print(
            "Usage: sumup_solo.py MERCHANT READER AFFILIATE AMOUNT_CENTS",
            file=sys.stderr,
        )
        sys.exit(1)

    MERCHANT_CODE = sys.argv[1].strip()
    READER_ID = sys.argv[2].strip()
    AFFILIATE_KEY = sys.argv[3].strip()

    try:
        AMOUNT_CENTS = int(sys.argv[4])
    except ValueError:
        print("ERROR: AMOUNT_CENTS must be an integer", file=sys.stderr)
        sys.exit(1)

    if not MERCHANT_CODE:
        print("ERROR: MERCHANT_CODE missing", file=sys.stderr)
        sys.exit(1)

    if not READER_ID:
        print("ERROR: READER_ID missing", file=sys.stderr)
        sys.exit(1)

    if not AFFILIATE_KEY:
        print("ERROR: AFFILIATE_KEY missing", file=sys.stderr)
        sys.exit(1)

    if AMOUNT_CENTS <= 0:
        print("ERROR: AMOUNT_CENTS invalid", file=sys.stderr)
        sys.exit(1)


def start_checkout():
    checkout_reference = f"PHOTO-{datetime.now().strftime('%Y%m%d-%H%M%S')}"

    url = f"{API_BASE_V01}/merchants/{MERCHANT_CODE}/readers/{READER_ID}/checkout"

    payload = {
        "affiliate": {
            "app_id": "photobooth.local",
            "key": AFFILIATE_KEY,
            "foreign_transaction_id": checkout_reference,
        },
        "total_amount": {
            "value": AMOUNT_CENTS,
            "currency": CURRENCY,
            "minor_unit": 2,
        },
        "description": DESCRIPTION,
    }

    response = requests.post(url, json=payload, headers=auth_headers(), timeout=30)

    if response.status_code not in (200, 201):
        print(
            f"ERROR: checkout failed status={response.status_code} body={response.text}",
            file=sys.stderr,
        )
        return None, None

    data = response.json().get("data", {})
    client_transaction_id = data.get("client_transaction_id")

    if not client_transaction_id:
        print(
            "ERROR: checkout succeeded but no client_transaction_id returned",
            file=sys.stderr,
        )
        return None, checkout_reference

    return client_transaction_id, checkout_reference


def get_transaction_status(client_transaction_id):
    url = f"{API_BASE_V21}/merchants/{MERCHANT_CODE}/transactions"

    response = requests.get(
        url,
        params={"client_transaction_id": client_transaction_id},
        headers={"Authorization": f"Bearer {ACCESS_TOKEN}"},
        timeout=20,
    )

    if response.status_code == 404:
        return None, "not_found_yet"

    if response.status_code != 200:
        return None, f"http_{response.status_code}:{response.text}"

    data = response.json()

    if "status" in data:
        return data["status"], None

    if "items" in data and data["items"]:
        return data["items"][0].get("status"), None

    return None, "empty_response"


def finish_print_job():
    if not os.path.exists(JOB_FILE):
        print("INFO: JOB_FILE not found, nothing to print", file=sys.stderr)
        return True

    try:
        with open(JOB_FILE, "r", encoding="utf-8") as f:
            job = json.load(f)
    except Exception as e:
        print(f"ERROR: could not read JOB_FILE: {e}", file=sys.stderr)
        return False

    if job.get("printed"):
        print("INFO: job already printed", file=sys.stderr)
        return True

    filename = job.get("filename")
    copies = int(job.get("copies", 1))

    if not filename:
        print("ERROR: filename missing in JOB_FILE", file=sys.stderr)
        return False

    cmd = ["php", PRINT_SCRIPT, str(filename), str(copies)]

    result = subprocess.run(
        cmd, capture_output=True, text=True, cwd="/var/www/html/api"
    )

    print("PRINT OUTPUT STDOUT:", result.stdout, file=sys.stderr)
    print("PRINT OUTPUT STDERR:", result.stderr, file=sys.stderr)
    print("PRINT RETURN CODE:", result.returncode, file=sys.stderr)

    if result.returncode != 0:
        return False

    job["paid"] = True
    job["printed"] = True

    try:
        with open(JOB_FILE, "w", encoding="utf-8") as f:
            json.dump(job, f, indent=4)
    except Exception as e:
        print(f"ERROR: could not update JOB_FILE: {e}", file=sys.stderr)
        return False

    return True


def main():
    load_access_token()
    parse_args()

    tx_id, checkout_reference = start_checkout()
    if not tx_id:
        sys.exit(1)

    print(
        f"INFO: checkout started client_transaction_id={tx_id} foreign_transaction_id={checkout_reference}",
        file=sys.stderr,
    )

    deadline = time.time() + MAX_WAIT_SECONDS
    saw_404 = False

    while time.time() < deadline:
        status, error = get_transaction_status(tx_id)

        if status == "SUCCESSFUL":
            ok = finish_print_job()
            sys.exit(0 if ok else 4)

        if status in ("FAILED", "CANCELLED"):
            sys.exit(3)

        if error == "not_found_yet":
            saw_404 = True
            time.sleep(POLL_INTERVAL)
            continue

        if error:
            print(f"ERROR: status poll failed {error}", file=sys.stderr)
            time.sleep(POLL_INTERVAL)
            continue

        time.sleep(POLL_INTERVAL)

    if saw_404:
        print("ERROR: payment timeout after temporary 404 responses", file=sys.stderr)
    else:
        print("ERROR: payment timeout", file=sys.stderr)

    sys.exit(2)


if __name__ == "__main__":
    main()
