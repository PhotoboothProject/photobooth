#!/bin/bash

# Stop on the first sign of trouble
set -e

git fetch origin
git checkout origin/stable3
git submodule update --init
yarn install
yarn build
echo "Update completed!"

