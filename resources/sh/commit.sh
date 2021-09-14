#!/bin/bash

# Stop on the first sign of trouble
set -e

date=$(date +"%Y%m%d-%H-%M")
get_username="$(git config user.name)"
set_username="$(git config user.name Photobooth)"
get_useremail="$(git config user.email)"
set_useremail="$(git config user.email Photobooth@localhost)"

if [ -z "$get_username" ];
then
	echo "git user.name not set!"
	echo "Setting git user.name."
	$set_username
fi

if [ -z "$get_useremail" ];
then
	echo "git user.email not set!"
	echo "Setting git user.email."
	$set_useremail
fi


echo "git user.name: $get_username"
echo "git user.email: $get_useremail"

git add --all
git commit -a -m "backup changes"
git checkout -b "backup-$date"
echo "Backup done to branch: backup-$date"

