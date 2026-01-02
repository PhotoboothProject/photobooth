#!/usr/bin/env python3
import json, glob, sys

paths = sorted(glob.glob("template/collage/**/*.json", recursive=True))
errs = 0
for f in paths:
    try:
        with open(f, "r", encoding="utf-8") as fh:
            json.load(fh)
    except Exception as e:
        print(f + ": " + str(e))
        errs += 1
if errs:
    print("\nFound {} invalid JSON files".format(errs))
    sys.exit(2)
else:
    print("All template JSON files are valid ({} files)".format(len(paths)))
    sys.exit(0)
