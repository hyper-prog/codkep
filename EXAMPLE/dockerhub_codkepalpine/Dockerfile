FROM alpine
LABEL maintainer="hyper80@gmail.com" Description="Codkep web framework on Apline linux with apache webserver"

RUN apk update
RUN apk add php7-apache2 php7-pdo_mysql php7-pdo_pgsql php7-apcu php7-json php7-gd
RUN mkdir /run/apache2
RUN cp /etc/apache2/httpd.conf /etc/apache2/httpd.conf_orig
RUN cat /etc/apache2/httpd.conf_orig | sed 's/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/' > /etc/apache2/httpd.conf
COPY ./EXAMPLE/dockerhub_codkepalpine/my.conf /etc/apache2/conf.d/my.conf
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log && ln -sf /proc/self/fd/1 /var/log/apache2/error.log
RUN rm -rf /var/www/localhost/htdocs/* && mkdir /var/www/localhost/htdocs/sys
COPY . /var/www/localhost/htdocs/sys/
RUN ln -s /var/www/localhost/htdocs/sys/index.php /var/www/localhost/htdocs/index.php
RUN cd /var/www/localhost/htdocs && mkdir site && mkdir data && chown apache:apache data && chmod 775 data
VOLUME ["/var/www/localhost/htdocs/site","/var/www/localhost/htdocs/data"]

EXPOSE 80

CMD ["-D","FOREGROUND"]
# Srart httpd when container runs
ENTRYPOINT ["/usr/sbin/httpd"]
