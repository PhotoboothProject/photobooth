#!/bin/bash

inside_git_repo="$(git rev-parse --is-inside-work-tree 2>/dev/null)"

if [ $inside_git_repo == 'true' ];
then
	if [ -z "$(git status --porcelain)" ];
	then
		echo "1"
	else
		echo "2"
	fi
else
	echo "3"
fi

