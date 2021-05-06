#!/bin/bash
date=$(date +"%Y%m%d")

git add --all
git commit -a -m "backup changes"
git checkout -b "backup-$date"
echo "Backup done to branch: backup-$date. </br> Update now possible. </br> Page reloads automatically."

