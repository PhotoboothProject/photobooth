# Additional needed steps to enable PHP in Lighttpd

```sh
sudo lighttpd-enable-mod fastcgi
sudo lighttpd-enable-mod fastcgi-php
```

Edit fastcgi-php config, keep a backup of the original file in case something went wrong:
```sh
sudo cp /etc/lighttpd/conf-available/15-fastcgi-php.conf /etc/lighttpd/conf-available/15-fastcgi-php.conf.bak
sudo nano /etc/lighttpd/conf-available/15-fastcgi-php.conf
```

Change the `15-fastcgi-php.conf` from
```
# -*- depends: fastcgi -*-
# /usr/share/doc/lighttpd/fastcgi.txt.gz
# http://redmine.lighttpd.net/projects/lighttpd/wiki/Docs:ConfigurationOptions#mod_fastcgi-fastcgi

## Start an FastCGI server for php (needs the php5-cgi package)
fastcgi.server += ( ".php" =>
	((
		"bin-path" => "/usr/bin/php-cgi",
		"socket" => "/var/run/lighttpd/php.socket",
		"max-procs" => 1,
		"bin-environment" => (
			"PHP_FCGI_CHILDREN" => "4",
			"PHP_FCGI_MAX_REQUESTS" => "10000"
		),
		"bin-copy-environment" => (
			"PATH", "SHELL", "USER"
		),
		"broken-scriptfilename" => "enable"
	))
)
```

to look like this:
```
# -*- depends: fastcgi -*-
# /usr/share/doc/lighttpd/fastcgi.txt.gz
# http://redmine.lighttpd.net/projects/lighttpd/wiki/Docs:ConfigurationOptions#mod_fastcgi-fastcgi

## Start an FastCGI server for php (needs the php5-cgi package)
fastcgi.server += ( ".php" =>
	((
		"socket" => "/var/run/php/php8.2-fpm.sock",
		"broken-scriptfilename" => "enable"
	))
)
```

Now reload the service:
```sh
sudo service lighttpd force-reload
```
