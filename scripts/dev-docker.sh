#!/usr/bin/env bash
set -Eeuo pipefail

# Bring up the dev stack (build + watcher) with a bind mount so you can edit locally.
# Call this from the repo root.

docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
