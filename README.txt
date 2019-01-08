
 CodKep - Lightweight web framework

 Written by Peter Deak (C) hyper80@gmail.com , License GPLv2

 Webpage:        http://hyperprog.com/codkep
 Documentation:  http://hyperprog.com/codkep/doc

                 The documentation on hyperprog.com can be very outdated.
                 After installing use the local "doc/codkep" url to reach
                 the newest available documentation.

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


Docker image
===================================================================
Available with Apline linux and apache webserver on:
 Docker hub: https://hub.docker.com/r/hyperprog/codkepalpine

 Image name:  hyperprog/codkepalpine


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
        # For php5:
        #fastcgi_pass unix:/var/run/php5-fpm.sock;
        # For php7:
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
    }
}

===================================================================
APACHE2.X config

<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName sandbox.example.com
    DocumentRoot /var/www/mypage
    <Directory /var/www/mypage/>
        DirectoryIndex index.php
        AllowOverride None
        Options -Indexes +FollowSymLinks

        #On apache 2.2
         Order allow,deny
         allow from all
        #On apache 2.4
         Require all granted

        RewriteEngine on
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [L]
    </Directory>
</VirtualHost>

===================================================================
If you use apache you can put the rewrite rules into .htaccess file:

DirectoryIndex index.php
Options -Indexes

RewriteEngine on
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]

