
 CodKep - Lightweight web framework

 Written by Peter Deak (C) hyper80@gmail.com , License GPLv2

 Webpage:        http://hyperprog.com/codkep
 Documentation:  http://hyperprog.com/codkep/doc

Install:
===================================================================
Note: The github repository contains the "sys" directory.

Step-1:
    The CodKep needs a php enabled webserver to run.

Step-2:
    Create a CodKep installation in webserver root's sys:

    /var/www/html$ git clone https://github.com/hyper-prog/codkep.git sys

Step-3:
    Make a symlink to sys/index.php

    /var/www/html$ ln -s sys/index.php index.php

In case the php is enabled you shoud see the CodKep's open page in your browser.
The documentation is also available there.


Note for webserver config with cleanurl support:
===================================================================
NGINX config

server {
    listen 80;
    listen [::]:80;

    server_name sandbox.example.com;

    root /var/www/mypage;
    index index.php;

    location / {
        try_files $uri @rewrite;
    }

    location @rewrite {
        rewrite ^ /index.php;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php5-fpm.sock;
    }
}

===================================================================
APACHE2 config

<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName sandbox.example.com
    DocumentRoot /var/www/mypage
    <Directory /var/www/mypage/>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        allow from all
        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [L]
        #RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    LogLevel warn
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

===================================================================
.htaccess

DirectoryIndex index.php
Options -Indexes

RewriteEngine on
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]

