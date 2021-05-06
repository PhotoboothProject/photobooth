#!/bin/bash

git fetch origin
git checkout origin/dev
git submodule update --init
yarn install
yarn build
echo "Update completed!"

