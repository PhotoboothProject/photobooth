FROM webdevops/php-apache:8.2

# Update and install dependencies
RUN apt-get update
RUN apt install -y \
    build-essential \
    git \
    gphoto2 \
    libimage-exiftool-perl \
    rsync \
    udisks2 \
    python3

# Install Nodejs
# https://github.com/nodesource/distributions#debian-versions
RUN apt update &&\
    apt install -y \
    ca-certificates \
    curl \
    gnupg
RUN mkdir -p /etc/apt/keyrings
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_16.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
RUN apt-get update 
RUN apt-get install -y nodejs

# Install Yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update
RUN apt install -y yarn

# Copy files
WORKDIR /app
COPY . .

# Install and build
RUN git config --global --add safe.directory /app 
RUN git submodule update --init
RUN yarn install
RUN yarn build
