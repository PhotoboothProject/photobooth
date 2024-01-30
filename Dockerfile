FROM webdevops/php-apache:8.3

# Adjust LimitRequestLine
RUN echo "LimitRequestLine 12000" > /opt/docker/etc/httpd/conf.d/limits.conf

# Update and install dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    build-essential \
    git \
    gphoto2 \
    libimage-exiftool-perl \
    rsync \
    udisks2 \
    python3 && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Nodejs
# https://github.com/nodesource/distributions#debian-versions
RUN apt-get update &&\
    apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - &&\
    apt-get install -y --no-install-recommends nodejs && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Copy files
WORKDIR /app
COPY . .
RUN chown -R application:application /app

# switch to application user
USER application

# Install and build
RUN git config --global --add safe.directory /app
RUN git submodule update --init
RUN npm install
RUN npm run build
