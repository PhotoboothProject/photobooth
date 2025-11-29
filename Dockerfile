# Use Ubuntu 24.04 as the base image
FROM ubuntu:24.04

# Set environment variables to avoid interactive prompts during installation
ENV DEBIAN_FRONTEND=noninteractive

# Install all dependencies, configure system, and clean up in a single layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    # Core runtime dependencies
    apache2 \
    php \
    php-cli \
    php-mbstring \
    php-xml \
    php-curl \
    php-zip \
    php-gd \
    gphoto2 \
    libimage-exiftool-perl \
    rsync \
    udisks2 \
    python3 \
    cups \
    ipp-usb \
    avahi-daemon \
    avahi-utils \
    dbus \
    printer-driver-gutenprint \
    # Build dependencies (will be removed later)
    build-essential \
    git \
    ca-certificates \
    curl \
    gnupg \
    # Install Node.js 20.x
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    # Configure www-data user and groups
    && (id -u www-data || useradd -r -s /usr/sbin/nologin www-data) \
    && gpasswd -a www-data plugdev \
    && gpasswd -a www-data lp \
    && gpasswd -a www-data lpadmin \
    && usermod -aG root www-data \
    && mkdir -p /var/www/html \
    && chown -R www-data:www-data /var/www/html \
    && chsh -s /bin/bash www-data \
    # Configure Apache
    && a2enmod rewrite \
    && a2enmod headers \
    && echo "LimitRequestLine 12000" > /etc/apache2/conf-available/limits.conf \
    && a2enconf limits \
    && chown -R www-data:www-data /var/www/ \
    && chmod -R 755 /var/www/

# Switch to the www-data user for building
USER www-data

# Set the working directory
WORKDIR /var/www/html

# Clone the Photobooth repository and build the application, then clean up
RUN rm -rf /var/www/html/* \
    && git clone https://github.com/PhotoboothProject/photobooth /var/www/html \
    && cd /var/www/html \
    && git submodule update --init \
    && npm install \
    && npm run build \
    # Clean up after build to reduce image size
    && npm cache clean --force \
    && rm -rf node_modules/.cache \
    && rm -rf .git

# Switch back to root for final configuration
USER root

# Copy configuration script for my Wireless Canon 1500 printer usb printer maybe do not need this
COPY scripts/configure_printer.sh /usr/local/bin/configure_printer.sh

# Remove build dependencies and clean up to reduce image size
RUN apt-get remove -y \
    build-essential \
    git \
    curl \
    gnupg \
    ca-certificates \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* \
    && rm -rf /var/tmp/*

# Expose ports for HTTP and HTTPS
EXPOSE 80 443

# Start Apache in the foreground
CMD dbus-daemon --system --fork & /usr/sbin/ipp-usb & service cups start && apachectl -D FOREGROUND