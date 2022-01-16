#!/bin/bash

# Stop on the first sign of trouble
set -e

WEBSERVER=(
    'libapache2-mod-php'
    'nginx'
    'lighttpd'
)

COMMON_PACKAGES=(
    'git'
    'gphoto2'
    'libimage-exiftool-perl'
    'nodejs'
    'php-gd'
    'php-zip'
    'yarn'
    'rsync'
    'udisks2'
)

MISSING_PACKAGES=()

echo "[Info]      Checking for webserver..."
for server in "${WEBSERVER[@]}"; do
    if [ $(dpkg-query -W -f='${Status}' ${server} 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
	echo "[Webserver] ${server} used."
	if [[ ${server} == "nginx" || ${server} == "lighttpd" ]]; then
	   echo "[NOTE]      You're using ${server} as your Webserver."
	    echo "[NOTE]      For a no-hassle-setup Apache2 Webserver is recommend!"
	    if [ $(dpkg-query -W -f='${Status}' php-fpm 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
	        echo "[Package]   php-fpm installed already"
	    else
	        echo "[WARNING]   Missing common package: php-fpm"
	    fi
	fi
    fi
done

echo ""
echo ""

echo "[Info]      Checking common software..."
for package in "${COMMON_PACKAGES[@]}"; do
    if [ $(dpkg-query -W -f='${Status}' ${package} 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
	echo "[Package]   ${package} installed already"
    else
	echo "[WARNING]   Missing common package: ${package}"
        MISSING_PACKAGES+=("${package}")
    fi
done

echo ""
echo ""

if [[ ${MISSING_PACKAGES[@]} ]]; then
	echo "[RESULT]    Missing common packages:"
	for dependencie in "${MISSING_PACKAGES[@]}"
	do
		echo $dependencie
	done
	echo "[WARNING]   Please install missing common packages!"
else
	echo "[RESULT]    No common packages missing."
	echo "[OK]"

fi

