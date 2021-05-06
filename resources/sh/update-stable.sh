#!/bin/bash

git fetch origin
git checkout origin/stable3
git submodule update --init
yarn install
yarn build
echo "Update completed!"

