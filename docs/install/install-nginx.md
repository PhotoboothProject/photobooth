# Additional needed steps to enable PHP in NGINX

Once NGINX is installed we need to enable PHP in NGINX. If you haven't made any changes to your NGINX config you can run the following commands:
```sh
sudo cp /etc/nginx/sites-enabled/default ~/nginx-default.bak
sudo sed -i 's/^\(\s*\)index index\.html\(.*\)/\1index index\.php index\.html\2/g' /etc/nginx/sites-available/default
sudo sed -i '/location ~ \\.php$ {/s/^\(\s*\)#/\1/g' /etc/nginx/sites-available/default
sudo sed -i '/include snippets\/fastcgi-php.conf/s/^\(\s*\)#/\1/g' /etc/nginx/sites-available/default
sudo sed -i '/fastcgi_pass unix:\/run\/php\//s/^\(\s*\)#/\1/g' /etc/nginx/sites-available/default
sudo sed -i '/.*fastcgi_pass unix:\/run\/php\//,// { /}/s/^\(\s*\)#/\1/g; }' /etc/nginx/sites-available/default
```

If you've made changes by hand already to `/etc/nginx/sites-enabled/default` you have to do all changes by hand:
```sh
sudo nano /etc/nginx/sites-enabled/default
```

Find the line `index index.html index.htm;` and add `index.php` after `index` (the line now should look like this: `index index.php index.html index.htm;`).

Now scroll down until you find a section with the following content:
```
# pass the PHP scripts to FastCGI server
#
# location ~ \.php$ {
```

Edit by removing the `#` characters on the following lines:
```
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

It should look like this:
```
        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
        #
        #       # With php-fpm (or other unix sockets):
                fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        #       # With php-cgi (or other tcp sockets):
        #       fastcgi_pass 127.0.0.1:9000;
        }
```


Test the config once `/etc/nginx/sites-enabled/default` was changed:
```sh
sudo /usr/sbin/nginx -t -c /etc/nginx/nginx.conf &>/dev/null && echo 'config test ok' || echo 'config test failed'
```

If you get the response
```
'config test ok'
```

then it is time to restart the server with:
```sh
sudo systemctl reload nginx
```

