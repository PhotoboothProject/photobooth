#!/bin/bash

get_useremail="$(git config user.email)"
set_useremail="$(git config user.email Photobooth@localhost)"

if [ -z $get_useremail ];
then
	$set_useremail
	echo "git user.email: $get_useremail"
else
	echo "git user.email: $get_useremail"
fi

