"""Run against the disposable, offline app fixture; never against a user installation."""

import argparse
import base64
import http.cookiejar
import json
from pathlib import Path
import re
import shutil
import urllib.parse
import urllib.request
import uuid

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--fixture-root", required=True, type=Path)
parser.add_argument("--url", default="http://127.0.0.1:19877")
args = parser.parse_args()
root = args.fixture_root.resolve()
assert root == Path("/tmp/photobooth-goal-app") or (
    root == Path("/var/www/html") and Path("/.dockerenv").is_file()
), "Requires the disposable goal fixture"
config = (root / "config/my.config.inc.php").read_text()
assert "'username' => 'synthetic'" in config
assert "'upload_in_background' => true" in config
assert "'keep_original' => true" in config

client = urllib.request.build_opener(
    urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
)
settings = client.open(args.url + "/api/settings.php").read().decode()
token = re.search(r'const csrf = \{ key: "csrf", token: "([^"]+)"', settings)[1]


def post(endpoint, **data):
    data["csrf"] = token
    request = urllib.request.Request(
        args.url + endpoint, urllib.parse.urlencode(data).encode()
    )
    result = json.loads(client.open(request).read())
    assert "error" not in result, result
    return result


def jobs(filename):
    return [
        job
        for path in (root / "data/remote-queue").glob("*.json")
        if (job := json.loads(path.read_text()))["file"] == filename
    ]


results = {}
for style in ("photo", "collage", "chroma"):
    filename = "trigger-" + uuid.uuid4().hex + ".jpg"
    shutil.copyfile(root / "data/tmp/photo.jpg", root / "data/tmp" / filename)
    if style == "collage":
        for index in range(4):
            shutil.copyfile(
                root / "data/tmp/photo.jpg",
                root / "data/tmp" / filename.replace(".jpg", f"-{index}.jpg"),
            )
    post(
        "/api/applyEffects.php",
        file=filename,
        style=style,
        filter="plain",
        collageLayout="2+2-1",
    )
    exists = (root / "data/images" / filename).is_file()
    results[style] = (
        len(jobs(filename)) == 1 and exists
        if style != "chroma"
        else not jobs(filename) and not exists
    )
    if style == "chroma":
        image = base64.b64encode((root / "data/tmp/photo.jpg").read_bytes()).decode()
        saved = post(
            "/api/chromakeying/save.php",
            file=filename,
            imgData="data:image/jpeg;base64," + image,
        )["filename"]
        results["chroma_saved"] = (
            len(jobs(saved)) == 1
            and (root / "data/images" / saved).is_file()
            and (root / "data/thumbs" / saved).is_file()
        )

print(json.dumps(results, indent=2))
assert all(results.values()), "Upload trigger regression"
