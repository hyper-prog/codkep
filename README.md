![CodKep Logo](https://raw.githubusercontent.com/hyper-prog/codkep/master/images/cklogo_mid.png)

CodKep - Lightweight web framework
==================================

CodKep is a lightweight web framework written in [PHP](https://php.net/). 
It has a modular design and use hook system for easy extend the core functions. 
Although the working of base api was inspired by the Drupal CMS (version 7),
the CodKep does not contains or use any codes from Drupal, it's built on own codebase.
(Written from scratch)
It's designed to build very database active sites,
so it has a rich set of form generation tools.

- Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
- Webpage:            http://hyperprog.com/codkep
- Documentation:      http://hyperprog.com/codkep/doc
- Modules for codkep: https://github.com/hyper-prog/codkepmodules

                 The documentation on hyperprog.com can be very outdated.
                 After installing use the local "doc/codkep" url to reach
                 the newest available documentation.

Install on bare machine
------------------------
*Note: The github repository contains the "sys" directory.*

#### Steps to install

    #Step 1: The CodKep needs a php enabled webserver to run.

    $ sudo apt-get install -y apache2 libapache2-mod-php php-gd php-mysql php-pgsql php-apcu
    $ sudo a2enmod rewrite 
    $ cd /var/www/html

    #Step 2: Copy the CodKep files in the webserver root's "sys" directory:

    /var/www/html$ git clone https://github.com/hyper-prog/codkep.git sys

    #Step 3: Make a symlink to sys/index.php

    /var/www/html$ ln -s sys/index.php index.php

In case the php is enabled you shoud see the CodKep's open page in your browser.
The documentation is also available there.

Docker images
-------------
Available with Debian and Apline linux with apache webserver on:
 Docker hub:

- https://hub.docker.com/r/hyperprog/codkepdebian (Debian base)
- https://hub.docker.com/r/hyperprog/codkepalpine (Alpine base)

 Downloadable (pullable) image names:

    hyperprog/codkepdebian
    hyperprog/codkepalpine

To run a pure CodKep container and expose to port 80 
(For example to read the newest documentation)

    $ docker run -t -p 80:80 hyperprog/codkepalpine

Settings for webservers
-----------------------
### NGINX config sample with cleanurl support:

```
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
```

### Apache 2.X config with cleanurl

```
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
```

#### If you use apache you can put the rewrite rules into .htaccess file:

```
DirectoryIndex index.php
Options -Indexes

RewriteEngine on
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```
