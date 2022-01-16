#!/bin/bash

# Stop on the first sign of trouble
set -e

git fetch origin
git checkout origin/dev
git submodule update --init
yarn install
yarn build
echo "Update completed!"
echo "Checked out on HEAD:"
git log --format="%h %s" -n 1

