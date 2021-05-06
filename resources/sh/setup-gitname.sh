#!/bin/bash

get_username="$(git config user.name)"
set_username="$(git config user.name Photobooth)"

if [ -z $get_username ];
then
	$set_username
	echo "git user.name: $get_username"
else
	echo "git user.name: $get_username"
fi

