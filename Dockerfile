FROM webdevops/php-apache:8.4

# Adjust LimitRequestLine and
# update and install dependencies
RUN echo "LimitRequestLine 12000" > /opt/docker/etc/httpd/conf.d/limits.conf \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        git \
        gphoto2 \
        libimage-exiftool-perl \
        rsync \
        udisks2 \
        python3 \
        ca-certificates \
        curl \
        gnupg \
        nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy files
WORKDIR /app
COPY . .

RUN chown -R application:application /app

# switch to application user
USER application

# Install and build
RUN git config --global --add safe.directory /app \
    && git submodule update --init \
    && npm install \
    && npm run build
