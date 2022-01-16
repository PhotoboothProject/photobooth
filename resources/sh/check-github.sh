#!/bin/bash

# Stop on the first sign of trouble
set -e

inside_git_repo="$(git rev-parse --is-inside-work-tree 2>/dev/null)"

if [ $inside_git_repo == 'true' ];
then
	if [ -z "$(git status --porcelain)" ];
	then
		echo "true"
	else
		echo "commit"
	fi
else
	echo "false"
fi

