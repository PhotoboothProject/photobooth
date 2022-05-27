FROM webdevops/php-nginx

# Update and install dependencies
RUN apt update

RUN curl -fsSL https://deb.nodesource.com/setup_12.x | bash -
RUN apt install -y build-essential git php-gd php-zip gphoto2 libimage-exiftool-perl nodejs rsync udisks2
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update && apt install -y yarn

# Copy files
WORKDIR /app
COPY . .

# Install and build
RUN git config --global --add safe.directory /app 
RUN git submodule update --init
RUN yarn install
RUN yarn build
