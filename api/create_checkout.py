import sys
import uuid
import requests

TOKEN_FILE = "/var/www/html/config/sumup_token.txt"

ACCESS_TOKEN = ""
MERCHANT_CODE = ""
AMOUNT_CENTS = 0
RETURN_URL = ""

API_BASE_V01 = "https://api.sumup.com/v0.1"


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
    global MERCHANT_CODE, AMOUNT_CENTS, RETURN_URL

    if len(sys.argv) < 4:
        print(
            "Usage: create_checkout.py MERCHANT_CODE AMOUNT_CENTS RETURN_URL",
            file=sys.stderr,
        )
        sys.exit(1)

    MERCHANT_CODE = sys.argv[1].strip()

    try:
        AMOUNT_CENTS = int(sys.argv[2])
    except ValueError:
        print("ERROR: AMOUNT_CENTS must be an integer", file=sys.stderr)
        sys.exit(1)

    RETURN_URL = sys.argv[3].strip()

    if not MERCHANT_CODE:
        print("ERROR: MERCHANT_CODE missing", file=sys.stderr)
        sys.exit(1)

    if AMOUNT_CENTS <= 0:
        print("ERROR: AMOUNT_CENTS invalid", file=sys.stderr)
        sys.exit(1)

    if not RETURN_URL:
        print("ERROR: RETURN_URL missing", file=sys.stderr)
        sys.exit(1)


def main():
    load_access_token()
    parse_args()

    checkout_reference = "PHOTO-" + str(uuid.uuid4())[:8]
    amount_eur = AMOUNT_CENTS / 100.0

    url = f"{API_BASE_V01}/checkouts"

    payload = {
        "checkout_reference": checkout_reference,
        "amount": amount_eur,
        "currency": "EUR",
        "merchant_code": MERCHANT_CODE,
        "description": "Fotobox Ausdruck",
        "hosted_checkout": {"enabled": True},
        "return_url": RETURN_URL,
    }

    response = requests.post(url, json=payload, headers=auth_headers(), timeout=30)

    print("Status:", response.status_code)
    print("Antwort:", response.text)

    if response.status_code != 201:
        sys.exit(1)

    data = response.json()
    payment_link = data.get("hosted_checkout_url", "").strip()

    if not payment_link:
        print("ERROR: hosted_checkout_url missing", file=sys.stderr)
        sys.exit(1)

    print(payment_link)


if __name__ == "__main__":
    main()
